-- Rutas con contenedores (plan de rutas para la defensa, bloque 2, 22/10/2026): hasta ahora una ruta era solo una etiqueta (nombre, dia, hora y zona) y
-- no sabia que contenedores recorre. Esta migracion agrega:
--   * ruta_contenedor: los contenedores de una ruta y su orden de visita. Un contenedor pertenece a una sola ruta ACTIVA (eso lo valida la API, no la base);
--     si se borra la ruta o el contenedor, el vinculo se va con el (ON DELETE CASCADE: es solo un vinculo, no un hecho que haya que conservar).
--   * en ruta: el trazado por calles (GeoJSON), de donde salio (calles o linea recta), la firma de la lista de paradas con la que se calculo
--     (si la lista cambia, hay que recalcular), el servicio usado, la distancia y duracion estimadas, y cuando se calculo.
-- Es una tabla mas del modelo de clases (la 23).

CREATE TABLE IF NOT EXISTS ruta_contenedor (
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

SET @existe = (SELECT COUNT(*) FROM information_schema.COLUMNS
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ruta' AND COLUMN_NAME = 'trazado');
SET @sql = IF(@existe = 0,
              'ALTER TABLE ruta
                   ADD COLUMN trazado LONGTEXT NULL,
                   ADD COLUMN trazado_tipo ENUM(''calles'',''recta'') NULL,
                   ADD COLUMN trazado_firma CHAR(64) NULL,
                   ADD COLUMN trazado_servicio VARCHAR(40) NULL,
                   ADD COLUMN trazado_distancia_m INT NULL,
                   ADD COLUMN trazado_duracion_s INT NULL,
                   ADD COLUMN trazado_en DATETIME NULL',
              'DO 0');
PREPARE sentencia FROM @sql;
EXECUTE sentencia;
DEALLOCATE PREPARE sentencia;
