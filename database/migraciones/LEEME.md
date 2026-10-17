# Migraciones de la base de datos

Cada cambio al esquema después de la 2da entrega es un archivo numerado en esta carpeta.
`schema.sql` sigue siendo el **estado final** (una base nueva se crea de una vez con él) y las
migraciones son el camino para llevar una base **ya existente** hasta ese estado sin perder datos.

## Cómo se aplican

    herramientas\php\php.exe sigeru\database\migrar.php            aplica las pendientes
    herramientas\php\php.exe sigeru\database\migrar.php --estado   lista cuáles faltan (sale con 1 si falta alguna)

Con Docker: `docker compose exec web php /var/www/html/sigeru/database/migrar.php`.
Usa las mismas variables de entorno que las APIs (`SIGERU_DB_HOST`, `SIGERU_DB_PORT`,
`SIGERU_DB_NAME`, `SIGERU_DB_USER`, `SIGERU_DB_PASS`) y, para pruebas, `SIGERU_MIGRACIONES`
con otra carpeta de archivos. Solo corre desde la terminal: por web responde 404.

## Cómo se escribe una migración

1. **Nombre**: `NNN-palabras-con-guiones.sql`, con tres dígitos, minúsculas y sin repetir
   número. Se aplican en orden alfabético. Ejemplo: `001-incidencias-historial.sql`.
2. **Tiene que poder repetirse sin error.** Si falla a la mitad, el archivo no se registra y se
   vuelve a correr entero. Para eso, `CREATE TABLE IF NOT EXISTS`, `INSERT IGNORE` y las
   guardas de abajo.
3. **Tiene que andar en MariaDB 10.4 (XAMPP) y en MySQL 8 (Docker).** No usar
   `ADD COLUMN IF NOT EXISTS` ni `CREATE INDEX IF NOT EXISTS` (solo existen en MariaDB), ni
   `DELIMITER`, ni procedimientos almacenados. Lo condicional se escribe con `information_schema`:

       SET @existe = (SELECT COUNT(*) FROM information_schema.COLUMNS
                      WHERE TABLE_SCHEMA = DATABASE()
                        AND TABLE_NAME = 'incidencia' AND COLUMN_NAME = 'tipo_problema');
       SET @sql = IF(@existe = 0,
                     'ALTER TABLE incidencia ADD COLUMN tipo_problema VARCHAR(40) NULL',
                     'DO 0');
       PREPARE sentencia FROM @sql;
       EXECUTE sentencia;
       DEALLOCATE PREPARE sentencia;

   Para un índice es lo mismo con `information_schema.STATISTICS`.
4. **Sin acentos ni eñes** en el SQL, como el resto de `database/`.

## Qué más hay que tocar en cada migración nueva

- `schema.sql`: dejarlo en el estado final (la columna o tabla nueva ya incluida) y **agregar el
  nombre del archivo** al `INSERT INTO migracion (nombre) VALUES (...)` del final, para que una
  base nueva no intente aplicar de nuevo lo que ya trae.
- `datos_prueba.sql`, si cambia lo que cargan los datos de ejemplo.
- `tests/unitarias.php` verifica que los nombres de `schema.sql` y los archivos de esta carpeta
  coincidan: si olvidás uno de los dos pasos, falla.
