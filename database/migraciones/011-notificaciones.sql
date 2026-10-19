-- Parte 13 de las extras (19/10/2026): avisos dentro del sistema (la campanita) y por correo.
-- notificacion: un aviso para una persona (tipo, titulo, mensaje, enlace a la pantalla, leida).
-- usuario.avisos_por_correo: preferencia del vecino (por defecto, si).

CREATE TABLE IF NOT EXISTS notificacion (
    id_notificacion INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario      INT          NOT NULL,
    tipo            VARCHAR(40)  NOT NULL,
    titulo          VARCHAR(120) NOT NULL,
    mensaje         VARCHAR(300) NOT NULL,
    enlace          VARCHAR(120) NULL,
    leida           TINYINT(1)   NOT NULL DEFAULT 0,
    fecha_hora      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notificacion_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE,
    INDEX idx_notificacion_usuario (id_usuario, leida, fecha_hora)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @existe = (SELECT COUNT(*) FROM information_schema.COLUMNS
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuario' AND COLUMN_NAME = 'avisos_por_correo');
SET @sql = IF(@existe = 0,
              'ALTER TABLE usuario ADD COLUMN avisos_por_correo TINYINT(1) NOT NULL DEFAULT 1 AFTER estado',
              'DO 0');
PREPARE sentencia FROM @sql;
EXECUTE sentencia;
DEALLOCATE PREPARE sentencia;
