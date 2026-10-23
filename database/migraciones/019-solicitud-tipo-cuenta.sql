-- Pedidos de acceso de cuadrillas (plan de rutas para la defensa, bloque 8, 23/10/2026).
-- Nadie se registra solo como cuadrilla: lo pide desde el login (tipo_cuenta = 'cuadrilla') diciendo en que cuadrilla trabaja,
-- y el administrador elige la cuadrilla real al aprobar. cuadrilla_indicada es solo lo que escribio la persona (texto libre, no se confia en el).

SET @existe = (SELECT COUNT(*) FROM information_schema.COLUMNS
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'solicitud_registro' AND COLUMN_NAME = 'tipo_cuenta');
SET @sql = IF(@existe = 0,
              'ALTER TABLE solicitud_registro ADD COLUMN tipo_cuenta ENUM(''vecino'',''cuadrilla'') NOT NULL DEFAULT ''vecino'', ADD COLUMN cuadrilla_indicada VARCHAR(80) NULL',
              'DO 0');
PREPARE sentencia FROM @sql;
EXECUTE sentencia;
DEALLOCATE PREPARE sentencia;
