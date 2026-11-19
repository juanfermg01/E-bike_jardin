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
