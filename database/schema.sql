-- ============================================================================
--  SiGeRU 2026 - Sistema de Gestion de Residuos Urbanos
--  Archivo: schema.sql
--  Que es : DDL de la base de datos. Crea la base y las 21 tablas del modelo:
--           las 15 de la 2da entrega, historial_incidencia (15 bis, de la 3ra) y, de
--           las extras, recepcion, movimiento_stock, historial_maquinaria (parte 12) y
--           notificacion (parte 13).
--           Mas cinco tablas fuera del modelo de clases: migracion, intento_login,
--           limite_peticion (freno por IP a los pedidos publicos),
--           auditoria (quien hizo que) y
--           foto_incidencia (la foto opcional de una incidencia, RF-222; es parte
--           de Incidencia, no una clase aparte).
--  Motor  : MySQL / MariaDB (InnoDB, utf8mb4)
--  Entrega: 2da entrega. Reemplaza al schema de 7 tablas de la 1ra.
-- ============================================================================
--
--  DE DONDE SALEN ESTAS TABLAS
--  Del modelo de clases del proyecto (15 clases, 81 atributos, 18 relaciones),
--  que es la misma fuente del Diagrama de Clases y del Pasaje a Tablas 3FN.
--  Cada clase es una tabla y cada relacion "0..* a 1" es una clave foranea del
--  lado "muchos".
--  La tabla 15 bis (historial_incidencia) entro en la 3ra entrega, con las
--  migraciones 002 y 003: el requerimiento RF-232 pide conservar cada cambio de
--  estado de una incidencia con su fecha, su autor y su observacion, y la tabla
--  incidencia sola no puede guardarlo. Ver programacion-full-stack/
--  ampliacion-incidencias-3ra.md.
--  La tabla 11 (maquinaria) entro despues, con la Adenda de Alcance del
--  09/09/2026: el modelo original tenia 14 tablas y no cubria la "maquinaria
--  basica" que la letra pide para esta entrega. El motivo y el calculo que
--  respaldan la decision estan en Adenda_Alcance_Maquinaria_SiGeRU.pdf.
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

-- Tablas que se retiraron (25/10/2026: calificacion y puntos del vecino, claves de operacion; 30/10/2026: enlaces de contrasena por correo): si quedan
-- de una base anterior hay que borrarlas primero, porque algunas tienen claves foraneas hacia incidencia y usuario y bloquearian el reimportado.
DROP TABLE IF EXISTS movimiento_punto;
DROP TABLE IF EXISTS calificacion;
DROP TABLE IF EXISTS clave_idempotencia;
DROP TABLE IF EXISTS enlace_clave;

