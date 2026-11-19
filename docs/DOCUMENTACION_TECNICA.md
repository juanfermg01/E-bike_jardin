# Documentación técnica — E-Bike Jardín

## 1. Arquitectura general

Aplicación **MVC monolítica** sin dependencias externas de PHP (sin Composer). El flujo de una petición es:

```
Navegador → public/index.php → Router → Middleware → Controlador → Modelo/PDO → Vista + Layout
```

- `public/index.php`: único punto de entrada. Carga configuración, helpers, autoloader, sesión, cabeceras de seguridad, valida CSRF en POST y despacha la ruta.
- `app/core/Router.php`: registra rutas `GET`/`POST`, convierte `{param}` en grupos regex y ejecuta el middleware (`auth`, `guest`, `staff`, `admin`).
- `app/core/Database.php`: singleton PDO con `ERRMODE_EXCEPTION`, `FETCH_ASSOC` y sentencias preparadas reales (`EMULATE_PREPARES = false`). Expone `fetch`, `fetchAll`, `scalar`, `execute`, `lastInsertId`, `begin`, `commit`, `rollback`.
- `app/controllers/*`: validan entrada, aplican reglas de negocio y llaman a `view()`.
- `app/views/*`: HTML con escape mediante `e()`. Los layouts (`layouts/app`, `layouts/guest`) envuelven el contenido.
- `app/helpers/functions.php`: utilidades de vista, CSRF, formato de moneda/fecha, paginación, UUID v4, manejo de errores.

## 2. Modelo de datos

Base de datos `ebike_jardin`, motor InnoDB, charset `utf8mb4_unicode_ci`. Las tablas respetan el orden del diagrama ER:

| # | Tabla | Propósito | Llaves foráneas |
|---|---|---|---|
| 1 | `roles` | Roles y nivel de acceso | — |
| 2 | `usuarios` | Cuentas del sistema | `rol_id → roles` (RESTRICT) |
| 3 | `metodos_pago` | Métodos del cliente (sin datos sensibles) | `id_usuario → usuarios` (CASCADE) |
| 4 | `estaciones` | Puntos de alquiler con coordenadas `POINT` | — |
| 5 | `bicicletas` | Flota, batería, kilometraje y mantenimiento | `estacion_act_fk → estaciones` (SET NULL) |
| 6 | `alquileres` | Recorridos y costos | `id_usuario`, `id_bicicleta`, `estacion_orig_fk`, `estacion_dest_fk` (RESTRICT) |
| 7 | `transacciones` | Pagos asociados a un alquiler | `id_alquiler_fk → alquileres` (CASCADE), `id_metodo_pago_fk → metodos_pago` (SET NULL) |
| 8 | `mantenimientos` | Órdenes de taller | `id_bicicleta_fk → bicicletas` (CASCADE) |
| 9 | `registro_energia` | Consumo eléctrico por estación | `id_estacion_fk → estaciones` (CASCADE) |

Elementos adicionales:

- Vista `v_estaciones_mapa`: expone latitud/longitud (`ST_Y`, `ST_X`), disponibilidad y total de bicicletas por estación. La usan el mapa y los gráficos del dashboard.
- Columna generada `registro_energia.total_costo` (`STORED`): `cantidad_kwh * costo_kwh`. Nunca se inserta desde PHP.
- Restricciones `CHECK`: nivel de acceso 1–3, capacidad > 0, batería entre 0 y 100.
- Índices por estado, fecha, usuario, bicicleta y estación para las consultas de reportes.

Los datos de prueba se generan con `scripts/database/seed.sql` (500+ registros: 30 usuarios, 55 bicicletas, 123 alquileres, 113 transacciones, 30 mantenimientos y 140 registros de energía).

## 3. Autenticación y autorización

- Contraseñas con `password_hash(PASSWORD_BCRYPT, cost 12)`; verificación con `password_verify`.
- Sesión nombrada `EBIKEJARDIN`, cookie `HttpOnly`, `SameSite=Lax`, `Secure` si hay HTTPS.
- Tras 5 intentos fallidos se bloquea el inicio de sesión 60 segundos (contadores en sesión).
- Roles por `nivel_acceso`: 1 Administrador, 2 Operador, 3 Cliente.
  - `guest`: solo usuarios sin sesión (login, registro, home).
  - `auth`: cualquier usuario autenticado.
  - `staff`: Administrador u Operador.
  - `admin`: solo Administrador.
