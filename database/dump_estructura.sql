SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS sigeru
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sigeru;

DROP TABLE IF EXISTS rol;
CREATE TABLE rol (
    id_rol      INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(50)  NOT NULL UNIQUE,
    descripcion VARCHAR(200) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS usuario;
CREATE TABLE usuario (
    id_usuario     INT AUTO_INCREMENT PRIMARY KEY,
    nombre         VARCHAR(80)  NOT NULL,
    apellido       VARCHAR(80)  NOT NULL,
    email          VARCHAR(120) NOT NULL UNIQUE,
    password_hash  VARCHAR(255) NOT NULL,
    telefono       VARCHAR(30)  NULL,
    direccion      VARCHAR(200) NULL,
    id_rol         INT          NOT NULL,
    estado         ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
    fecha_registro DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuario_rol FOREIGN KEY (id_rol) REFERENCES rol(id_rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS solicitud_registro;
CREATE TABLE solicitud_registro (
    id_solicitud    INT AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(80)  NOT NULL,
    apellido        VARCHAR(80)  NOT NULL,
    email           VARCHAR(120) NOT NULL,
    telefono        VARCHAR(30)  NULL,
    direccion       VARCHAR(200) NULL,
    estado          ENUM('pendiente','aprobada','rechazada') NOT NULL DEFAULT 'pendiente',
    fecha_solicitud DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS tipo_residuo;
CREATE TABLE tipo_residuo (
    id_tipo_residuo INT AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(60)  NOT NULL UNIQUE,
    descripcion     VARCHAR(200) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS centro_acopio;
CREATE TABLE centro_acopio (
    id_centro  INT AUTO_INCREMENT PRIMARY KEY,
    nombre     VARCHAR(120) NOT NULL,
    direccion  VARCHAR(200) NULL,
    latitud    DECIMAL(10,7) NULL,
    longitud   DECIMAL(10,7) NULL,
    capacidad  INT NULL,
    estado     ENUM('activo','inactivo') NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS contenedor;
CREATE TABLE contenedor (
    id_contenedor   INT AUTO_INCREMENT PRIMARY KEY,
    codigo          VARCHAR(30)  NOT NULL UNIQUE,
    direccion       VARCHAR(200) NOT NULL,
    latitud         DECIMAL(10,7) NULL,
    longitud        DECIMAL(10,7) NULL,
    capacidad       INT NULL,
    estado          ENUM('disponible','lleno','mantenimiento','retirado') NOT NULL DEFAULT 'disponible',
    id_tipo_residuo INT NULL,
    id_centro       INT NULL,
    CONSTRAINT fk_contenedor_tipo   FOREIGN KEY (id_tipo_residuo) REFERENCES tipo_residuo(id_tipo_residuo),
    CONSTRAINT fk_contenedor_centro FOREIGN KEY (id_centro)       REFERENCES centro_acopio(id_centro)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS camion;
CREATE TABLE camion (
    id_camion  INT AUTO_INCREMENT PRIMARY KEY,
    matricula  VARCHAR(20)  NOT NULL UNIQUE,
    marca      VARCHAR(60)  NULL,
    modelo     VARCHAR(60)  NULL,
    capacidad  INT NULL,
    estado     ENUM('operativo','en_ruta','mantenimiento','fuera_servicio') NOT NULL DEFAULT 'operativo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;