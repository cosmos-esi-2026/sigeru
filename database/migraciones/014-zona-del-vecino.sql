-- Parte 15 de las extras (19/10/2026): la zona del vecino, para decirle cuando pasa el camion por su zona.
-- Es opcional (el vecino la elige y la puede cambiar). Solo la usa el Inicio del vecino.

SET @existe = (SELECT COUNT(*) FROM information_schema.COLUMNS
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuario' AND COLUMN_NAME = 'id_zona');
SET @sql = IF(@existe = 0,
              'ALTER TABLE usuario ADD COLUMN id_zona INT NULL AFTER id_centro',
              'DO 0');
PREPARE sentencia FROM @sql;
EXECUTE sentencia;
DEALLOCATE PREPARE sentencia;

SET @existe = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuario' AND CONSTRAINT_NAME = 'fk_usuario_zona');
SET @sql = IF(@existe = 0,
              'ALTER TABLE usuario ADD CONSTRAINT fk_usuario_zona FOREIGN KEY (id_zona) REFERENCES zona(id_zona)',
              'DO 0');
PREPARE sentencia FROM @sql;
EXECUTE sentencia;
DEALLOCATE PREPARE sentencia;
