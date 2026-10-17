-- Distingue cuentas pendientes de activacion de cuentas dadas de baja.
ALTER TABLE usuario
    MODIFY estado ENUM('activo','inactivo','pendiente') NOT NULL DEFAULT 'activo';