-- El orden inverso al de creacion, para poder reimportar sin errores de FK.
DROP TABLE IF EXISTS notificacion;
DROP TABLE IF EXISTS historial_maquinaria;
DROP TABLE IF EXISTS movimiento_stock;
DROP TABLE IF EXISTS recepcion;
DROP TABLE IF EXISTS limite_peticion;
DROP TABLE IF EXISTS auditoria;
DROP TABLE IF EXISTS foto_incidencia;
DROP TABLE IF EXISTS historial_incidencia;
DROP TABLE IF EXISTS incidencia;
DROP TABLE IF EXISTS vaciado;
DROP TABLE IF EXISTS recoleccion;
DROP TABLE IF EXISTS ruta_contenedor;
DROP TABLE IF EXISTS ruta;
DROP TABLE IF EXISTS maquinaria;
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
DROP TABLE IF EXISTS intento_login;
DROP TABLE IF EXISTS migracion;


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
    estado       ENUM('activa','inactiva') NOT NULL DEFAULT 'activa',
    id_zona      INT NULL,
    CONSTRAINT fk_cuadrilla_zona
        FOREIGN KEY (id_zona) REFERENCES zona(id_zona)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Desde la migracion 020 la cuadrilla tiene municipio (opcional): la sugerencia de asignacion empareja cada ruta con la cuadrilla de su municipio.


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
    fecha_solicitud DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tipo_cuenta     ENUM('vecino','cuadrilla') NOT NULL DEFAULT 'vecino',
    cuadrilla_indicada VARCHAR(80) NULL
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
    id_centro      INT          NULL,
    id_zona        INT          NULL,
    id_solicitud   INT          NULL,
    estado         ENUM('activo','inactivo','pendiente') NOT NULL DEFAULT 'activo',
    fecha_registro DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuario_rol
        FOREIGN KEY (id_rol) REFERENCES rol(id_rol),
    CONSTRAINT fk_usuario_cuadrilla
        FOREIGN KEY (id_cuadrilla) REFERENCES cuadrilla(id_cuadrilla),
    CONSTRAINT fk_usuario_centro
        FOREIGN KEY (id_centro) REFERENCES centro_acopio(id_centro),
    CONSTRAINT fk_usuario_zona
        FOREIGN KEY (id_zona) REFERENCES zona(id_zona),
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
-- 11) maquinaria
-- El equipamiento fijo de los centros de acopio y vertederos: compactadoras,
-- palas cargadoras, cintas de clasificacion. Es lo que la letra llama
-- "maquinaria basica" (nivel intermedio, punto 2.4 de los alcances sugeridos).
--
-- El alcance es de REGISTRO de inventario: que hay, de que tipo, donde esta y
-- en que estado. La planificacion de mantenimiento preventivo y la maquinaria
-- especializada son nivel avanzado de la letra y quedan documentadas como
-- alcance futuro en la Adenda de Alcance, no implementadas.
--
-- Depende de centro_acopio y la relacion es ASOCIACION SIMPLE, no composicion:
-- si un centro se da de baja, sus maquinas siguen existiendo y se reasignan a
-- otro. Por eso no lleva ON DELETE CASCADE, a diferencia de estado_contenedor
-- y vaciado.
--
-- La baja es logica, como en el resto del sistema: estado = 'fuera_servicio',
-- el mismo criterio y hasta el mismo valor que usa camion.
-- ----------------------------------------------------------------------------
CREATE TABLE maquinaria (
    id_maquinaria INT AUTO_INCREMENT PRIMARY KEY,
    codigo        VARCHAR(30) NOT NULL UNIQUE COMMENT 'Codigo patrimonial del equipo',
    tipo          ENUM('compactadora','pala_cargadora','cinta_clasificadora',
                       'retroexcavadora','montacargas','otra')
                      NOT NULL DEFAULT 'otra',
    marca         VARCHAR(60) NULL,
    modelo        VARCHAR(60) NULL,
    estado        ENUM('operativa','mantenimiento','fuera_servicio')
                      NOT NULL DEFAULT 'operativa',
    id_centro     INT NOT NULL,
    CONSTRAINT fk_maquinaria_centro
        FOREIGN KEY (id_centro) REFERENCES centro_acopio(id_centro)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- 12) ruta
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
    trazado             LONGTEXT NULL,
    trazado_tipo        ENUM('calles','recta') NULL,
    trazado_firma       CHAR(64) NULL,
    trazado_servicio    VARCHAR(40) NULL,
    trazado_distancia_m INT NULL,
    trazado_duracion_s  INT NULL,
    trazado_en          DATETIME NULL,
    CONSTRAINT fk_ruta_zona
        FOREIGN KEY (id_zona) REFERENCES zona(id_zona)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Desde la migracion 017 la ruta guarda su trazado por calles (GeoJSON), el servicio que lo calculo y la firma de la
-- lista de paradas con la que se calculo; y sus contenedores van en ruta_contenedor.


-- ----------------------------------------------------------------------------
-- 12 bis) ruta_contenedor
-- Los contenedores de una ruta y su orden de visita (migracion 017). Un contenedor pertenece a una sola ruta
-- ACTIVA: lo valida la API. Si se borra la ruta o el contenedor, el vinculo se va con el.
-- ----------------------------------------------------------------------------
CREATE TABLE ruta_contenedor (
    id_ruta       INT NOT NULL,
    id_contenedor INT NOT NULL,
    orden         INT NOT NULL,
    PRIMARY KEY (id_ruta, id_contenedor),
    UNIQUE KEY uq_ruta_orden (id_ruta, orden),
    INDEX idx_rutacont_contenedor (id_contenedor),
    CONSTRAINT fk_rutacont_ruta
        FOREIGN KEY (id_ruta) REFERENCES ruta(id_ruta) ON DELETE CASCADE,
    CONSTRAINT fk_rutacont_contenedor
        FOREIGN KEY (id_contenedor) REFERENCES contenedor(id_contenedor) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- 13) recoleccion
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
-- 14) vaciado
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
-- 15) incidencia
-- El reporte que hace un vecino sobre un contenedor. Puede quedar asignado a
-- una cuadrilla para que lo atienda. Estados: abierta (recien reportada),
-- en_curso (asignada a una cuadrilla), resuelta y descartada (las dos finales).
-- Hasta la 2da entrega se llamaban reportada y en_proceso: la migracion 002 los
-- renombro para coincidir con la especificacion (RF-219, RF-227).
-- ----------------------------------------------------------------------------
CREATE TABLE incidencia (
    id_incidencia    INT AUTO_INCREMENT PRIMARY KEY,
    descripcion      VARCHAR(500) NOT NULL,
    tipo_problema    ENUM('desbordado','lleno','roto','residuo_no_permitido',
                          'ausente_o_mal_ubicado','otro')
                         NOT NULL DEFAULT 'otro',
    fecha_reporte    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_resolucion DATETIME NULL,
    estado           ENUM('abierta','en_curso','resuelta','descartada')
                         NOT NULL DEFAULT 'abierta',
    prioridad        ENUM('baja','media','alta') NOT NULL DEFAULT 'media',
    id_usuario       INT NOT NULL,
    id_contenedor    INT NOT NULL,
    id_cuadrilla     INT NULL,
    derivada_en      DATETIME NULL,
    derivada_nota    VARCHAR(200) NULL,
    CONSTRAINT fk_incidencia_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario),
    CONSTRAINT fk_incidencia_contenedor
        FOREIGN KEY (id_contenedor) REFERENCES contenedor(id_contenedor),
    CONSTRAINT fk_incidencia_cuadrilla
        FOREIGN KEY (id_cuadrilla) REFERENCES cuadrilla(id_cuadrilla)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- 15 bis) historial_incidencia
