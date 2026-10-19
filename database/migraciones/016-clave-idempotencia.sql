-- Claves de operacion (extras, parte 21): una operacion que la Cuadrilla guarda sin conexion y reenvia despues (registrar un
-- vaciado, registrar un avance de incidencia) lleva una clave unica (UUID). Si el mismo pedido llega dos veces, el servidor no lo
-- repite: devuelve la misma respuesta. "en_proceso" evita que dos pedidos iguales simultaneos se ejecuten a la vez. Un error
-- no se recuerda (se borra la clave) para que se pueda corregir y reintentar. No es parte del modelo de clases: es de soporte.
CREATE TABLE IF NOT EXISTS clave_idempotencia (
    clave      CHAR(36)     NOT NULL PRIMARY KEY,
    id_usuario INT          NOT NULL,
    operacion  VARCHAR(40)  NOT NULL,
    estado     ENUM('en_proceso','terminada') NOT NULL DEFAULT 'en_proceso',
    codigo     SMALLINT UNSIGNED NULL,
    respuesta  MEDIUMTEXT   NULL,
    creada     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_clave_idempotencia_creada (creada)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
