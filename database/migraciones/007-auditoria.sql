-- Registro de auditoria (OWASP A09): quien hizo que. Cada accion que cambia algo (POST, PUT, DELETE) y sale bien,
-- y cada intento que el sistema niega por permisos (403), deja una fila. No se guarda el cuerpo de la peticion
-- (ahi viajan contrasenas y enlaces): solo el metodo, la ruta, el resultado, la persona y la direccion.
-- La aplicacion solo agrega y lee; no hay ninguna operacion que modifique o borre filas de esta tabla.
CREATE TABLE IF NOT EXISTS auditoria (
    id_auditoria BIGINT AUTO_INCREMENT PRIMARY KEY,
    fecha_hora   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_usuario   INT           NULL,
    rol          VARCHAR(20)   NULL,
    metodo       VARCHAR(6)    NOT NULL,
    ruta         VARCHAR(200)  NOT NULL,
    codigo       SMALLINT UNSIGNED NOT NULL,
    ip           VARCHAR(45)   NOT NULL,
    INDEX idx_auditoria_fecha (fecha_hora),
    INDEX idx_auditoria_usuario (id_usuario, fecha_hora)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
