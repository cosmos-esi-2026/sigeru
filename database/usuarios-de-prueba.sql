-- ============================================================================
--  SiGeRU 2026 - Usuarios de prueba para probar a mano (30/10/2026)
--
--  QUE HACE: borra TODOS los usuarios y deja solo estos 11. Clave de todos: 123.
--      admin@sigeru.uy        Administrador
--      vecino@sigeru.uy       Vecino
--      operario@sigeru.uy     Operario (Centro de Acopio Felipe Cardoso)
--      cuadrillaa@sigeru.uy   Cuadrilla Municipio A (cedula 41000019; B 41000025, C 41000031, CH 41000047, D 41000053, E 41000069, F 41000075, G 41000081)
--      cuadrillab@sigeru.uy   Cuadrilla Municipio B
--      cuadrillac@sigeru.uy   Cuadrilla Municipio C
--      cuadrillach@sigeru.uy  Cuadrilla Municipio CH
--      cuadrillad@sigeru.uy   Cuadrilla Municipio D
--      cuadrillae@sigeru.uy   Cuadrilla Municipio E
--      cuadrillaf@sigeru.uy   Cuadrilla Municipio F
--      cuadrillag@sigeru.uy   Cuadrilla Municipio G
--
--  COMO SE CORRE: doble clic en usuarios-de-prueba.cmd (con XAMPP andando). Se puede repetir.
--
--  CUIDADO:
--  - Son cuentas de PRUEBA con clave de 3 caracteres: el sistema la acepta al iniciar sesion, pero NO pasaria la politica
--    de claves al crear o cambiar una cuenta desde la pantalla de Usuarios (8 o mas). No usar en una instalacion real.
--  - Las pruebas automaticas (correr-tests.cmd) usan las cuentas del seed con clave sigeru2026: despues de este script
--    hay que volver a correr restaurar-base-demo.cmd antes de correrlas, y restaurar-base-defensa.cmd para la defensa.
--  - Los ids 1 a 6 se conservan a proposito: el historial del seed (incidencias, recepciones, stock, maquinaria) apunta
--    a esos usuarios y el historial es de solo agregado, no se puede reescribir. Por eso la «Cuadrilla C» (id 6)
--    figura como quien informo dos incidencias de ejemplo que en el seed eran de otro vecino.
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM notificacion;
DELETE FROM intento_login;
DELETE FROM usuario;

SET FOREIGN_KEY_CHECKS = 1;

-- Clave 123 (bcrypt). Las cuadrillas se buscan por nombre, asi que no depende de sus ids.
SET @clave = '$2y$10$llYRmEc2okCaRzbeIBYf.OiJwcy.JuGStwIBKLKEGryW3Gpj7nffe';

INSERT INTO usuario (id_usuario, nombre, apellido, email, password_hash, telefono, direccion, id_rol, id_cuadrilla, id_centro, estado) VALUES
(1,  'Admin',    'Prueba',    'admin@sigeru.uy',       @clave, NULL, NULL, 1, NULL, NULL, 'activo'),
(3,  'Vecino',   'Prueba',    'vecino@sigeru.uy',      @clave, NULL, NULL, 4, NULL, NULL, 'activo'),
(4,  'Operario', 'Prueba',    'operario@sigeru.uy',    @clave, NULL, NULL, 2, NULL, 1,    'activo');

INSERT INTO usuario (id_usuario, nombre, apellido, email, password_hash, telefono, direccion, id_rol, id_cuadrilla, id_centro, estado)
SELECT t.id, 'Cuadrilla', t.letra, CONCAT('cuadrilla', LOWER(t.letra), '@sigeru.uy'), @clave, NULL, NULL, 3,
       (SELECT q.id_cuadrilla FROM cuadrilla q WHERE q.nombre = CONCAT('Cuadrilla Municipio ', t.letra)), NULL, 'activo'
FROM (SELECT 2 AS id, 'A' AS letra UNION ALL SELECT 5, 'B' UNION ALL SELECT 6, 'C' UNION ALL SELECT 7, 'CH'
      UNION ALL SELECT 8, 'D' UNION ALL SELECT 9, 'E' UNION ALL SELECT 10, 'F' UNION ALL SELECT 11, 'G') AS t;

-- Cedulas de prueba (ficticias, con digito verificador valido), una por cuadrilla: A 4.100.001-9, B 4.100.002-5, C 4.100.003-1,
-- CH 4.100.004-7, D 4.100.005-3, E 4.100.006-9, F 4.100.007-5, G 4.100.008-1. Se entra con la cedula (sin puntos ni guion) y la clave.
UPDATE usuario u JOIN cuadrilla q ON q.id_cuadrilla = u.id_cuadrilla
SET u.cedula = CASE q.nombre
    WHEN 'Cuadrilla Municipio A' THEN '41000019' WHEN 'Cuadrilla Municipio B' THEN '41000025'
    WHEN 'Cuadrilla Municipio C' THEN '41000031' WHEN 'Cuadrilla Municipio CH' THEN '41000047'
    WHEN 'Cuadrilla Municipio D' THEN '41000053' WHEN 'Cuadrilla Municipio E' THEN '41000069'
    WHEN 'Cuadrilla Municipio F' THEN '41000075' WHEN 'Cuadrilla Municipio G' THEN '41000081' END
WHERE u.id_rol = 3;

-- Control: tienen que salir 11 usuarios y las 8 cuadrillas con su cuenta (id_cuadrilla distinto de NULL).
SELECT u.id_usuario, u.email, r.nombre AS rol, q.nombre AS cuadrilla, u.id_centro
FROM usuario u JOIN rol r ON r.id_rol = u.id_rol LEFT JOIN cuadrilla q ON q.id_cuadrilla = u.id_cuadrilla
ORDER BY u.id_usuario;

-- Control: todo esto tiene que dar 0 (si no, algo del historial apunta a un usuario que ya no existe).
SELECT 'incidencia' AS tabla, COUNT(*) AS huerfanos FROM incidencia WHERE id_usuario NOT IN (SELECT id_usuario FROM usuario)
UNION ALL SELECT 'historial_incidencia', COUNT(*) FROM historial_incidencia WHERE id_usuario NOT IN (SELECT id_usuario FROM usuario)
UNION ALL SELECT 'recepcion', COUNT(*) FROM recepcion WHERE id_usuario NOT IN (SELECT id_usuario FROM usuario)
UNION ALL SELECT 'movimiento_stock', COUNT(*) FROM movimiento_stock WHERE id_usuario NOT IN (SELECT id_usuario FROM usuario)
UNION ALL SELECT 'historial_maquinaria', COUNT(*) FROM historial_maquinaria WHERE id_usuario NOT IN (SELECT id_usuario FROM usuario);
