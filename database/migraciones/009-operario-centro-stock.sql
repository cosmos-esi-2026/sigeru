-- Parte 12 de las extras (18/10/2026): el Operario trabaja en un centro de acopio.
-- Agrega: el centro del Operario (usuario.id_centro), la recepcion de lo que trae cada
-- recoleccion, los movimientos de stock del centro (entradas y salidas, de solo agregado)
-- y el historial de estados de cada maquina. Los triggers de solo agregado van aparte, en
-- la 010, como en la 002 y la 003.

SET @existe = (SELECT COUNT(*) FROM information_schema.COLUMNS
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuario' AND COLUMN_NAME = 'id_centro');
SET @sql = IF(@existe = 0,
              'ALTER TABLE usuario ADD COLUMN id_centro INT NULL AFTER id_cuadrilla',
              'DO 0');
PREPARE sentencia FROM @sql;
EXECUTE sentencia;
DEALLOCATE PREPARE sentencia;

SET @existe = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuario' AND CONSTRAINT_NAME = 'fk_usuario_centro');
SET @sql = IF(@existe = 0,
              'ALTER TABLE usuario ADD CONSTRAINT fk_usuario_centro FOREIGN KEY (id_centro) REFERENCES centro_acopio(id_centro)',
              'DO 0');
PREPARE sentencia FROM @sql;
EXECUTE sentencia;
DEALLOCATE PREPARE sentencia;

CREATE TABLE IF NOT EXISTS recepcion (
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

CREATE TABLE IF NOT EXISTS movimiento_stock (
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

CREATE TABLE IF NOT EXISTS historial_maquinaria (
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
