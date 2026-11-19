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
