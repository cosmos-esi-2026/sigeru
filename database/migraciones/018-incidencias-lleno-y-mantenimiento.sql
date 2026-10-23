-- Incidencias con prioridad y derivacion a mantenimiento (plan de rutas para la defensa, bloque 7, 23/10/2026).
--   * Nuevo tipo de problema «lleno»: el contenedor esta lleno pero todavia no desborda (prioridad media). «desbordado» sigue siendo la mas urgente.
--   * Derivacion a mantenimiento: un contenedor roto o danado no lo recolecta el camion; el administrador avisa a otra gente (mantenimiento). Se guarda cuando se
--     derivo y la nota (a quien se llamo). El hecho tambien queda en historial_incidencia, que es de solo agregado.
-- El orden del ENUM tiene que ser el mismo que el de schema.sql (la huella de la base lo ve).

SET @tipo = (SELECT COLUMN_TYPE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'incidencia' AND COLUMN_NAME = 'tipo_problema');
SET @sql = IF(@tipo LIKE '%''lleno''%',
              'DO 0',
              'ALTER TABLE incidencia MODIFY COLUMN tipo_problema ENUM(''desbordado'',''lleno'',''roto'',''residuo_no_permitido'',''ausente_o_mal_ubicado'',''otro'') NOT NULL DEFAULT ''otro''');
PREPARE sentencia FROM @sql;
EXECUTE sentencia;
DEALLOCATE PREPARE sentencia;

SET @existe = (SELECT COUNT(*) FROM information_schema.COLUMNS
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'incidencia' AND COLUMN_NAME = 'derivada_en');
SET @sql = IF(@existe = 0,
              'ALTER TABLE incidencia ADD COLUMN derivada_en DATETIME NULL, ADD COLUMN derivada_nota VARCHAR(200) NULL',
              'DO 0');
PREPARE sentencia FROM @sql;
EXECUTE sentencia;
DEALLOCATE PREPARE sentencia;