- Además de la ruta, los controladores verifican propiedad y reglas específicas:
  - Un cliente solo ve y finaliza sus propios alquileres (`Auth::ownsRental`).
  - El operador puede iniciar un alquiler a nombre de un cliente activo.
  - El administrador no puede cambiar su propio rol/estado ni eliminarse.
  - Un usuario con alquileres no puede eliminarse.

## 4. Seguridad

| Riesgo | Mitigación |
|---|---|
| Inyección SQL | PDO con sentencias preparadas y parámetros vinculados |
| XSS | Escape de salida con `htmlspecialchars` (`e()`) |
| CSRF | Token por sesión validado globalmente en cada POST (error 419) |
| Fijación de sesión | `session_regenerate_id(true)` al iniciar sesión |
| Fuerza bruta | Bloqueo temporal por intentos fallidos |
| Clickjacking | `X-Frame-Options: SAMEORIGIN` |
| MIME sniffing | `X-Content-Type-Options: nosniff` |
| Fuga de errores | `display_errors=0`, log en `storage/logs/app.log`, detalles solo con `APP_DEBUG=1` |
| Datos de pago | Solo tipo, alias, últimos 4 dígitos y hash SHA-256 de referencia |

## 5. Flujo de alquiler

### Inicio (`POST /alquileres/iniciar`)

1. Determina el cliente: el propio usuario o, si es staff, el cliente seleccionado (rol 3 y activo).
2. Bloquea la bicicleta con `SELECT … FOR UPDATE` dentro de una transacción.
3. Valida: estado `disponible`, estación asignada, batería ≥ 10 %, estación operativa, cliente sin alquiler activo y bicicleta sin alquiler activo.
4. Inserta el alquiler con `uuid_alquiler` (UUID v4), `fecha_inicio = NOW()`, tarifa `TARIFA_POR_MINUTO` y estado `activo`.
5. Cambia la bicicleta a `en_uso` y confirma la transacción.

### Finalización (`POST /alquileres/finalizar/{id}`)

1. Bloquea el alquiler con `FOR UPDATE` y verifica que esté activo y pertenezca al usuario (o que sea staff).
2. Calcula `minutos = max(1, ceil((time() − fecha_inicio) / 60))` y `costo = minutos × tarifa`.
3. Calcula el impacto: batería consumida (`min(45, max(2, minutos × 0.35))` %) y distancia (`minutos × 0.22` km).
4. Actualiza el alquiler: estado `completado`, estación destino, duración y costo.
5. Actualiza la bicicleta: `disponible`, estación destino, batería descontada (mínimo 0) y kilometraje sumado.
6. Inserta una transacción `aprobado` con referencia `EBJ-XXXXXXXX`; el método de pago es opcional (pago simulado si no se elige).
7. Todo ocurre en una transacción de base de datos con `rollback` ante cualquier error.

### Cancelación (`POST /alquileres/{id}/cancelar`, staff)

Solo alquileres activos. Pasa el alquiler a `cancelado` con costo 0 y devuelve la bicicleta a `disponible` en su estación de origen.

## 6. Módulos

### Dashboard

- **Staff**: ingresos del mes, alquileres activos, flota disponible, energía del mes, gráficas de 6 meses, estados de flota, disponibilidad por estación, consumo de 7 días, alquileres activos/recientes, alertas de batería y mantenimientos próximos.
- **Cliente**: alquiler activo con cronómetro, viajes, gasto, tiempo total, recomendaciones, mapa de estaciones, gráficas de viajes/gasto e historial.

### Bicicletas y estaciones

- CRUD con validación de unicidad (código QR, serie, código de estación).
- Eliminación protegida: no se puede borrar una bicicleta en uso o con alquileres; una estación con bicicletas asignadas.
- Batería entre 0 y 100; capacidad de estación mayor que cero; latitud (-90 a 90) y longitud (-180 a 180) validadas y convertidas a `POINT` con `ST_GeomFromText`.

