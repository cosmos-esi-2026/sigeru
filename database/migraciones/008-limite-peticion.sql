-- Freno por direccion IP a los pedidos publicos que cualquiera puede mandar sin tener cuenta (registro,
-- solicitud de registro, recuperar contrasena, crear contrasena con enlace). Sin esto una sola maquina podia
-- crear miles de cuentas o llenar la bandeja de solicitudes (OWASP A04). Cada pedido deja una fila; se cuentan
-- las de la ventana de tiempo y las viejas se borran solas.
CREATE TABLE IF NOT EXISTS limite_peticion (
    id_limite  BIGINT AUTO_INCREMENT PRIMARY KEY,
    clave      VARCHAR(80) NOT NULL,
    fecha_hora DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_limite_clave (clave, fecha_hora)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
