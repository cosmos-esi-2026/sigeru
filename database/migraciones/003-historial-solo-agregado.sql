-- Historial de solo agregado (RNF-016, RF-235), parte 3 de la mejora de la 3ra entrega.
-- Ningun asiento de historial_incidencia ni de estado_contenedor puede modificarse ni
-- borrarse una vez registrado: la propia base lo impide, aunque alguien entre por fuera
-- de la API. Las bajas en cascada (borrar el contenedor o la incidencia completos) no
-- disparan estos triggers, asi que la composicion sigue funcionando.
-- Va aparte de la 002 para que, si un servidor no deja crear triggers, el resto de las
-- incidencias igual quede instalado.

DROP TRIGGER IF EXISTS trg_histinc_sin_update;
CREATE TRIGGER trg_histinc_sin_update BEFORE UPDATE ON historial_incidencia
FOR EACH ROW SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 'El historial de una incidencia es de solo agregado';

DROP TRIGGER IF EXISTS trg_histinc_sin_delete;
CREATE TRIGGER trg_histinc_sin_delete BEFORE DELETE ON historial_incidencia
FOR EACH ROW SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 'El historial de una incidencia es de solo agregado';

DROP TRIGGER IF EXISTS trg_estadocont_sin_update;
CREATE TRIGGER trg_estadocont_sin_update BEFORE UPDATE ON estado_contenedor
FOR EACH ROW SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 'El historial de un contenedor es de solo agregado';

DROP TRIGGER IF EXISTS trg_estadocont_sin_delete;
CREATE TRIGGER trg_estadocont_sin_delete BEFORE DELETE ON estado_contenedor
FOR EACH ROW SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 'El historial de un contenedor es de solo agregado';
