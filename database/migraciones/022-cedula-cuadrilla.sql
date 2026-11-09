-- Cedula de identidad (30/10/2026). La cuadrilla pide su acceso con la cedula y despues inicia sesion con cedula y contrasena.
-- Se guarda solo con digitos (7 u 8, el ultimo es el verificador), sin puntos ni guion. Es opcional en la tabla porque las cuentas
-- de los otros roles no la piden; la obligatoriedad para el rol Cuadrilla la exige la API. En usuario es unica: dos cuentas no
-- comparten cedula. En solicitud_registro solo lleva un indice: una persona rechazada puede volver a pedir.
-- Se puede repetir sin error.

SET @existe = (SELECT COUNT(*) FROM information_schema.COLUMNS
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuario' AND COLUMN_NAME = 'cedula');
SET @sql = IF(@existe = 0,
              'ALTER TABLE usuario ADD COLUMN cedula VARCHAR(8) NULL AFTER email',
              'DO 0');
PREPARE sentencia FROM @sql;
EXECUTE sentencia;
DEALLOCATE PREPARE sentencia;

SET @existe = (SELECT COUNT(*) FROM information_schema.STATISTICS
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuario' AND INDEX_NAME = 'uq_usuario_cedula');
SET @sql = IF(@existe = 0,
              'ALTER TABLE usuario ADD UNIQUE INDEX uq_usuario_cedula (cedula)',
              'DO 0');
PREPARE sentencia FROM @sql;
EXECUTE sentencia;
DEALLOCATE PREPARE sentencia;

SET @existe = (SELECT COUNT(*) FROM information_schema.COLUMNS
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'solicitud_registro' AND COLUMN_NAME = 'cedula');
SET @sql = IF(@existe = 0,
              'ALTER TABLE solicitud_registro ADD COLUMN cedula VARCHAR(8) NULL AFTER email',
              'DO 0');
PREPARE sentencia FROM @sql;
EXECUTE sentencia;
DEALLOCATE PREPARE sentencia;

SET @existe = (SELECT COUNT(*) FROM information_schema.STATISTICS
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'solicitud_registro' AND INDEX_NAME = 'idx_solicitud_cedula');
SET @sql = IF(@existe = 0,
              'ALTER TABLE solicitud_registro ADD INDEX idx_solicitud_cedula (cedula)',
              'DO 0');
PREPARE sentencia FROM @sql;
EXECUTE sentencia;
DEALLOCATE PREPARE sentencia;
