-- Parte 14 de las extras: las calificaciones no se editan ni se borran y los puntos son de solo agregado.
-- Las bajas en cascada (borrar la incidencia o el usuario completos) no disparan estos triggers.

DROP TRIGGER IF EXISTS trg_calificacion_sin_update;
CREATE TRIGGER trg_calificacion_sin_update BEFORE UPDATE ON calificacion
FOR EACH ROW SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 'Una calificacion no se edita';

DROP TRIGGER IF EXISTS trg_calificacion_sin_delete;
CREATE TRIGGER trg_calificacion_sin_delete BEFORE DELETE ON calificacion
FOR EACH ROW SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 'Una calificacion no se borra';

DROP TRIGGER IF EXISTS trg_punto_sin_update;
CREATE TRIGGER trg_punto_sin_update BEFORE UPDATE ON movimiento_punto
FOR EACH ROW SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 'Los puntos son de solo agregado';

DROP TRIGGER IF EXISTS trg_punto_sin_delete;
CREATE TRIGGER trg_punto_sin_delete BEFORE DELETE ON movimiento_punto
FOR EACH ROW SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 'Los puntos son de solo agregado';
