-- =====================================================================
-- DonaVida – Red de donantes de sangre
-- Script de la base de datos (MySQL 8)
--
-- IMPORTANTE: en el proyecto la base se crea con las migraciones de Laravel
--   (php artisan migrate:fresh --seed). Este script es una copia equivalente
--   para la documentación y para generar el diagrama ER en MySQL Workbench
--   (Database > Reverse Engineer). Si cambian una migración, actualicen este archivo.
-- =====================================================================

DROP DATABASE IF EXISTS donavida;
CREATE DATABASE donavida CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE donavida;

-- 1/10 roles
CREATE TABLE roles (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(30)  NOT NULL UNIQUE,
    descripcion   VARCHAR(150) NULL,
    created_at    TIMESTAMP NULL,
    updated_at    TIMESTAMP NULL
);

-- 2/10 usuarios
CREATE TABLE usuarios (
    id                 BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rol_id             BIGINT UNSIGNED NOT NULL,
    nombre             VARCHAR(100) NOT NULL,
    email              VARCHAR(150) NOT NULL UNIQUE,
    telefono           VARCHAR(20)  NULL,
    password           VARCHAR(255) NOT NULL,
    estado             ENUM('activo','inactivo','pendiente') NOT NULL DEFAULT 'activo',
    email_verified_at  TIMESTAMP NULL,
    remember_token     VARCHAR(100) NULL,
    created_at         TIMESTAMP NULL,
    updated_at         TIMESTAMP NULL,
    CONSTRAINT fk_usuarios_rol FOREIGN KEY (rol_id) REFERENCES roles(id)
);

-- 3/10 tipos_sangre
CREATE TABLE tipos_sangre (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo            VARCHAR(3) NOT NULL UNIQUE,
    grupo             ENUM('A','B','AB','O') NOT NULL,
    factor_rh         ENUM('+','-') NOT NULL,
    puede_recibir_de  JSON NOT NULL,
    descripcion       VARCHAR(150) NULL,
    created_at        TIMESTAMP NULL,
    updated_at        TIMESTAMP NULL
);

-- 4/10 donantes
CREATE TABLE donantes (
    id                     BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id             BIGINT UNSIGNED NOT NULL UNIQUE,
    tipo_sangre_id         BIGINT UNSIGNED NOT NULL,
    apellidos              VARCHAR(100) NOT NULL,
    ci                     VARCHAR(20)  NOT NULL UNIQUE,
    fecha_nacimiento       DATE NOT NULL,
    sexo                   ENUM('F','M') NOT NULL,
    peso_kg                DECIMAL(5,2) NULL,
    direccion              VARCHAR(200) NULL,
    zona                   VARCHAR(100) NULL,
    latitud                DECIMAL(10,7) NULL,
    longitud               DECIMAL(10,7) NULL,
    disponible             TINYINT(1) NOT NULL DEFAULT 1,
    foto_ruta              VARCHAR(255) NULL,
    carnet_ruta            VARCHAR(255) NULL,
    fecha_ultima_donacion  DATE NULL,
    created_at             TIMESTAMP NULL,
    updated_at             TIMESTAMP NULL,
    CONSTRAINT fk_donantes_usuario     FOREIGN KEY (usuario_id)     REFERENCES usuarios(id),
    CONSTRAINT fk_donantes_tipo_sangre FOREIGN KEY (tipo_sangre_id) REFERENCES tipos_sangre(id)
);

