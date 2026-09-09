-- ============================================================================
--  SiGeRU 2026 - Sistema de Gestion de Residuos Urbanos
--  Archivo: schema.sql
--  Que es : DDL de la base de datos. Crea la base y las 14 tablas del modelo.
--  Motor  : MySQL / MariaDB (InnoDB, utf8mb4)
--  Entrega: 2da entrega. Reemplaza al schema de 7 tablas de la 1ra.
-- ============================================================================
--
--  DE DONDE SALEN ESTAS TABLAS
--  Del modelo de clases del proyecto (14 clases, 75 atributos, 17 relaciones),
--  que es la misma fuente del Diagrama de Clases y del Pasaje a Tablas 3FN.
--  Cada clase es una tabla y cada relacion "0..* a 1" es una clave foranea del
--  lado "muchos".
--
--  DECISIONES QUE CONVIENE PODER DEFENDER
--  1. Las relaciones de composicion del diagrama de clases llevan
--     ON DELETE CASCADE, porque la parte no existe sin el todo:
--       - estado_contenedor no tiene sentido sin su contenedor
--       - vaciado no tiene sentido sin su recoleccion
--  2. Las bajas del sistema son LOGICAS (columna estado), no borrados fisicos:
--     un contenedor retirado o un usuario inactivo son parte del historial.
--  3. La columna se llama "capacidad" y no "capacidad_l" / "capacidad_kg" como
--     el modelo de clases, porque es el nombre que ya usan las APIs y el
--     frontend. La unidad de cada una queda aclarada en el comentario.
--  4. El orden de creacion respeta las claves foraneas: primero las tablas
--     referenciadas, despues las que referencian.
-- ============================================================================

CREATE DATABASE IF NOT EXISTS sigeru
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE sigeru;

-- El orden inverso al de creacion, para poder reimportar sin errores de FK.
DROP TABLE IF EXISTS incidencia;
DROP TABLE IF EXISTS vaciado;
DROP TABLE IF EXISTS recoleccion;
DROP TABLE IF EXISTS ruta;
DROP TABLE IF EXISTS camion;
DROP TABLE IF EXISTS estado_contenedor;
DROP TABLE IF EXISTS contenedor;
DROP TABLE IF EXISTS usuario;
DROP TABLE IF EXISTS solicitud_registro;
DROP TABLE IF EXISTS cuadrilla;
DROP TABLE IF EXISTS tipo_residuo;
DROP TABLE IF EXISTS zona;
DROP TABLE IF EXISTS centro_acopio;
DROP TABLE IF EXISTS rol;


-- ----------------------------------------------------------------------------
-- 1) rol
-- Los tipos de usuario del sistema: Administrador, Operario, Cuadrilla, Vecino.
-- ----------------------------------------------------------------------------
CREATE TABLE rol (
    id_rol      INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(50)  NOT NULL UNIQUE,
    descripcion VARCHAR(200) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- 2) centro_acopio
-- Donde se descarga lo recolectado. Se crea antes que zona porque zona lo
-- referencia (relacion "abastece_a").
-- ----------------------------------------------------------------------------
CREATE TABLE centro_acopio (
    id_centro  INT AUTO_INCREMENT PRIMARY KEY,
    nombre     VARCHAR(120)  NOT NULL,
    direccion  VARCHAR(200)  NULL,
    latitud    DECIMAL(10,7) NULL,
    longitud   DECIMAL(10,7) NULL,
    capacidad  INT           NULL COMMENT 'Capacidad en kg',
    estado     ENUM('activo','inactivo') NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- 3) zona
-- Division territorial de la ciudad. Cada zona puede tener un centro de acopio
-- que la abastece (0..1), y agrupa contenedores y rutas.
-- ----------------------------------------------------------------------------
CREATE TABLE zona (
    id_zona     INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(80)  NOT NULL UNIQUE,
    descripcion VARCHAR(200) NULL,
    id_centro   INT          NULL,
    CONSTRAINT fk_zona_centro
        FOREIGN KEY (id_centro) REFERENCES centro_acopio(id_centro)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- 4) tipo_residuo
-- Las categorias de residuo. "reciclable" permite separar en los informes lo
-- que se recupera de lo que va a disposicion final.
-- ----------------------------------------------------------------------------
CREATE TABLE tipo_residuo (
    id_tipo_residuo INT AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(60)  NOT NULL UNIQUE,
    descripcion     VARCHAR(200) NULL,
    reciclable      BOOLEAN      NOT NULL DEFAULT FALSE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- 5) cuadrilla
