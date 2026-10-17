-- Enlaces de un solo uso para activar cuentas y renovar contrasenas.
CREATE TABLE IF NOT EXISTS enlace_clave (
    id_enlace INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    vence_en DATETIME NOT NULL,
    CONSTRAINT fk_enlace_usuario FOREIGN KEY (id_usuario)
        REFERENCES usuario(id_usuario) ON DELETE CASCADE,
    INDEX idx_enlace_usuario (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