-- 5/10 hospitales
CREATE TABLE hospitales (
    id                 BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id         BIGINT UNSIGNED NOT NULL UNIQUE,
    razon_social       VARCHAR(150) NOT NULL,
    nit                VARCHAR(20)  NOT NULL UNIQUE,
    direccion          VARCHAR(200) NOT NULL,
    zona               VARCHAR(100) NULL,
    latitud            DECIMAL(10,7) NOT NULL,
    longitud           DECIMAL(10,7) NOT NULL,
    telefono_contacto  VARCHAR(20) NOT NULL,
    whatsapp           VARCHAR(20) NULL,
    verificado         TINYINT(1) NOT NULL DEFAULT 0,
    created_at         TIMESTAMP NULL,
    updated_at         TIMESTAMP NULL,
    CONSTRAINT fk_hospitales_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- 6/10 solicitudes
CREATE TABLE solicitudes (
    id                    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hospital_id           BIGINT UNSIGNED NOT NULL,
    tipo_sangre_id        BIGINT UNSIGNED NOT NULL,
    unidades_requeridas   SMALLINT UNSIGNED NOT NULL,
    unidades_confirmadas  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    prioridad             ENUM('baja','media','alta','critica') NOT NULL DEFAULT 'alta',
    motivo                TEXT NULL,
    fecha_limite          DATETIME NOT NULL,
    estado                ENUM('abierta','cubierta','cerrada','cancelada') NOT NULL DEFAULT 'abierta',
    created_at            TIMESTAMP NULL,
    updated_at            TIMESTAMP NULL,
    CONSTRAINT fk_solicitudes_hospital    FOREIGN KEY (hospital_id)    REFERENCES hospitales(id),
    CONSTRAINT fk_solicitudes_tipo_sangre FOREIGN KEY (tipo_sangre_id) REFERENCES tipos_sangre(id)
);

-- 7/10 campanas (sin "ñ" a propósito)
CREATE TABLE campanas (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hospital_id  BIGINT UNSIGNED NOT NULL,
    nombre       VARCHAR(150) NOT NULL,
    descripcion  TEXT NULL,
    lugar        VARCHAR(200) NOT NULL,
    latitud      DECIMAL(10,7) NULL,
    longitud     DECIMAL(10,7) NULL,
    fecha        DATE NOT NULL,
    hora_inicio  TIME NOT NULL,
    hora_fin     TIME NOT NULL,
    cupos        SMALLINT UNSIGNED NOT NULL,
    estado       ENUM('programada','en_curso','finalizada','cancelada') NOT NULL DEFAULT 'programada',
    created_at   TIMESTAMP NULL,
    updated_at   TIMESTAMP NULL,
    CONSTRAINT fk_campanas_hospital FOREIGN KEY (hospital_id) REFERENCES hospitales(id)
);

-- 8/10 citas
CREATE TABLE citas (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    donante_id     BIGINT UNSIGNED NOT NULL,
    solicitud_id   BIGINT UNSIGNED NULL,
    campana_id     BIGINT UNSIGNED NULL,
    fecha_hora     DATETIME NOT NULL,
    estado         ENUM('pendiente','confirmada','asistio','no_asistio','cancelada') NOT NULL DEFAULT 'pendiente',
    observaciones  TEXT NULL,
    created_at     TIMESTAMP NULL,
    updated_at     TIMESTAMP NULL,
    CONSTRAINT fk_citas_donante   FOREIGN KEY (donante_id)   REFERENCES donantes(id),
    CONSTRAINT fk_citas_solicitud FOREIGN KEY (solicitud_id) REFERENCES solicitudes(id),
    CONSTRAINT fk_citas_campana   FOREIGN KEY (campana_id)   REFERENCES campanas(id),
    -- La cita es de una solicitud O de una campaña, nunca de las dos ni de ninguna
    CONSTRAINT chk_citas_origen CHECK (
        (solicitud_id IS NOT NULL AND campana_id IS NULL)
        OR (solicitud_id IS NULL AND campana_id IS NOT NULL)
    )
);

-- 9/10 donaciones
CREATE TABLE donaciones (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cita_id         BIGINT UNSIGNED NOT NULL UNIQUE,
    donante_id      BIGINT UNSIGNED NOT NULL,
    fecha_donacion  DATETIME NOT NULL,
    volumen_ml      SMALLINT UNSIGNED NOT NULL DEFAULT 450,
    estado          ENUM('valida','anulada') NOT NULL DEFAULT 'valida',
    observaciones   TEXT NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    CONSTRAINT fk_donaciones_cita    FOREIGN KEY (cita_id)    REFERENCES citas(id),
    CONSTRAINT fk_donaciones_donante FOREIGN KEY (donante_id) REFERENCES donantes(id)
);

-- 10/10 notificaciones
CREATE TABLE notificaciones (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id     BIGINT UNSIGNED NOT NULL,
    solicitud_id   BIGINT UNSIGNED NULL,
    tipo           ENUM('solicitud_urgente','cita','recordatorio','sistema') NOT NULL,
    titulo         VARCHAR(150) NOT NULL,
    mensaje        TEXT NOT NULL,
    leida          TINYINT(1) NOT NULL DEFAULT 0,
    fecha_lectura  TIMESTAMP NULL,
    created_at     TIMESTAMP NULL,
    updated_at     TIMESTAMP NULL,
    CONSTRAINT fk_notificaciones_usuario   FOREIGN KEY (usuario_id)   REFERENCES usuarios(id),
    CONSTRAINT fk_notificaciones_solicitud FOREIGN KEY (solicitud_id) REFERENCES solicitudes(id)
);

-- ---------------------------------------------------------------------
-- Datos fijos del sistema
-- ---------------------------------------------------------------------
INSERT INTO roles (nombre, descripcion, created_at, updated_at) VALUES
('administrador', 'Valida hospitales y supervisa todo el sistema',      NOW(), NOW()),
('hospital',      'Publica solicitudes y campañas, registra donaciones', NOW(), NOW()),
('donante',       'Recibe avisos, acepta solicitudes y agenda citas',    NOW(), NOW());

-- "puede_recibir_de" = tipos de donantes compatibles con ese receptor
INSERT INTO tipos_sangre (codigo, grupo, factor_rh, puede_recibir_de, descripcion, created_at, updated_at) VALUES
('O-',  'O',  '-', '["O-"]',                                       'Donante universal',  NOW(), NOW()),
('O+',  'O',  '+', '["O+","O-"]',                                  NULL,                 NOW(), NOW()),
('A-',  'A',  '-', '["A-","O-"]',                                  NULL,                 NOW(), NOW()),
('A+',  'A',  '+', '["A+","A-","O+","O-"]',                        NULL,                 NOW(), NOW()),
('B-',  'B',  '-', '["B-","O-"]',                                  NULL,                 NOW(), NOW()),
('B+',  'B',  '+', '["B+","B-","O+","O-"]',                        NULL,                 NOW(), NOW()),
('AB-', 'AB', '-', '["AB-","A-","B-","O-"]',                       NULL,                 NOW(), NOW()),
('AB+', 'AB', '+', '["AB+","AB-","A+","A-","B+","B-","O+","O-"]',  'Receptor universal', NOW(), NOW());
