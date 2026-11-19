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
