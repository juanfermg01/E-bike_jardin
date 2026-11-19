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
