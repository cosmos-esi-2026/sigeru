-- Municipio (zona) de la cuadrilla (parte 26, 24/10/2026; hallazgo H-04 de la evaluacion heuristica).
-- Hasta aca la cuadrilla no sabia a que municipio pertenece y la sugerencia de asignacion emparejaba rutas y cuadrillas por posicion
-- (la ruta del Municipio B recibia la cuadrilla del A). Es opcional: una cuadrilla sin municipio puede cubrir cualquiera.

SET @existe = (SELECT COUNT(*) FROM information_schema.COLUMNS
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cuadrilla' AND COLUMN_NAME = 'id_zona');
SET @sql = IF(@existe = 0,
              'ALTER TABLE cuadrilla ADD COLUMN id_zona INT NULL AFTER estado',
              'DO 0');
PREPARE sentencia FROM @sql;
EXECUTE sentencia;
DEALLOCATE PREPARE sentencia;

SET @existe = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cuadrilla' AND CONSTRAINT_NAME = 'fk_cuadrilla_zona');
SET @sql = IF(@existe = 0,
              'ALTER TABLE cuadrilla ADD CONSTRAINT fk_cuadrilla_zona FOREIGN KEY (id_zona) REFERENCES zona(id_zona)',
              'DO 0');
PREPARE sentencia FROM @sql;
EXECUTE sentencia;
DEALLOCATE PREPARE sentencia;

-- Las cuadrillas del seed de defensa se llaman «Cuadrilla Municipio X» y la zona «Municipio X»: se vinculan solas. Si el nombre no coincide
-- con ninguna zona (otros datos), queda en NULL. Repetirla no cambia nada porque solo toca las que todavia no tienen municipio.
UPDATE cuadrilla q
    JOIN zona z ON q.nombre = CONCAT('Cuadrilla ', z.nombre)
SET q.id_zona = z.id_zona
WHERE q.id_zona IS NULL;
