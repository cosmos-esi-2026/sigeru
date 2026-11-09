-- Se quita la foto opcional de las incidencias (30/10/2026). El sistema ya no recibe ni guarda fotos.
-- Borra la tabla tecnica creada por la migracion 006; los archivos viejos de almacen/ quedan sin uso
-- y se pueden borrar a mano. Se puede repetir sin error.
DROP TABLE IF EXISTS foto_incidencia;
