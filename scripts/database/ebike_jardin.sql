-- =====================================================================
-- E-BIKE JARDÍN · Importación completa (estructura + datos de prueba)
-- Generado: 2026-11-05
-- Uso: mysql -u root -p < ebike_jardin.sql
-- =====================================================================

-- =====================================================================
-- E-BIKE JARDÍN - Sistema de Gestión de Alquiler de Bicicletas Eléctricas
-- Base de datos: ebike_jardin
-- Motor: InnoDB | Charset: utf8mb4 | MySQL 8 / MariaDB 10.4+
-- Orden de tablas según el diagrama Entidad-Relación del proyecto.
-- =====================================================================

DROP DATABASE IF EXISTS ebike_jardin;
CREATE DATABASE ebike_jardin
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE ebike_jardin;

-- ---------------------------------------------------------------------
-- 1. ROLES
-- ---------------------------------------------------------------------
CREATE TABLE roles (
    id_rol          INT AUTO_INCREMENT,
    codigo_rol      VARCHAR(20)  NOT NULL,
    nombre_rol      VARCHAR(50)  NOT NULL,
    permisos        JSON         NULL,
    nivel_acceso    INT          NOT NULL DEFAULT 3,
    fecha_creacion  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_roles PRIMARY KEY (id_rol),
    CONSTRAINT uq_roles_codigo UNIQUE (codigo_rol),
    CONSTRAINT ck_roles_nivel CHECK (nivel_acceso BETWEEN 1 AND 3)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. USUARIOS
-- ---------------------------------------------------------------------
CREATE TABLE usuarios (
    id_usuario      INT AUTO_INCREMENT,
    rol_id          INT          NOT NULL,
    uuid            CHAR(36)     NOT NULL,
    nombre          VARCHAR(100) NOT NULL,
    email           VARCHAR(150) NOT NULL,
    password_hash   VARCHAR(255) NOT NULL,
    telefono        VARCHAR(20)  NULL,
    estado          ENUM('activo','inactivo','suspendido') NOT NULL DEFAULT 'activo',
    fecha_registro  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_usuarios PRIMARY KEY (id_usuario),
    CONSTRAINT uq_usuarios_uuid UNIQUE (uuid),
    CONSTRAINT uq_usuarios_email UNIQUE (email),
    CONSTRAINT fk_usuarios_roles FOREIGN KEY (rol_id)
        REFERENCES roles (id_rol)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_usuarios_rol_estado (rol_id, estado),
    INDEX idx_usuarios_fecha (fecha_registro)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3. METODOS_PAGO
-- ---------------------------------------------------------------------
CREATE TABLE metodos_pago (
    id_metodo        INT AUTO_INCREMENT,
    id_usuario       INT          NOT NULL,
    tipo             ENUM('tarjeta_credito','tarjeta_debito','transferencia','billetera_digital') NOT NULL,
    alias            VARCHAR(50)  NULL,
    ultimos_digitos  CHAR(4)      NULL,
    token_hash       VARCHAR(255) NOT NULL,
    activo           BOOLEAN      NOT NULL DEFAULT TRUE,
    fecha_creacion   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_metodos_pago PRIMARY KEY (id_metodo),
    CONSTRAINT fk_metodos_pago_usuarios FOREIGN KEY (id_usuario)
        REFERENCES usuarios (id_usuario)
        ON UPDATE CASCADE ON DELETE CASCADE,
    INDEX idx_metodos_usuario (id_usuario, activo)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4. ESTACIONES
-- ---------------------------------------------------------------------
CREATE TABLE estaciones (
    id_estacion   INT AUTO_INCREMENT,
    codigo        VARCHAR(20)   NOT NULL,
    nombre        VARCHAR(100)  NOT NULL,
    direccion     VARCHAR(255)  NULL,
    coordenadas   POINT         NOT NULL,
    capacidad     INT           NOT NULL,
    energia_disp  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    estado        ENUM('operativa','mantenimiento','inactiva') NOT NULL DEFAULT 'operativa',
    fecha_creacion TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_estaciones PRIMARY KEY (id_estacion),
    CONSTRAINT uq_estaciones_codigo UNIQUE (codigo),
    CONSTRAINT ck_estaciones_capacidad CHECK (capacidad > 0),
    INDEX idx_estaciones_estado (estado)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5. BICICLETAS
-- ---------------------------------------------------------------------
CREATE TABLE bicicletas (
    id_bicicleta     INT AUTO_INCREMENT,
    estacion_act_fk  INT           NULL,
    codigo_qr        VARCHAR(50)   NOT NULL,
    modelo           VARCHAR(100)  NOT NULL,
    num_serie        VARCHAR(100)  NOT NULL,
    nivel_bateria    DECIMAL(5,2)  NOT NULL DEFAULT 100.00,
    estado           ENUM('disponible','en_uso','mantenimiento','baja') NOT NULL DEFAULT 'disponible',
    kilometraje      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    ult_manten_date  DATE          NULL,
    prox_manten_date DATE          NULL,
    CONSTRAINT pk_bicicletas PRIMARY KEY (id_bicicleta),
    CONSTRAINT uq_bicicletas_qr UNIQUE (codigo_qr),
    CONSTRAINT uq_bicicletas_serie UNIQUE (num_serie),
    CONSTRAINT ck_bicicletas_bateria CHECK (nivel_bateria BETWEEN 0 AND 100),
    CONSTRAINT fk_bicicletas_estaciones FOREIGN KEY (estacion_act_fk)
        REFERENCES estaciones (id_estacion)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_bicicletas_estado_estacion (estado, estacion_act_fk),
    INDEX idx_bicicletas_mantenimiento (prox_manten_date)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 6. ALQUILERES
-- ---------------------------------------------------------------------
CREATE TABLE alquileres (
    id_alquiler          INT AUTO_INCREMENT,
    id_usuario           INT           NOT NULL,
    id_bicicleta         INT           NOT NULL,
    estacion_orig_fk     INT           NOT NULL,
    estacion_dest_fk     INT           NULL,
    uuid_alquiler        CHAR(36)      NOT NULL,
    fecha_inicio         DATETIME      NOT NULL,
    fecha_fin            DATETIME      NULL,
    duracion_minutos     INT           NULL,
    tarifa_por_minuto    DECIMAL(10,2) NOT NULL DEFAULT 150.00,
    costo_total          DECIMAL(10,2) NULL,
    estado               ENUM('activo','completado','cancelado','incidencia') NOT NULL DEFAULT 'activo',
    CONSTRAINT pk_alquileres PRIMARY KEY (id_alquiler),
    CONSTRAINT uq_alquileres_uuid UNIQUE (uuid_alquiler),
    CONSTRAINT fk_alquileres_usuarios FOREIGN KEY (id_usuario)
        REFERENCES usuarios (id_usuario)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_alquileres_bicicletas FOREIGN KEY (id_bicicleta)
        REFERENCES bicicletas (id_bicicleta)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_alquileres_estacion_orig FOREIGN KEY (estacion_orig_fk)
        REFERENCES estaciones (id_estacion)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_alquileres_estacion_dest FOREIGN KEY (estacion_dest_fk)
        REFERENCES estaciones (id_estacion)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_alquileres_usuario_estado (id_usuario, estado),
    INDEX idx_alquileres_bici_estado (id_bicicleta, estado),
    INDEX idx_alquileres_fecha (fecha_inicio)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 7. TRANSACCIONES
-- ---------------------------------------------------------------------
CREATE TABLE transacciones (
    id_transaccion     INT AUTO_INCREMENT,
    id_alquiler_fk     INT           NOT NULL,
    id_metodo_pago_fk  INT           NULL,
    monto              DECIMAL(10,2) NOT NULL,
    estado_pago        ENUM('pendiente','aprobado','rechazado','reembolsado') NOT NULL DEFAULT 'pendiente',
    referencia_ext     VARCHAR(100)  NULL,
    fecha_proceso      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_transacciones PRIMARY KEY (id_transaccion),
    CONSTRAINT fk_transacciones_alquileres FOREIGN KEY (id_alquiler_fk)
        REFERENCES alquileres (id_alquiler)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_transacciones_metodos FOREIGN KEY (id_metodo_pago_fk)
        REFERENCES metodos_pago (id_metodo)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_transacciones_estado (estado_pago, fecha_proceso)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 8. MANTENIMIENTOS
-- ---------------------------------------------------------------------
CREATE TABLE mantenimientos (
    id_manten       INT AUTO_INCREMENT,
    id_bicicleta_fk INT           NOT NULL,
    tipo_manten     VARCHAR(100)  NOT NULL,
    descripcion     TEXT          NULL,
    tecnico         VARCHAR(150)  NULL,
    fecha_inicio    DATETIME      NOT NULL,
    fecha_fin       DATETIME      NULL,
    costo           DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    CONSTRAINT pk_mantenimientos PRIMARY KEY (id_manten),
    CONSTRAINT fk_mantenimientos_bicicletas FOREIGN KEY (id_bicicleta_fk)
        REFERENCES bicicletas (id_bicicleta)
        ON UPDATE CASCADE ON DELETE CASCADE,
    INDEX idx_mantenimientos_bici (id_bicicleta_fk, fecha_inicio)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 9. REGISTRO_ENERGIA
-- ---------------------------------------------------------------------
CREATE TABLE registro_energia (
    id_registro     INT AUTO_INCREMENT,
    id_estacion_fk  INT           NOT NULL,
    tipo_operac     VARCHAR(50)   NOT NULL,
    cantidad_kwh    DECIMAL(10,2) NOT NULL,
    costo_kwh       DECIMAL(10,4) NOT NULL,
    total_costo     DECIMAL(12,2) AS (cantidad_kwh * costo_kwh) STORED,
    fecha_registro  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    descripcion     TEXT          NULL,
    CONSTRAINT pk_registro_energia PRIMARY KEY (id_registro),
    CONSTRAINT fk_registro_energia_estaciones FOREIGN KEY (id_estacion_fk)
        REFERENCES estaciones (id_estacion)
        ON UPDATE CASCADE ON DELETE CASCADE,
    INDEX idx_energia_estacion_fecha (id_estacion_fk, fecha_registro)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- VISTA para el mapa: estaciones con coordenadas legibles y ocupación
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW v_estaciones_mapa AS
SELECT
    e.id_estacion,
    e.codigo,
    e.nombre,
    e.direccion,
    e.capacidad,
    e.energia_disp,
    e.estado,
    ST_Y(e.coordenadas) AS latitud,
    ST_X(e.coordenadas) AS longitud,
    (SELECT COUNT(*) FROM bicicletas b
      WHERE b.estacion_act_fk = e.id_estacion AND b.estado = 'disponible') AS bicis_disponibles,
    (SELECT COUNT(*) FROM bicicletas b
      WHERE b.estacion_act_fk = e.id_estacion) AS bicis_totales
FROM estaciones e;


-- =====================================================================
-- E-BIKE JARDÍN - Datos de prueba (500+ registros)
-- Cuentas demo:
--   admin@ebikejardin.com     / Admin123*
--   operador@ebikejardin.com  / Operador123*
--   cliente@ebikejardin.com   / Cliente123*
--   clientes 4..30           / Demo123*
-- =====================================================================

USE ebike_jardin;
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

INSERT INTO roles (id_rol, codigo_rol, nombre_rol, permisos, nivel_acceso) VALUES
(1, 'ADMIN', 'Administrador', '["gestionar_usuarios","gestionar_bicicletas","gestionar_estaciones","gestionar_alquileres","ver_reportes"]', 1),
(2, 'OPERADOR', 'Operador', '["gestionar_bicicletas","gestionar_estaciones","gestionar_alquileres"]', 2),
(3, 'CLIENTE', 'Cliente', '["alquilar","ver_historial","gestionar_pagos"]', 3);

INSERT INTO usuarios (id_usuario, rol_id, uuid, nombre, email, password_hash, telefono, estado) VALUES
(1, 1, 'ebcf47d8-aed8-4933-93a9-1e5a8adb9d51', 'Administrador E-Bike Jardín', 'admin@ebikejardin.com', '$2y$12$oYfT1f7oq8J.zOxu2Wn8DuyCsdCn.nPpUfS.cwTQ26rwrgal9cv6q', '3001234567', 'activo'),
(2, 2, '7bdd85ca-db33-4283-a740-fd3ae826f7db', 'Laura Gómez (Operadora)', 'operador@ebikejardin.com', '$2y$12$IEVtVbSLaXLpcSpNI9ngc.z6uozOo84JFK4CIy/li99JDis7syYRS', '3002345678', 'activo'),
(3, 3, '51f490fd-5e99-453f-8474-57af601c5c64', 'Carlos Restrepo', 'cliente@ebikejardin.com', '$2y$12$fhxByqqgv7FEP4LAfzC.dutM1zHngjjEsqHysi/18akXoKwrlXgm.', '3003456789', 'activo'),
(4, 3, 'b5fd6742-de64-41b0-8269-c52f0625a25f', 'Mariana Pérez', 'mariana1@correo.com', '$2y$12$8/Y92zbcYmZnRhIHXeaqE.x3mnPVvIx5keggrfdzJjuwckJK0S0iW', '3112972517', 'activo'),
(5, 3, '1904a3b9-355c-4aa8-8c1e-fb2ff7644bde', 'Andrés Rodríguez', 'andres2@correo.com', '$2y$12$8/Y92zbcYmZnRhIHXeaqE.x3mnPVvIx5keggrfdzJjuwckJK0S0iW', '3177601410', 'activo'),
(6, 3, 'fd6ad030-397a-4d00-81ea-a724e471a7fe', 'Sofía Martínez', 'sofia3@correo.com', '$2y$12$8/Y92zbcYmZnRhIHXeaqE.x3mnPVvIx5keggrfdzJjuwckJK0S0iW', '3166004955', 'activo'),
(7, 3, '40827fed-a19b-407c-aaba-da90c88fb337', 'Juliana López', 'juliana4@correo.com', '$2y$12$8/Y92zbcYmZnRhIHXeaqE.x3mnPVvIx5keggrfdzJjuwckJK0S0iW', '3131813791', 'activo'),
(8, 3, 'f302ff9a-ea39-43ab-aa00-116d6f7aabe3', 'Felipe García', 'felipe5@correo.com', '$2y$12$8/Y92zbcYmZnRhIHXeaqE.x3mnPVvIx5keggrfdzJjuwckJK0S0iW', '3101931799', 'activo'),
(9, 3, '064e9c4f-77cf-464b-9392-672b1f91a763', 'Valentina Hernández', 'valentina6@correo.com', '$2y$12$8/Y92zbcYmZnRhIHXeaqE.x3mnPVvIx5keggrfdzJjuwckJK0S0iW', '3152294039', 'inactivo'),
(10, 3, '230dd5d2-b496-45b3-9643-58a07cfcbcb1', 'Sebastián Ramírez', 'sebastian7@correo.com', '$2y$12$8/Y92zbcYmZnRhIHXeaqE.x3mnPVvIx5keggrfdzJjuwckJK0S0iW', '3118525734', 'activo'),
(11, 3, 'da300f3a-fa5b-4303-ac44-cbd1165e5ca3', 'Camila Torres', 'camila8@correo.com', '$2y$12$8/Y92zbcYmZnRhIHXeaqE.x3mnPVvIx5keggrfdzJjuwckJK0S0iW', '3128026116', 'activo'),
(12, 3, 'b6a16fb0-4183-4215-b12f-d8b3629d4795', 'Daniel Flórez', 'daniel9@correo.com', '$2y$12$8/Y92zbcYmZnRhIHXeaqE.x3mnPVvIx5keggrfdzJjuwckJK0S0iW', '3166857703', 'activo'),
(13, 3, 'd1f3efc3-bda3-4b01-b2e3-8fef9010df2d', 'Isabella Vargas', 'isabella10@correo.com', '$2y$12$8/Y92zbcYmZnRhIHXeaqE.x3mnPVvIx5keggrfdzJjuwckJK0S0iW', '3146700866', 'activo'),
(14, 3, 'a303abdf-1134-4fc1-8cbf-db2f0b865b04', 'Santiago Castro', 'santiago11@correo.com', '$2y$12$8/Y92zbcYmZnRhIHXeaqE.x3mnPVvIx5keggrfdzJjuwckJK0S0iW', '3176622165', 'activo'),
(15, 3, '712cf4fd-6bc2-4998-a0a6-91202150a1c1', 'Valeria Ospina', 'valeria12@correo.com', '$2y$12$8/Y92zbcYmZnRhIHXeaqE.x3mnPVvIx5keggrfdzJjuwckJK0S0iW', '3154325611', 'activo'),
(16, 3, 'c3a2a7a7-9a00-4297-90eb-bf7306a08af5', 'Mateo Ríos', 'mateo13@correo.com', '$2y$12$8/Y92zbcYmZnRhIHXeaqE.x3mnPVvIx5keggrfdzJjuwckJK0S0iW', '3183217887', 'activo'),
(17, 3, 'b6ac9760-622e-4a2b-b531-0c83a4bb7f40', 'Lucía Arango', 'lucia14@correo.com', '$2y$12$8/Y92zbcYmZnRhIHXeaqE.x3mnPVvIx5keggrfdzJjuwckJK0S0iW', '3141129426', 'activo'),
(18, 3, 'b956e796-9d53-469f-b662-ac4c440c0b95', 'Nicolás Mejía', 'nicolas15@correo.com', '$2y$12$8/Y92zbcYmZnRhIHXeaqE.x3mnPVvIx5keggrfdzJjuwckJK0S0iW', '3182974648', 'activo'),
(19, 3, 'bc372423-42bd-480a-8531-b5d19c5294af', 'Paula Zapata', 'paula16@correo.com', '$2y$12$8/Y92zbcYmZnRhIHXeaqE.x3mnPVvIx5keggrfdzJjuwckJK0S0iW', '3185850033', 'activo'),
(20, 3, '90b62570-5b20-4349-b7a1-887154966f82', 'Juan José Cárdenas', 'juanjose17@correo.com', '$2y$12$8/Y92zbcYmZnRhIHXeaqE.x3mnPVvIx5keggrfdzJjuwckJK0S0iW', '3139977007', 'activo'),
(21, 3, '33abd33f-e28b-42cf-ab3a-d900ca47a010', 'Sara Vélez', 'sara18@correo.com', '$2y$12$8/Y92zbcYmZnRhIHXeaqE.x3mnPVvIx5keggrfdzJjuwckJK0S0iW', '3134727883', 'inactivo'),
(22, 3, 'bed1be39-d498-4c22-823a-2ce05c2e722b', 'Alejandro Montoya', 'alejandro19@correo.com', '$2y$12$8/Y92zbcYmZnRhIHXeaqE.x3mnPVvIx5keggrfdzJjuwckJK0S0iW', '3168363749', 'activo'),
(23, 3, 'a53d4848-cac6-4d10-9134-9bd3cb739f4c', 'Manuela Giraldo', 'manuela20@correo.com', '$2y$12$8/Y92zbcYmZnRhIHXeaqE.x3mnPVvIx5keggrfdzJjuwckJK0S0iW', '3113727954', 'activo'),
(24, 3, 'e55052ef-b721-44a9-b80b-a587dd3f32b6', 'Diego Serna', 'diego21@correo.com', '$2y$12$8/Y92zbcYmZnRhIHXeaqE.x3mnPVvIx5keggrfdzJjuwckJK0S0iW', '3196346438', 'activo'),
(25, 3, '678547a3-9348-4c5b-8503-acd539f7ae56', 'Daniela Quintero', 'daniela22@correo.com', '$2y$12$8/Y92zbcYmZnRhIHXeaqE.x3mnPVvIx5keggrfdzJjuwckJK0S0iW', '3176860039', 'activo'),
(26, 3, '132abccf-77ea-47bb-92dc-cf86631f8d08', 'Samuel Arias', 'samuel23@correo.com', '$2y$12$8/Y92zbcYmZnRhIHXeaqE.x3mnPVvIx5keggrfdzJjuwckJK0S0iW', '3186567150', 'activo'),
(27, 3, '3d3a0d79-48ee-4411-9f8f-ae59df5b2f06', 'Salomé Bedoya', 'salome24@correo.com', '$2y$12$8/Y92zbcYmZnRhIHXeaqE.x3mnPVvIx5keggrfdzJjuwckJK0S0iW', '3133102797', 'activo'),
(28, 3, '099e6f4f-7f1b-46f8-bc09-f45b99b6c34f', 'Emmanuel Correa', 'emmanuel25@correo.com', '$2y$12$8/Y92zbcYmZnRhIHXeaqE.x3mnPVvIx5keggrfdzJjuwckJK0S0iW', '3123024761', 'activo'),
(29, 3, 'f5d77485-05e9-425d-a695-10ec323d31a5', 'Antonia Uribe', 'antonia26@correo.com', '$2y$12$8/Y92zbcYmZnRhIHXeaqE.x3mnPVvIx5keggrfdzJjuwckJK0S0iW', '3101292017', 'activo'),
(30, 3, 'ba6b54c7-0c73-4642-b11f-ced295dc927d', 'Tomás Hoyos', 'tomas27@correo.com', '$2y$12$8/Y92zbcYmZnRhIHXeaqE.x3mnPVvIx5keggrfdzJjuwckJK0S0iW', '3166117892', 'activo');

INSERT INTO metodos_pago (id_metodo, id_usuario, tipo, alias, ultimos_digitos, token_hash, activo) VALUES
(1, 2, 'tarjeta_credito', 'Trabajo 3007', '3007', 'be34884ae545fc032c7ff145170a37afbec48de77323b2cbbde991132268486d', 1),
(2, 3, 'tarjeta_credito', 'Mastercard 5780', '5780', 'f2018f4caafa815442550f31b6c8c1c808fe4a5aa81ff0f2a735e90a5564abd3', 1),
(3, 4, 'tarjeta_debito', 'Personal 6677', '6677', '48333376f2987452ce25523d554249c1323c4109163b9c78c4052d2757a55880', 1),
(4, 5, 'tarjeta_credito', 'Daviplata 9574', '9574', 'fd783ea3644a859368e1794b6dd01bc392b5b6c113f0a0e8dc337ea9e877282f', 0),
(5, 5, 'tarjeta_debito', 'Visa 7851', '7851', 'a6ba124868b3b8d287a3d7e98235fc3f9cd2d0fafb4cef569f339c7821060ed3', 1),
(6, 6, 'transferencia', 'Daviplata 9847', '9847', '1f555cabb29b5babce3ded064bbf23c8399e05e9cc89a198cf83232035644b69', 1),
(7, 7, 'tarjeta_credito', 'Ahorros 9242', '9242', '896b448e3dc9468ca3d4bc896eb4dd9a71acf70ed482891a19afde9cd3678706', 1),
(8, 8, 'tarjeta_debito', 'Daviplata 7293', '7293', '48e4ee7a7b0a6033399433849880b5e0681aa4585698b76a7cd8fd91d25c31ac', 1),
(9, 9, 'billetera_digital', 'Nequi 3767', '3767', '2ba4de6d119dc7895da30887517cea4d4be981afeb9d571f324103ba8e2c9632', 1),
(10, 10, 'tarjeta_debito', 'Personal 4160', '4160', '29881a7db8bb01e37721e605dfb6319444f9f2be68fe683a20f535e78eb5f516', 1),
(11, 10, 'tarjeta_debito', 'Ahorros 5735', '5735', 'e134a866948999c62080c30ad2c0c2e1173ba0d57af6e55ed8f8263fef72e36b', 1),
(12, 11, 'tarjeta_debito', 'Nequi 8095', '8095', '1374844344e7cbbead72b3af8dddfe77264493ffe3a75dc6f12e7ee40a5155dc', 1),
(13, 12, 'tarjeta_debito', 'Mastercard 4389', '4389', '11d71803474f0d114fdd51110a7255de2c83a5a8a3584e10915ebe8744fd37cf', 1),
(14, 13, 'billetera_digital', 'Ahorros 3710', '3710', '2d7a122ae290c441a819bdab38c166fc9af7f02201c1205d0c61f4392d717074', 1),
(15, 14, 'tarjeta_credito', 'Ahorros 4779', '4779', '539a6dffd301ac22503c45791f37581d6abeea8e540f4441cca4d62576c0f835', 1),
(16, 15, 'transferencia', 'Trabajo 8903', '8903', 'b3d132b7d5804277328781a8ddd20d9f64d454bd1b331f616d5ed8eaa1de5be4', 1),
(17, 15, 'transferencia', 'Daviplata 3766', '3766', 'f8503971424547416c96eee282a29dd5e625a72664305d017c400c6ec936f209', 1),
(18, 16, 'tarjeta_credito', 'Principal 5194', '5194', 'c070d89f33fa7881f2bdfec235543a4010ce6369609f4e5387f6e7b4309b25b1', 1),
(19, 17, 'billetera_digital', 'Personal 8521', '8521', 'ca7c2d863087df33846f5f1e8ee0896581e2ca18715837d4c1c64df45a9acef0', 1),
(20, 18, 'tarjeta_debito', 'Principal 6804', '6804', 'aa9cd08ad1d2fce067b3b8bcc97e6b229027d19516ff63340fbee510578b34db', 0),
(21, 19, 'transferencia', 'Personal 6349', '6349', 'fc7abc333a2b3bad09abf70b8c76b9b48feeb67d80a781885877e51d9453bb14', 1),
(22, 20, 'billetera_digital', 'Mastercard 8126', '8126', 'f4b63b84ccffc70fc24a0186efba413a23f3b0ee0f8eedf2c7adb21c2fe944ac', 1),
(23, 20, 'transferencia', 'Principal 5756', '5756', '56030958f2d84535e893e94c41af888790ae3e8d32381b56fe840ca5ec068cae', 1),
(24, 21, 'tarjeta_debito', 'Trabajo 1969', '1969', '34d75639a5cdff200f0f36f050ed7404d803b970a641aa4d82792f02b090629f', 0),
(25, 22, 'billetera_digital', 'Principal 1608', '1608', 'd0f80237344902f4cafa521ee456e113c124badc1c350698a171d8ca136ae0b8', 1),
(26, 23, 'billetera_digital', 'Principal 5961', '5961', 'aed7ef2d22875b80639299e3e4daf5110a7eacfbeb7c6e08f31a6078b0ce088a', 0),
(27, 24, 'tarjeta_credito', 'Daviplata 1594', '1594', '1ddfdc68e77a91cad205ac8dfc5605aaac90ae3782d19a5317e9ea758eba5285', 1),
(28, 25, 'billetera_digital', 'Personal 3601', '3601', 'd681b4a4c25f6f580aa8fc0a06484ff6febf392b666e47bc9e746154cb124ecd', 0),
(29, 25, 'billetera_digital', 'Ahorros 6117', '6117', '1354d0658e5930956af2d5b35d0e8ab20b837aaccf3bb3e491be6cb55af966f1', 0),
(30, 26, 'transferencia', 'Principal 5222', '5222', '0d2530fcb5463d4413cf67589b478d665c2e020ba7fe15ae83281450c65acff6', 1),
(31, 27, 'tarjeta_debito', 'Principal 3953', '3953', '8f0826ada352c5da7e8b397efad6c8ed9fd9121ccb27e8e3825f2b73fd4c3689', 1),
(32, 28, 'transferencia', 'Daviplata 4270', '4270', 'c41486d7c619d7ebd2df73fe513f906d2508c65386cb9e9cb6da07f0fdda8a72', 1),
(33, 29, 'tarjeta_credito', 'Personal 4351', '4351', 'cd64d9c01927c5dbb9f116fab0ec472c4c6207d15205102eb024d0facbdc406e', 1),
(34, 30, 'transferencia', 'Ahorros 8571', '8571', '8e204abd53b1c05701547bdcefaa9355b3f304a21544527f0265173497cf6bea', 1),
(35, 30, 'tarjeta_credito', 'Daviplata 9541', '9541', 'edbe0c4c1c5ffc774bdab4a828f03f00884f1d4efd184a06b2d5f342a50705b5', 1);

INSERT INTO estaciones (id_estacion, codigo, nombre, direccion, coordenadas, capacidad, energia_disp, estado) VALUES
(1, 'EST-01', 'Parque Principal de Jardín', 'Calle 5 # 5-42, Jardín, Antioquia', ST_GeomFromText('POINT(-75.8199 5.5986)'), 20, 86.5, 'operativa'),
(2, 'EST-02', 'Casa de la Cultura', 'Carrera 3 # 5-18, Jardín, Antioquia', ST_GeomFromText('POINT(-75.8183 5.6002)'), 14, 72, 'operativa'),
(3, 'EST-03', 'Terminal de Transportes', 'Calle 10 # 4-55, Jardín, Antioquia', ST_GeomFromText('POINT(-75.8255 5.592)'), 16, 64.75, 'operativa'),
(4, 'EST-04', 'Hospital San Juan de Dios', 'Carrera 5 # 8-33, Jardín, Antioquia', ST_GeomFromText('POINT(-75.823 5.6019)'), 12, 91.2, 'operativa'),
(5, 'EST-05', 'Estación de Bomberos', 'Calle 7 # 6-10, Jardín, Antioquia', ST_GeomFromText('POINT(-75.8225 5.5968)'), 10, 48.4, 'mantenimiento'),
(6, 'EST-06', 'Mirador Alto de las Flores', 'Vía Alto de las Flores, Jardín, Antioquia', ST_GeomFromText('POINT(-75.8152 5.5945)'), 8, 55.1, 'operativa');

INSERT INTO bicicletas (id_bicicleta, estacion_act_fk, codigo_qr, modelo, num_serie, nivel_bateria, estado, kilometraje, ult_manten_date, prox_manten_date) VALUES
(1, 2, 'EBJ-QR-0001', 'Sunpeed City E', 'EBJ2026-0001', 71.93, 'disponible', 810.01, '2026-07-12', '2026-10-10'),
(2, 3, 'EBJ-QR-0002', 'Trek E-Urban', 'EBJ2026-0002', 55.97, 'disponible', 563.15, '2026-09-14', '2026-12-13'),
(3, 4, 'EBJ-QR-0003', 'GW E-City Pro', 'EBJ2026-0003', 96.66, 'disponible', 528.09, '2026-07-25', '2026-10-23'),
(4, 5, 'EBJ-QR-0004', 'Specialized Turbo Vado', 'EBJ2026-0004', 77.86, 'disponible', 236.95, '2026-09-26', '2026-12-25'),
(5, 6, 'EBJ-QR-0005', 'Cannondale Mavaro', 'EBJ2026-0005', 63.13, 'disponible', 131.37, '2026-08-10', '2026-11-08'),
(6, 1, 'EBJ-QR-0006', 'Giant Explore E+', 'EBJ2026-0006', 80.18, 'disponible', 846.93, '2026-08-03', '2026-11-01'),
(7, 2, 'EBJ-QR-0007', 'Merida eSpresso', 'EBJ2026-0007', 65.23, 'disponible', 593.24, '2026-10-13', '2027-01-11'),
(8, 3, 'EBJ-QR-0008', 'Bafang Urban 2025', 'EBJ2026-0008', 60.61, 'disponible', 910.39, '2026-07-13', '2026-10-11'),
(9, 4, 'EBJ-QR-0009', 'Sunpeed City E', 'EBJ2026-0009', 64.12, 'disponible', 254.48, '2026-08-22', '2026-11-20'),
(10, 5, 'EBJ-QR-0010', 'Trek E-Urban', 'EBJ2026-0010', 79.78, 'disponible', 224.15, '2026-07-27', '2026-10-25'),
(11, 6, 'EBJ-QR-0011', 'GW E-City Pro', 'EBJ2026-0011', 79.93, 'disponible', 672.42, '2026-07-14', '2026-10-12'),
(12, 1, 'EBJ-QR-0012', 'Specialized Turbo Vado', 'EBJ2026-0012', 91.81, 'disponible', 193.92, '2026-07-17', '2026-10-15'),
(13, 2, 'EBJ-QR-0013', 'Cannondale Mavaro', 'EBJ2026-0013', 86.34, 'disponible', 876, '2026-10-03', '2027-01-01'),
(14, 3, 'EBJ-QR-0014', 'Giant Explore E+', 'EBJ2026-0014', 55.55, 'disponible', 643.84, '2026-07-25', '2026-10-23'),
(15, 4, 'EBJ-QR-0015', 'Merida eSpresso', 'EBJ2026-0015', 94.59, 'disponible', 752.2, '2026-07-22', '2026-10-20'),
(16, 5, 'EBJ-QR-0016', 'Bafang Urban 2025', 'EBJ2026-0016', 69.94, 'disponible', 699.48, '2026-08-18', '2026-11-16'),
(17, 6, 'EBJ-QR-0017', 'Sunpeed City E', 'EBJ2026-0017', 68.27, 'disponible', 738.23, '2026-07-28', '2026-10-26'),
(18, 1, 'EBJ-QR-0018', 'Trek E-Urban', 'EBJ2026-0018', 70.84, 'disponible', 756.43, '2026-09-13', '2026-12-12'),
(19, 2, 'EBJ-QR-0019', 'GW E-City Pro', 'EBJ2026-0019', 64.99, 'disponible', 742.39, '2026-08-17', '2026-11-15'),
(20, 3, 'EBJ-QR-0020', 'Specialized Turbo Vado', 'EBJ2026-0020', 96.21, 'disponible', 610.23, '2026-10-14', '2027-01-12'),
(21, 4, 'EBJ-QR-0021', 'Cannondale Mavaro', 'EBJ2026-0021', 93.21, 'disponible', 818.74, '2026-09-08', '2026-12-07'),
(22, 5, 'EBJ-QR-0022', 'Giant Explore E+', 'EBJ2026-0022', 97.99, 'disponible', 343.59, '2026-07-17', '2026-10-15'),
(23, 6, 'EBJ-QR-0023', 'Merida eSpresso', 'EBJ2026-0023', 76.02, 'disponible', 873.95, '2026-09-15', '2026-12-14'),
(24, 1, 'EBJ-QR-0024', 'Bafang Urban 2025', 'EBJ2026-0024', 80.53, 'disponible', 126.87, '2026-09-26', '2026-12-25'),
(25, 2, 'EBJ-QR-0025', 'Sunpeed City E', 'EBJ2026-0025', 80.24, 'disponible', 393.68, '2026-09-26', '2026-12-25'),
(26, 3, 'EBJ-QR-0026', 'Trek E-Urban', 'EBJ2026-0026', 90.87, 'disponible', 320.21, '2026-09-25', '2026-12-24'),
(27, 4, 'EBJ-QR-0027', 'GW E-City Pro', 'EBJ2026-0027', 78.44, 'disponible', 586.7, '2026-10-10', '2027-01-08'),
(28, 5, 'EBJ-QR-0028', 'Specialized Turbo Vado', 'EBJ2026-0028', 80.37, 'disponible', 451.94, '2026-07-27', '2026-10-25'),
(29, 6, 'EBJ-QR-0029', 'Cannondale Mavaro', 'EBJ2026-0029', 79.09, 'disponible', 916.84, '2026-08-24', '2026-11-22'),
(30, 1, 'EBJ-QR-0030', 'Giant Explore E+', 'EBJ2026-0030', 59.44, 'disponible', 945.54, '2026-08-02', '2026-10-31'),
(31, 2, 'EBJ-QR-0031', 'Merida eSpresso', 'EBJ2026-0031', 70.54, 'disponible', 514.6, '2026-10-04', '2027-01-02'),
(32, 3, 'EBJ-QR-0032', 'Bafang Urban 2025', 'EBJ2026-0032', 67.55, 'disponible', 441.85, '2026-07-20', '2026-10-18'),
(33, 4, 'EBJ-QR-0033', 'Sunpeed City E', 'EBJ2026-0033', 97, 'disponible', 350.02, '2026-07-16', '2026-10-14'),
(34, 5, 'EBJ-QR-0034', 'Trek E-Urban', 'EBJ2026-0034', 72.44, 'disponible', 684.65, '2026-09-22', '2026-12-21'),
(35, 6, 'EBJ-QR-0035', 'GW E-City Pro', 'EBJ2026-0035', 57.4, 'disponible', 663.88, '2026-07-24', '2026-10-22'),
(36, 1, 'EBJ-QR-0036', 'Specialized Turbo Vado', 'EBJ2026-0036', 88.84, 'disponible', 865.26, '2026-09-19', '2026-12-18'),
(37, 2, 'EBJ-QR-0037', 'Cannondale Mavaro', 'EBJ2026-0037', 89.59, 'disponible', 542.86, '2026-07-18', '2026-10-16'),
(38, 3, 'EBJ-QR-0038', 'Giant Explore E+', 'EBJ2026-0038', 99.41, 'disponible', 827.03, '2026-08-25', '2026-11-23'),
(39, 4, 'EBJ-QR-0039', 'Merida eSpresso', 'EBJ2026-0039', 57.62, 'disponible', 292.53, '2026-10-05', '2027-01-03'),
(40, 5, 'EBJ-QR-0040', 'Bafang Urban 2025', 'EBJ2026-0040', 95.86, 'disponible', 969.5, '2026-07-26', '2026-10-24'),
(41, 6, 'EBJ-QR-0041', 'Sunpeed City E', 'EBJ2026-0041', 63.85, 'disponible', 316.65, '2026-07-23', '2026-10-21'),
(42, 1, 'EBJ-QR-0042', 'Trek E-Urban', 'EBJ2026-0042', 84.01, 'disponible', 792.6, '2026-07-31', '2026-10-29'),
(43, 2, 'EBJ-QR-0043', 'GW E-City Pro', 'EBJ2026-0043', 70.8, 'disponible', 781.45, '2026-08-22', '2026-11-20'),
(44, 3, 'EBJ-QR-0044', 'Specialized Turbo Vado', 'EBJ2026-0044', 59.07, 'disponible', 157.83, '2026-08-24', '2026-11-22'),
(45, 4, 'EBJ-QR-0045', 'Cannondale Mavaro', 'EBJ2026-0045', 59.73, 'disponible', 357.34, '2026-08-13', '2026-11-11'),
(46, 5, 'EBJ-QR-0046', 'Giant Explore E+', 'EBJ2026-0046', 62.08, 'disponible', 707.81, '2026-07-18', '2026-10-16'),
(47, 1, 'EBJ-QR-0047', 'Merida eSpresso', 'EBJ2026-0047', 77.35, 'en_uso', 160.35, '2026-09-17', '2026-12-16'),
(48, 2, 'EBJ-QR-0048', 'Bafang Urban 2025', 'EBJ2026-0048', 35.1, 'en_uso', 730.09, '2026-10-01', '2026-12-30'),
(49, 3, 'EBJ-QR-0049', 'Sunpeed City E', 'EBJ2026-0049', 81.39, 'en_uso', 817.88, '2026-10-07', '2027-01-05'),
(50, 5, 'EBJ-QR-0050', 'Trek E-Urban', 'EBJ2026-0050', 36.42, 'mantenimiento', 415.21, '2026-10-05', '2027-01-03'),
(51, 5, 'EBJ-QR-0051', 'GW E-City Pro', 'EBJ2026-0051', 22.15, 'mantenimiento', 342.24, '2026-09-27', '2026-12-26'),
(52, 5, 'EBJ-QR-0052', 'Specialized Turbo Vado', 'EBJ2026-0052', 36.99, 'mantenimiento', 624.93, '2026-09-02', '2026-12-01'),
(53, NULL, 'EBJ-QR-0053', 'Cannondale Mavaro', 'EBJ2026-0053', 11.36, 'baja', 222.65, '2026-09-02', '2026-12-01'),
(54, NULL, 'EBJ-QR-0054', 'Giant Explore E+', 'EBJ2026-0054', 7.63, 'baja', 265.95, '2026-07-20', '2026-10-18'),
(55, NULL, 'EBJ-QR-0055', 'Merida eSpresso', 'EBJ2026-0055', 12.59, 'baja', 230.85, '2026-09-30', '2026-12-29');

INSERT INTO alquileres (id_alquiler, id_usuario, id_bicicleta, estacion_orig_fk, estacion_dest_fk, uuid_alquiler, fecha_inicio, fecha_fin, duracion_minutos, tarifa_por_minuto, costo_total, estado) VALUES
(1, 19, 34, 2, 4, 'e9b91c77-9199-432c-ae30-e3f4c8923b0a', '2026-07-24 11:29:36', '2026-07-24 12:04:36', 35, 150, 5250, 'completado'),
(2, 21, 36, 1, 5, '40fe41dc-a578-4bf7-9f01-45a1ba6d96ee', '2026-08-08 07:53:35', '2026-08-08 08:37:35', 44, 150, 6600, 'completado'),
(3, 13, 21, 6, 5, '91d5dcc4-8b36-40c1-8290-4480f40bec15', '2026-07-16 13:38:31', '2026-07-16 13:48:31', 10, 150, 1500, 'completado'),
(4, 9, 52, 4, 4, 'bd421023-9c22-4d35-842b-d7fde699d0a2', '2026-07-15 20:09:29', '2026-07-15 20:29:29', 20, 150, 3000, 'completado'),
(5, 16, 20, 2, 2, '8dfd4142-546d-4290-85bd-6f5b08366f80', '2026-06-24 06:43:20', '2026-06-24 06:55:20', 12, 150, 1800, 'completado'),
(6, 10, 20, 6, 3, '76cbac48-fdb0-44b3-8de0-d6cc082ebf8c', '2026-08-12 01:28:45', '2026-08-12 02:46:45', 78, 150, 11700, 'completado'),
(7, 14, 37, 2, 1, 'e168aa90-843a-4298-86eb-dd7c22e95926', '2026-06-20 15:09:40', '2026-06-20 16:00:40', 51, 150, 7650, 'completado'),
(8, 25, 15, 6, 5, '101f0e2e-cc99-41c8-9b30-a4b51633eabb', '2026-08-31 14:51:33', '2026-08-31 14:56:33', 5, 150, 750, 'completado'),
(9, 4, 7, 3, 1, '5af5e435-0590-4ebe-8185-8525b0467b8e', '2026-06-25 19:45:04', '2026-06-25 20:44:04', 59, 150, 8850, 'completado'),
(10, 26, 26, 1, 3, '60a9df50-77a4-4c7d-aa40-701df2a76ec8', '2026-10-03 04:11:30', '2026-10-03 04:38:30', 27, 150, 4050, 'completado'),
(11, 28, 1, 5, 3, '4bfdd39e-df8b-418d-92b1-0f47a395645e', '2026-07-19 02:48:25', '2026-07-19 03:30:25', 42, 150, 6300, 'completado'),
(12, 11, 26, 3, 4, '61b315d5-5adb-4cdb-9e95-df27028c6b4a', '2026-07-19 17:52:35', '2026-07-19 19:46:35', 114, 150, 17100, 'completado'),
(13, 12, 43, 3, 4, '07ce56e2-c0c4-4af7-9979-87440d51adcf', '2026-06-18 00:11:53', '2026-06-18 01:54:53', 103, 150, 15450, 'completado'),