### Mantenimientos

- Registro con tipo, técnico, fechas y costo.
- Sin fecha de fin → bicicleta en `mantenimiento`.
- Con fecha de fin → actualiza `ult_manten_date`, programa `prox_manten_date` (+90 días) y libera la bicicleta si no tiene otro mantenimiento abierto.
- No se permite registrar mantenimiento a bicicletas dadas de baja o en uso.

### Energía

- Registro por estación y tipo de operación; el total se calcula en MySQL.
- Filtros por estación, tipo y rango de fechas; gráfica de 6 meses y promedios.

### Pagos

- El cliente agrega métodos (tipo, alias, últimos 4 dígitos) y los desactiva sin borrar historial.
- El staff consulta todas las transacciones y su distribución por método.
- Reembolso: solo administrador, solo transacciones aprobadas; pasa a `reembolsado`.

### Reportes

- Rango de fechas con indicadores: ingresos aprobados, alquileres, duración promedio, clientes activos, costo de energía, costo de mantenimiento y margen operativo.
- Gráficas de 12 meses, top estaciones y clientes, resumen mensual.
- Exportación CSV con BOM UTF-8 y separador `;` para Excel en español. Tablas mediante `fputcsv` (sin concatenar cadenas).

### Mapa

- Leaflet + OpenStreetMap, estaciones desde `v_estaciones_mapa` y puntos de carga desde `public/data/electrolineras.json` (46 con coordenadas).
- Búsqueda, filtros por capa, autonomía estimada, geolocalización, estación más cercana y ruta por OSRM con enlace alternativo a Google Maps.

## 7. Frontend

- Bootstrap 5.3 y Bootstrap Icons; estilos propios en `public/assets/css/styles.css` (variables de marca, sidebar, tarjetas KPI, cronómetro, recibos, impresión).
- SweetAlert2: `data-confirm` en formularios y `data-text` para el detalle; toasts para mensajes flash.
- Chart.js: los datos se inyectan con `window.CHART_DATA` y se dibujan en `charts.js`.
- JavaScript propio sin frameworks: `app.js` (menú, contraseñas, confirmaciones), `map.js` (mapa) y scripts puntuales en las vistas (buscador de bicicletas, cronómetro, totales en vivo).

## 8. Manejo de errores

- `set_exception_handler` registra en `storage/logs/app.log` y, en producción, muestra una página genérica 500.
- `abort()` renderiza vistas de error 403/404/405/419/500 con diseño consistente.
- Los controladores capturan `PDOException` en operaciones de escritura para traducir errores de llave foránea/unicidad a mensajes comprensibles.

## 9. Pruebas realizadas

- Importación limpia de `schema.sql` + `seed.sql` y del archivo combinado `ebike_jardin.sql`.
- Inicio de sesión con los tres roles y verificación de menús y permisos (403 en rutas de admin para operador).
- CRUD de usuarios, estaciones y bicicletas, incluidas validaciones y protecciones de borrado.
- Ciclo de mantenimiento: alta abierta, cierre, reprogramación y liberación de la bicicleta.
- Alquiler completo: inicio, cronómetro, finalización, cálculo de costo, actualización de batería/kilometraje, transacción y recibo.
- Cancelación de alquiler y liberación de la bicicleta.
- Registro de energía con total generado por MySQL.
- Alta/baja de métodos de pago y reembolso de transacción aprobada.
- Exportaciones CSV de alquileres, inventario y energía.
- CSRF: POST sin token responde 419; rutas inexistentes responden 404.
- Navegación completa en navegador con SweetAlert2 en confirmaciones y mapas/charts renderizados.

## 10. Despliegue

- Apache: copiar el proyecto en `htdocs`, habilitar `mod_rewrite` y apuntar el navegador a `/ebike-jardin/public/`. El `.htaccess` redirige a `index.php`.
- Servidor embebido: `php -S 127.0.0.1:8080 -t public public/router.php`.
- Configuración por variables de entorno: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, `APP_BASE_URL`, `APP_DEBUG`.
- Para producción: `APP_DEBUG=0`, credenciales de BD con permisos mínimos y HTTPS para activar la cookie `Secure`.
