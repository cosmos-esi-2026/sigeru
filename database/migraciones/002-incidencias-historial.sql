-- Incidencias: modelo y API (parte 3 de la mejora de la 3ra entrega).
-- 1) Estados alineados a la especificacion (RF-219, RF-227): abierta y en_curso reemplazan
--    a reportada y en_proceso. Se ensancha el ENUM, se convierten los datos y se angosta.
-- 2) Columna tipo_problema (RF-217).
-- 3) Tabla historial_incidencia (RF-232, RNF-016, RNF-017): la 16va clase del modelo. Cada
--    cambio de estado, avance o cambio de prioridad es una fila, con su autor y su fecha.
--    Es parte de la incidencia (composicion): si la incidencia se borra, su historial se va.

SET @viejos = (SELECT COUNT(*) FROM information_schema.COLUMNS
               WHERE TABLE_SCHEMA = DATABASE()
                 AND TABLE_NAME = 'incidencia' AND COLUMN_NAME = 'estado'
                 AND COLUMN_TYPE LIKE '%reportada%');
SET @sql = IF(@viejos > 0,
              'ALTER TABLE incidencia MODIFY estado ENUM(''reportada'',''abierta'',''en_proceso'',''en_curso'',''resuelta'',''descartada'') NOT NULL DEFAULT ''abierta''',
              'DO 0');
PREPARE sentencia FROM @sql;
EXECUTE sentencia;
DEALLOCATE PREPARE sentencia;

UPDATE incidencia SET estado = 'abierta' WHERE estado = 'reportada';
UPDATE incidencia SET estado = 'en_curso' WHERE estado = 'en_proceso';

SET @sql = IF(@viejos > 0,
              'ALTER TABLE incidencia MODIFY estado ENUM(''abierta'',''en_curso'',''resuelta'',''descartada'') NOT NULL DEFAULT ''abierta''',
              'DO 0');
PREPARE sentencia FROM @sql;
EXECUTE sentencia;
DEALLOCATE PREPARE sentencia;

SET @existe = (SELECT COUNT(*) FROM information_schema.COLUMNS
               WHERE TABLE_SCHEMA = DATABASE()
                 AND TABLE_NAME = 'incidencia' AND COLUMN_NAME = 'tipo_problema');
SET @sql = IF(@existe = 0,
              'ALTER TABLE incidencia ADD COLUMN tipo_problema ENUM(''desbordado'',''roto'',''residuo_no_permitido'',''ausente_o_mal_ubicado'',''otro'') NOT NULL DEFAULT ''otro'' AFTER descripcion',
              'DO 0');
PREPARE sentencia FROM @sql;
EXECUTE sentencia;
DEALLOCATE PREPARE sentencia;

CREATE TABLE IF NOT EXISTS historial_incidencia (
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

INSERT INTO historial_incidencia (id_incidencia, fecha_hora, estado, observacion, id_usuario)
SELECT i.id_incidencia, i.fecha_reporte, 'abierta', 'Incidencia registrada (asiento creado al migrar)', i.id_usuario
FROM incidencia i
WHERE NOT EXISTS (SELECT 1 FROM historial_incidencia h WHERE h.id_incidencia = i.id_incidencia);