-- Cada cambio de una incidencia: su apertura, la asignacion a una cuadrilla, los
-- avances, el cambio de prioridad y el cierre. "estado" es el estado en que queda
-- la incidencia tras ese asiento. Es COMPOSICION respecto de la incidencia (ON
-- DELETE CASCADE) y es de SOLO AGREGADO (RNF-016): los triggers del final impiden
-- modificar o borrar un asiento. id_usuario es quien hizo el cambio (RNF-017).
-- Nace con las migraciones 002 y 003.
-- ----------------------------------------------------------------------------
CREATE TABLE historial_incidencia (
    id_registro   INT AUTO_INCREMENT PRIMARY KEY,
    id_incidencia INT      NOT NULL,
    fecha_hora    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado        ENUM('abierta','en_curso','resuelta','descartada') NOT NULL,
    observacion   VARCHAR(300) NULL,
    id_usuario    INT      NOT NULL,
    CONSTRAINT fk_histinc_incidencia
        FOREIGN KEY (id_incidencia) REFERENCES incidencia(id_incidencia)
        ON DELETE CASCADE,
    CONSTRAINT fk_histinc_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario),
    INDEX idx_histinc_incidencia_fecha (id_incidencia, fecha_hora)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- 16) migracion
-- Tabla TECNICA, fuera del modelo de clases. Guarda el nombre de cada archivo de
-- database/migraciones/ que ya se aplico a esta base, para que database/migrar.php
-- aplique solo los pendientes. Este schema.sql es siempre el estado FINAL: una base
-- creada desde cero ya trae todos los cambios, y por eso al final de este archivo
-- se registran todas las migraciones existentes (ver database/migraciones/LEEME.md).
-- ----------------------------------------------------------------------------
CREATE TABLE migracion (
    nombre      VARCHAR(120) NOT NULL PRIMARY KEY,
    aplicada_en DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- 17) intento_login
