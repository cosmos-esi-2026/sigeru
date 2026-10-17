-- Registro de accesos y base del limite de intentos de login (parte 2 de la mejora de la 3ra entrega).
-- Tabla tecnica, fuera del modelo de clases: una fila por intento de iniciar sesion.
-- email va normalizado (minusculas, sin espacios) para que cambiar mayusculas no esquive el limite.
CREATE TABLE IF NOT EXISTS intento_login (
    id_intento INT AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(120) NOT NULL,
    ip         VARCHAR(45)  NOT NULL,
    fecha_hora DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    exitoso    TINYINT(1)   NOT NULL DEFAULT 0,
    INDEX idx_intento_email_fecha (email, fecha_hora)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
