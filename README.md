# E-Bike Jardín

Sistema web de alquiler de bicicletas eléctricas para el municipio de Jardín, Antioquia.
Proyecto académico SENA desarrollado en **PHP 8 + MySQL 8 (compatible con MariaDB 10.4) + Bootstrap 5.3**, con arquitectura MVC propia (sin Composer ni frameworks).

## Funcionalidades

- Autenticación con sesiones, contraseñas bcrypt (costo 12), CSRF global y bloqueo por intentos fallidos.
- Tres roles: **Administrador** (nivel 1), **Operador** (nivel 2) y **Cliente** (nivel 3), con menús y rutas protegidas por middleware.
- CRUD de usuarios, estaciones y bicicletas con reglas de negocio y llaves foráneas.
- Flujo completo de alquiler: selección de bicicleta, cronómetro en vivo, finalización con cálculo automático por minuto, consumo de batería, kilometraje y generación de la transacción.
- Registro de mantenimientos que sincroniza el estado de la bicicleta y las fechas de próximo mantenimiento.
- Monitoreo energético por estación con costo calculado mediante columna generada en MySQL.
- Pagos: métodos del cliente (sin almacenar datos sensibles), filtros, reembolsos solo para administradores.
- Reportes con indicadores, gráficas de 12 meses y exportación CSV (alquileres, inventario, energía).
- Mapa Leaflet con las 6 estaciones del proyecto y 46 puntos de carga del dataset original, búsqueda, geolocalización y ruta.
- Recibos imprimibles y códigos QR por bicicleta.
- SweetAlert2 en confirmaciones y notificaciones; Chart.js en los dashboards.

## Requisitos

| Componente | Versión usada |
|---|---|
| PHP | 8.2 (extensiones `pdo_mysql`, `mbstring`, `json`) |
| MySQL / MariaDB | MySQL 8 o MariaDB 10.4+ |
| Servidor | Apache de XAMPP o servidor embebido de PHP |
| Navegador | Chrome, Edge o Firefox actualizado |

## Instalación rápida (XAMPP)

1. Copia la carpeta `ebike-jardin` dentro de `C:\xampp\htdocs\`.
2. Inicia **Apache** y **MySQL** desde el panel de XAMPP.
3. Importa la base de datos (crea la BD, las 9 tablas, la vista y los 500+ registros de prueba):

   ```bash
   C:\xampp\mysql\bin\mysql.exe -u root < scripts\database\ebike_jardin.sql
   ```

   También puedes importar `scripts/database/ebike_jardin.sql` desde phpMyAdmin.
4. Abre `http://localhost/ebike-jardin/public/`.

> Si tu MySQL tiene contraseña, defínela en `app/config/config.php` (`DB_PASS`) o con la variable de entorno `DB_PASS`. También puedes ajustar `DB_HOST`, `DB_PORT`, `DB_NAME` y `DB_USER`.

### Servidor embebido (sin Apache)

```bash
cd ebike-jardin
C:\xampp\php\php.exe -S 127.0.0.1:8080 -t public public/router.php
```

Luego abre `http://127.0.0.1:8080`.

## Cuentas de demostración

| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | `admin@ebikejardin.com` | `Admin123*` |
| Operador | `operador@ebikejardin.com` | `Operador123*` |
| Cliente | `cliente@ebikejardin.com` | `Cliente123*` |
| Clientes de prueba (4–30) | `mariana1@correo.com`, `andres2@correo.com`, … | `Demo123*` |

## Estructura del proyecto

```
ebike-jardin/
├── app/
│   ├── config/        config.php (BD, tarifa, zona horaria) y routes.php
│   ├── controllers/   13 controladores (auth, dashboard, CRUDs, alquileres, reportes…)
│   ├── core/          Database (PDO singleton) y Router (regex + middleware)
│   ├── helpers/       Auth, Validator y functions.php (vistas, CSRF, formato, paginación)
│   └── views/         layouts, auth, dashboard, CRUDs, rentals, map, reports…
├── public/            único punto de entrada (index.php), assets y datos del mapa
│   ├── assets/css     styles.css
│   ├── assets/js      app.js (SweetAlert), charts.js, map.js
│   └── data/          electrolineras.json (dataset de puntos de carga)
├── scripts/database/  schema.sql, seed.sql y ebike_jardin.sql (importación única)
├── storage/           logs de la aplicación (storage/logs/app.log)
├── docs/              documentación técnica y manual de usuario
└── README.md
```

## Base de datos

Las tablas se crean en el mismo orden del diagrama entidad-relación del proyecto:

