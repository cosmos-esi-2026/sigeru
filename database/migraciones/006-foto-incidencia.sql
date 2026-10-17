-- Una foto opcional por incidencia (RF-222, RF-223). El archivo vive fuera de la carpeta publica
-- (almacen/) con un nombre al azar; aca queda solo su ficha. Si se borra la incidencia, la ficha se va en cascada.
CREATE TABLE IF NOT EXISTS foto_incidencia (
    id_foto       INT AUTO_INCREMENT PRIMARY KEY,
    id_incidencia INT      NOT NULL UNIQUE,
    archivo       CHAR(32) NOT NULL UNIQUE,
    bytes         INT      NOT NULL,
    ancho         SMALLINT UNSIGNED NOT NULL,
    alto          SMALLINT UNSIGNED NOT NULL,
    fecha_hora    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_foto_incidencia
        FOREIGN KEY (id_incidencia) REFERENCES incidencia(id_incidencia) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
