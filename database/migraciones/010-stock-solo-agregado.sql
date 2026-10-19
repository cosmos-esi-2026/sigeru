-- Parte 12 de las extras: los movimientos de stock y el historial de maquinas son de solo
-- agregado. Un error se corrige con un movimiento inverso, no borrando ni editando. Las bajas
-- en cascada (borrar el centro o la maquina completos) no disparan estos triggers.

DROP TRIGGER IF EXISTS trg_movstock_sin_update;
CREATE TRIGGER trg_movstock_sin_update BEFORE UPDATE ON movimiento_stock
FOR EACH ROW SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 'Los movimientos de stock son de solo agregado';

DROP TRIGGER IF EXISTS trg_movstock_sin_delete;
CREATE TRIGGER trg_movstock_sin_delete BEFORE DELETE ON movimiento_stock
FOR EACH ROW SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 'Los movimientos de stock son de solo agregado';

DROP TRIGGER IF EXISTS trg_histmaq_sin_update;
CREATE TRIGGER trg_histmaq_sin_update BEFORE UPDATE ON historial_maquinaria
FOR EACH ROW SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 'El historial de una maquina es de solo agregado';

DROP TRIGGER IF EXISTS trg_histmaq_sin_delete;
CREATE TRIGGER trg_histmaq_sin_delete BEFORE DELETE ON historial_maquinaria
FOR EACH ROW SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 'El historial de una maquina es de solo agregado';