-- Los equipos de trabajo. Un usuario puede integrar una cuadrilla (agregacion:
-- la persona existe aunque la cuadrilla se disuelva).
-- ----------------------------------------------------------------------------
CREATE TABLE cuadrilla (
    id_cuadrilla INT AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(80) NOT NULL UNIQUE,
    turno        ENUM('matutino','vespertino','nocturno') NOT NULL DEFAULT 'matutino',
    estado       ENUM('activa','inactiva') NOT NULL DEFAULT 'activa'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- 6) solicitud_registro
-- Pedidos de alta que todavia NO fueron aprobados. Cuando un administrador
-- aprueba una, se crea el usuario y queda el vinculo (relacion "origina").
-- ----------------------------------------------------------------------------
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


-- ----------------------------------------------------------------------------
-- 7) usuario
-- Las personas que usan el sistema. La contrasena se guarda HASHEADA
-- (password_hash de PHP), nunca en texto plano.
-- ----------------------------------------------------------------------------
CREATE TABLE usuario (
    id_usuario     INT AUTO_INCREMENT PRIMARY KEY,
    nombre         VARCHAR(80)  NOT NULL,
    apellido       VARCHAR(80)  NOT NULL,
    email          VARCHAR(120) NOT NULL UNIQUE,
    password_hash  VARCHAR(255) NOT NULL,
    telefono       VARCHAR(30)  NULL,
    direccion      VARCHAR(200) NULL,
    id_rol         INT          NOT NULL,
    id_cuadrilla   INT          NULL,
    id_solicitud   INT          NULL,
    estado         ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
    fecha_registro DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuario_rol
        FOREIGN KEY (id_rol) REFERENCES rol(id_rol),
    CONSTRAINT fk_usuario_cuadrilla
        FOREIGN KEY (id_cuadrilla) REFERENCES cuadrilla(id_cuadrilla),
    CONSTRAINT fk_usuario_solicitud
        FOREIGN KEY (id_solicitud) REFERENCES solicitud_registro(id_solicitud)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- 8) contenedor
