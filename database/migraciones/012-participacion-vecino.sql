-- Parte 14 de las extras (19/10/2026): participacion del vecino.
-- calificacion: el vecino que informo califica la atencion de un reclamo resuelto (1 a 5, una sola vez por reclamo).
-- movimiento_punto: puntos del vecino, de solo agregado (los triggers van aparte, en la 013). Se suman solo al
-- RESOLVERSE el reclamo y al calificar; un reclamo descartado no suma. Las insignias no se guardan: se calculan.

CREATE TABLE IF NOT EXISTS calificacion (
    id_calificacion INT AUTO_INCREMENT PRIMARY KEY,
    id_incidencia   INT          NOT NULL UNIQUE,
    id_usuario      INT          NOT NULL,
    puntaje         TINYINT      NOT NULL,
    comentario      VARCHAR(300) NULL,
    fecha_hora      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_calificacion_puntaje CHECK (puntaje BETWEEN 1 AND 5),
    CONSTRAINT fk_calificacion_incidencia
        FOREIGN KEY (id_incidencia) REFERENCES incidencia(id_incidencia) ON DELETE CASCADE,
    CONSTRAINT fk_calificacion_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS movimiento_punto (
    id_movimiento INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario    INT      NOT NULL,
    id_incidencia INT      NOT NULL,
    motivo        ENUM('reclamo_resuelto','calificacion') NOT NULL,
    puntos        SMALLINT NOT NULL,
    fecha_hora    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_punto_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE,
    CONSTRAINT fk_punto_incidencia
        FOREIGN KEY (id_incidencia) REFERENCES incidencia(id_incidencia) ON DELETE CASCADE,
    UNIQUE KEY uq_punto_incidencia_motivo (id_incidencia, motivo),
    INDEX idx_punto_usuario (id_usuario, fecha_hora)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