1. `roles`
2. `usuarios`
3. `metodos_pago`
4. `estaciones`
5. `bicicletas`
6. `alquileres`
7. `transacciones`
8. `mantenimientos`
9. `registro_energia`

Además incluye la vista `v_estaciones_mapa` (estaciones con coordenadas y disponibilidad) y la columna generada `registro_energia.total_costo = cantidad_kwh * costo_kwh`.

Todas las relaciones usan llaves foráneas con `ON UPDATE CASCADE` y `ON DELETE RESTRICT`, salvo `metodos_pago.id_usuario`, que usa `ON DELETE CASCADE` por ser un dato propio de la cuenta.

## Reglas de negocio principales

- Tarifa única de **$150 COP por minuto** (`TARIFA_POR_MINUTO`).
- Solo se alquilan bicicletas `disponible`, con estación asignada, batería ≥ 10 % y sin otro alquiler activo.
- Al finalizar: minutos = `max(1, ceil((fin − inicio) / 60))`, costo = minutos × tarifa; se descuenta batería, se suma kilometraje y se registra una transacción aprobada.
- Un cliente no puede tener dos alquileres activos; el operador sí puede iniciar alquileres a nombre de un cliente activo.
- Cancelar un alquiler activo libera la bicicleta sin generar cobro.
- Un mantenimiento sin fecha de fin deja la bicicleta en `mantenimiento`; al cerrarlo vuelve a `disponible` y programa el próximo mantenimiento a 90 días.
- Los reembolsos solo los ejecuta un administrador y únicamente sobre transacciones aprobadas.
- Un usuario con alquileres no puede eliminarse (se conserva la trazabilidad); tampoco puede eliminarse a sí mismo el administrador.

## Rutas principales

| Ruta | Descripción | Acceso |
|---|---|---|
| `/login`, `/registro` | Autenticación | Público |
| `/dashboard` | Panel según rol | Autenticado |
| `/mapa` | Mapa de estaciones y puntos de carga | Autenticado |
| `/alquileres`, `/alquileres/iniciar` | Historial e inicio de alquiler | Autenticado |
| `/alquileres/finalizar/{id}` | Cierre y pago del recorrido | Autenticado |
| `/bicicletas`, `/estaciones` | Inventario y estaciones | Autenticado (crear/editar: staff; eliminar: admin) |
| `/mantenimientos`, `/energia` | Operación técnica | Staff |
| `/usuarios` | Gestión de cuentas | Admin |
| `/pagos` | Métodos y transacciones | Autenticado (reembolso: admin) |
| `/reportes` | Indicadores y exportación CSV | Staff |

## Seguridad implementada

- Contraseñas con `password_hash()` bcrypt costo 12 y verificación con `password_verify()`.
- Token CSRF por sesión validado en **todas** las peticiones POST; error 419 si no coincide.
- Consultas con sentencias preparadas y `ATTR_EMULATE_PREPARES = false`.
- Escape de salida con `e()` (`htmlspecialchars`) en las vistas.
- Cookies de sesión `HttpOnly`, `SameSite=Lax` y `Secure` bajo HTTPS; cabeceras `X-Frame-Options`, `X-Content-Type-Options` y `Referrer-Policy`.
- Middleware de rol (`auth`, `guest`, `staff`, `admin`) y verificación de propiedad del alquiler.
- Bloqueo temporal tras 5 intentos fallidos de inicio de sesión.
- Métodos de pago guardados solo como tipo, alias, últimos 4 dígitos y hash de referencia (nunca el número completo).
- Errores registrados en `storage/logs/app.log`; los detalles solo se muestran con `APP_DEBUG=1`.

## Exportaciones

En **Reportes → Exportar CSV** se descargan archivos separados por `;` con BOM UTF-8 (compatibles con Excel en español):

- `alquileres`: recorridos del rango seleccionado.
- `inventario`: estado actual de la flota.
- `energia`: consumo eléctrico del rango.

## Documentación

- [Documentación técnica](docs/DOCUMENTACION_TECNICA.md)
- [Manual de usuario](docs/MANUAL_DE_USUARIO.md)
- Guía de estudio y sustentación: entregada aparte, fuera de la carpeta del proyecto.

## Notas

- Zona horaria: `America/Bogota`.
- Moneda: COP.
- El mapa usa OpenStreetMap/Leaflet y el servicio público OSRM para rutas; si no hay internet, las estaciones siguen visibles y se ofrece abrir la ruta en Google Maps.
- Los datos de prueba se pueden restaurar en cualquier momento volviendo a importar `scripts/database/ebike_jardin.sql`.