-- Tabla TECNICA, fuera del modelo de clases. Una fila por intento de iniciar
-- sesion (exitoso o no): es el registro de accesos y la base del limite de
-- intentos (5 fallos en 15 minutos bloquean ese email por un rato). El email
-- va normalizado. Nace con la migracion 001-intento-login.sql.
-- ----------------------------------------------------------------------------
CREATE TABLE intento_login (
    id_intento INT AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(120) NOT NULL,
    ip         VARCHAR(45)  NOT NULL,
    fecha_hora DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    exitoso    TINYINT(1)   NOT NULL DEFAULT 0,
    INDEX idx_intento_email_fecha (email, fecha_hora)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ----------------------------------------------------------------------------
-- Indices de apoyo
-- Sobre las columnas por las que el sistema filtra todo el tiempo. Las claves
-- foraneas ya crean su indice; estos son los de los filtros de pantalla.
-- ----------------------------------------------------------------------------
CREATE INDEX idx_contenedor_estado   ON contenedor(estado);
CREATE INDEX idx_camion_estado       ON camion(estado);
CREATE INDEX idx_maquinaria_estado   ON maquinaria(estado);
CREATE INDEX idx_usuario_estado      ON usuario(estado);
CREATE INDEX idx_solicitud_estado    ON solicitud_registro(estado);
CREATE INDEX idx_incidencia_estado   ON incidencia(estado);
CREATE INDEX idx_estadocont_fecha    ON estado_contenedor(id_contenedor, fecha_hora);

-- ----------------------------------------------------------------------------
-- Migraciones que este schema ya incluye (ver database/migraciones/LEEME.md).
-- ----------------------------------------------------------------------------
INSERT INTO migracion (nombre) VALUES
    ('001-intento-login.sql'),
    ('002-incidencias-historial.sql'),
    ('003-historial-solo-agregado.sql'),
    ('005-estado-pendiente.sql'),
    ('006-foto-incidencia.sql'),
    ('007-auditoria.sql'),
    ('008-limite-peticion.sql'),
    ('009-operario-centro-stock.sql'),
    ('010-stock-solo-agregado.sql'),
    ('011-notificaciones.sql'),
    ('014-zona-del-vecino.sql'),
    ('017-rutas-con-contenedores.sql'),
    ('018-incidencias-lleno-y-mantenimiento.sql'),
    ('019-solicitud-tipo-cuenta.sql'),
    ('020-cuadrilla-municipio.sql');

-- ----------------------------------------------------------------------------
-- Freno por IP a los pedidos publicos (OWASP A04). Nace con la migracion 008.
-- ----------------------------------------------------------------------------
CREATE TABLE limite_peticion (
    id_limite  BIGINT AUTO_INCREMENT PRIMARY KEY,
    clave      VARCHAR(80) NOT NULL,
    fecha_hora DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_limite_clave (clave, fecha_hora)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Auditoria (OWASP A09). Una fila por accion que cambia datos y sale bien, y por cada
-- acceso negado por permisos. No guarda el cuerpo de la peticion. La aplicacion solo
-- agrega y lee. Nace con la migracion 007.
-- ----------------------------------------------------------------------------
CREATE TABLE auditoria (
    id_auditoria BIGINT AUTO_INCREMENT PRIMARY KEY,
    fecha_hora   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_usuario   INT           NULL,
    rol          VARCHAR(20)   NULL,
    metodo       VARCHAR(6)    NOT NULL,
    ruta         VARCHAR(200)  NOT NULL,
    codigo       SMALLINT UNSIGNED NOT NULL,
    ip           VARCHAR(45)   NOT NULL,
    INDEX idx_auditoria_fecha (fecha_hora),
    INDEX idx_auditoria_usuario (id_usuario, fecha_hora)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Foto opcional de una incidencia (RF-222, RF-223). El archivo se guarda fuera de
-- la carpeta publica (almacen/incidencias/), con un nombre al azar y ya reconvertido
-- a JPEG sin metadatos; aca queda su ficha. Una por incidencia. Nace con la
-- migracion 006.
-- ----------------------------------------------------------------------------
CREATE TABLE foto_incidencia (
    id_foto       INT AUTO_INCREMENT PRIMARY KEY,
    id_incidencia INT      NOT NULL UNIQUE,
    archivo       CHAR(32) NOT NULL UNIQUE,
    bytes         INT      NOT NULL,
    ancho         SMALLINT UNSIGNED NOT NULL,
    alto          SMALLINT UNSIGNED NOT NULL,
    fecha_hora    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_foto_incidencia
        FOREIGN KEY (id_incidencia) REFERENCES incidencia(id_incidencia) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Historiales de solo agregado (RNF-016, RF-235). Nacen con la migracion 003.
-- Un asiento de historial_incidencia o de estado_contenedor no se puede modificar
-- ni borrar. Borrar la incidencia o el contenedor completos si se puede: el
-- ON DELETE CASCADE no dispara estos triggers.
-- ----------------------------------------------------------------------------
-- Parte 12 de las extras: recepcion en el centro, stock y estado de las maquinas.
CREATE TABLE recepcion (
    id_recepcion   INT AUTO_INCREMENT PRIMARY KEY,
    id_recoleccion INT      NOT NULL UNIQUE,
    id_centro      INT      NOT NULL,
    id_usuario     INT      NOT NULL,
    fecha_hora     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    observacion    VARCHAR(300) NULL,
    CONSTRAINT fk_recepcion_recoleccion
        FOREIGN KEY (id_recoleccion) REFERENCES recoleccion(id_recoleccion) ON DELETE CASCADE,
    CONSTRAINT fk_recepcion_centro
        FOREIGN KEY (id_centro) REFERENCES centro_acopio(id_centro) ON DELETE CASCADE,
    CONSTRAINT fk_recepcion_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE movimiento_stock (
    id_movimiento   INT AUTO_INCREMENT PRIMARY KEY,
    id_centro       INT           NOT NULL,
    id_tipo_residuo INT           NOT NULL,
    sentido         ENUM('entrada','salida') NOT NULL,
    motivo          ENUM('inventario_inicial','recepcion','venta','traslado','disposicion','correccion') NOT NULL,
    kg              DECIMAL(10,2) NOT NULL,
    id_recoleccion  INT           NULL,
    id_corregido    INT           NULL UNIQUE,
    id_usuario      INT           NOT NULL,
    fecha_hora      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    observacion     VARCHAR(200)  NULL,
    CONSTRAINT fk_movstock_centro
        FOREIGN KEY (id_centro) REFERENCES centro_acopio(id_centro) ON DELETE CASCADE,
    CONSTRAINT fk_movstock_tipo
        FOREIGN KEY (id_tipo_residuo) REFERENCES tipo_residuo(id_tipo_residuo),
    CONSTRAINT fk_movstock_recoleccion
        FOREIGN KEY (id_recoleccion) REFERENCES recoleccion(id_recoleccion) ON DELETE SET NULL,
    CONSTRAINT fk_movstock_corregido
        FOREIGN KEY (id_corregido) REFERENCES movimiento_stock(id_movimiento) ON DELETE CASCADE,
    CONSTRAINT fk_movstock_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario),
    INDEX idx_movstock_centro_tipo (id_centro, id_tipo_residuo),
    INDEX idx_movstock_centro_fecha (id_centro, fecha_hora)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE historial_maquinaria (
    id_registro   INT AUTO_INCREMENT PRIMARY KEY,
    id_maquinaria INT      NOT NULL,
    fecha_hora    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado        ENUM('operativa','mantenimiento','fuera_servicio') NOT NULL,
    observacion   VARCHAR(300) NULL,
    id_usuario    INT      NOT NULL,
    CONSTRAINT fk_histmaq_maquinaria
        FOREIGN KEY (id_maquinaria) REFERENCES maquinaria(id_maquinaria) ON DELETE CASCADE,
    CONSTRAINT fk_histmaq_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario),
    INDEX idx_histmaq_maquinaria_fecha (id_maquinaria, fecha_hora)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Parte 13 de las extras: avisos (la campanita).
CREATE TABLE notificacion (
    id_notificacion INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario      INT          NOT NULL,
    tipo            VARCHAR(40)  NOT NULL,
    titulo          VARCHAR(120) NOT NULL,
    mensaje         VARCHAR(300) NOT NULL,
    enlace          VARCHAR(120) NULL,
    leida           TINYINT(1)   NOT NULL DEFAULT 0,
    fecha_hora      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notificacion_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE,
    INDEX idx_notificacion_usuario (id_usuario, leida, fecha_hora)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TRIGGER trg_histinc_sin_update BEFORE UPDATE ON historial_incidencia
FOR EACH ROW SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 'El historial de una incidencia es de solo agregado';

CREATE TRIGGER trg_histinc_sin_delete BEFORE DELETE ON historial_incidencia
FOR EACH ROW SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 'El historial de una incidencia es de solo agregado';

CREATE TRIGGER trg_estadocont_sin_update BEFORE UPDATE ON estado_contenedor
FOR EACH ROW SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 'El historial de un contenedor es de solo agregado';

CREATE TRIGGER trg_estadocont_sin_delete BEFORE DELETE ON estado_contenedor
FOR EACH ROW SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 'El historial de un contenedor es de solo agregado';

CREATE TRIGGER trg_movstock_sin_update BEFORE UPDATE ON movimiento_stock
FOR EACH ROW SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 'Los movimientos de stock son de solo agregado';

CREATE TRIGGER trg_movstock_sin_delete BEFORE DELETE ON movimiento_stock
FOR EACH ROW SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 'Los movimientos de stock son de solo agregado';

CREATE TRIGGER trg_histmaq_sin_update BEFORE UPDATE ON historial_maquinaria
FOR EACH ROW SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 'El historial de una maquina es de solo agregado';

CREATE TRIGGER trg_histmaq_sin_delete BEFORE DELETE ON historial_maquinaria
FOR EACH ROW SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 'El historial de una maquina es de solo agregado';

-- ============================================================================
--  Fin del schema. 23 tablas del modelo de clases (15 + historial_incidencia + recepcion,
--  movimiento_stock, historial_maquinaria, notificacion y ruta_contenedor, de las extras)
--  mas las tecnicas migracion e intento_login.
-- ============================================================================