-- Los contenedores repartidos en la ciudad. Pertenece a una zona y acepta un
-- tipo de residuo (las dos obligatorias segun el modelo).
-- ----------------------------------------------------------------------------
CREATE TABLE contenedor (
    id_contenedor   INT AUTO_INCREMENT PRIMARY KEY,
    codigo          VARCHAR(30)   NOT NULL UNIQUE,
    direccion       VARCHAR(200)  NOT NULL,
    latitud         DECIMAL(10,7) NULL,
    longitud        DECIMAL(10,7) NULL,
    capacidad       INT           NULL COMMENT 'Capacidad en litros',
    estado          ENUM('disponible','lleno','mantenimiento','retirado')
                        NOT NULL DEFAULT 'disponible',
    id_zona         INT NOT NULL,
    id_tipo_residuo INT NOT NULL,
    CONSTRAINT fk_contenedor_zona
        FOREIGN KEY (id_zona) REFERENCES zona(id_zona),
    CONSTRAINT fk_contenedor_tipo
        FOREIGN KEY (id_tipo_residuo) REFERENCES tipo_residuo(id_tipo_residuo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- 9) estado_contenedor
-- Historial de estados de cada contenedor. Es COMPOSICION: si se borra el
-- contenedor, su historial se va con el (ON DELETE CASCADE).
-- Este historial es lo que permite distinguir un contenedor que necesita
-- reparacion de uno que llego al final de su vida util.
-- ----------------------------------------------------------------------------
CREATE TABLE estado_contenedor (
    id_registro   INT AUTO_INCREMENT PRIMARY KEY,
    id_contenedor INT      NOT NULL,
    fecha_hora    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado        ENUM('disponible','lleno','mantenimiento','retirado') NOT NULL,
    observacion   VARCHAR(200) NULL,
    CONSTRAINT fk_estadocont_contenedor
        FOREIGN KEY (id_contenedor) REFERENCES contenedor(id_contenedor)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- 10) camion
-- La flota de recoleccion.
-- ----------------------------------------------------------------------------
CREATE TABLE camion (
    id_camion INT AUTO_INCREMENT PRIMARY KEY,
    matricula VARCHAR(20) NOT NULL UNIQUE,
    marca     VARCHAR(60) NULL,
    modelo    VARCHAR(60) NULL,
    capacidad INT         NULL COMMENT 'Capacidad de carga en kg',
    estado    ENUM('operativo','en_ruta','mantenimiento','fuera_servicio')
                  NOT NULL DEFAULT 'operativo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- 11) ruta
-- El recorrido planificado sobre una zona, con su dia y hora.
-- ----------------------------------------------------------------------------
CREATE TABLE ruta (
    id_ruta     INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(80) NOT NULL,
    dia_semana  ENUM('lunes','martes','miercoles','jueves','viernes','sabado','domingo')
                    NOT NULL,
    hora_inicio TIME NOT NULL,
    estado      ENUM('activa','inactiva') NOT NULL DEFAULT 'activa',
    id_zona     INT NOT NULL,
    CONSTRAINT fk_ruta_zona
        FOREIGN KEY (id_zona) REFERENCES zona(id_zona)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- 12) recoleccion
-- La ejecucion real de una ruta: que camion, que cuadrilla, cuando empezo y
-- termino, y en que centro descargo.
-- ----------------------------------------------------------------------------
CREATE TABLE recoleccion (
    id_recoleccion    INT AUTO_INCREMENT PRIMARY KEY,
    fecha_hora_inicio DATETIME NOT NULL,
    fecha_hora_fin    DATETIME NULL,
    estado            ENUM('planificada','en_curso','finalizada','cancelada')
                          NOT NULL DEFAULT 'planificada',
    observaciones     VARCHAR(300) NULL,
    id_ruta           INT NOT NULL,
    id_camion         INT NOT NULL,
    id_cuadrilla      INT NOT NULL,
    id_centro         INT NULL,
    CONSTRAINT fk_recoleccion_ruta
        FOREIGN KEY (id_ruta) REFERENCES ruta(id_ruta),
    CONSTRAINT fk_recoleccion_camion
        FOREIGN KEY (id_camion) REFERENCES camion(id_camion),
    CONSTRAINT fk_recoleccion_cuadrilla
        FOREIGN KEY (id_cuadrilla) REFERENCES cuadrilla(id_cuadrilla),
    CONSTRAINT fk_recoleccion_centro
        FOREIGN KEY (id_centro) REFERENCES centro_acopio(id_centro)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- 13) vaciado
-- Cada contenedor vaciado dentro de una recoleccion. Es COMPOSICION respecto de
-- la recoleccion: sin la recoleccion, el vaciado no existe.
-- Es la tabla que permite medir cuanto se levanta por tipo de residuo.
-- ----------------------------------------------------------------------------
CREATE TABLE vaciado (
    id_vaciado     INT AUTO_INCREMENT PRIMARY KEY,
    id_recoleccion INT      NOT NULL,
    id_contenedor  INT      NOT NULL,
    fecha_hora     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    peso_kg        DECIMAL(10,2) NULL,
    volumen_l      DECIMAL(10,2) NULL,
    CONSTRAINT fk_vaciado_recoleccion
        FOREIGN KEY (id_recoleccion) REFERENCES recoleccion(id_recoleccion)
        ON DELETE CASCADE,
    CONSTRAINT fk_vaciado_contenedor
        FOREIGN KEY (id_contenedor) REFERENCES contenedor(id_contenedor)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- 14) incidencia
-- El reporte que hace un vecino sobre un contenedor. Puede quedar asignado a
-- una cuadrilla para que lo atienda.
-- ----------------------------------------------------------------------------
CREATE TABLE incidencia (
    id_incidencia    INT AUTO_INCREMENT PRIMARY KEY,
    descripcion      VARCHAR(500) NOT NULL,
    fecha_reporte    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_resolucion DATETIME NULL,
    estado           ENUM('reportada','en_proceso','resuelta','descartada')
                         NOT NULL DEFAULT 'reportada',
    prioridad        ENUM('baja','media','alta') NOT NULL DEFAULT 'media',
    id_usuario       INT NOT NULL,
    id_contenedor    INT NOT NULL,
    id_cuadrilla     INT NULL,
    CONSTRAINT fk_incidencia_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario),
    CONSTRAINT fk_incidencia_contenedor
        FOREIGN KEY (id_contenedor) REFERENCES contenedor(id_contenedor),
    CONSTRAINT fk_incidencia_cuadrilla
        FOREIGN KEY (id_cuadrilla) REFERENCES cuadrilla(id_cuadrilla)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- Indices de apoyo
-- Sobre las columnas por las que el sistema filtra todo el tiempo. Las claves
-- foraneas ya crean su indice; estos son los de los filtros de pantalla.
-- ----------------------------------------------------------------------------
CREATE INDEX idx_contenedor_estado   ON contenedor(estado);
CREATE INDEX idx_camion_estado       ON camion(estado);
CREATE INDEX idx_usuario_estado      ON usuario(estado);
CREATE INDEX idx_solicitud_estado    ON solicitud_registro(estado);
CREATE INDEX idx_incidencia_estado   ON incidencia(estado);
CREATE INDEX idx_estadocont_fecha    ON estado_contenedor(id_contenedor, fecha_hora);

-- ============================================================================
--  Fin del schema. 14 tablas, derivadas del modelo de clases del proyecto.
-- ============================================================================
