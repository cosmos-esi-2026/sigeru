-- ============================================================================
--  SiGeRU 2026 - Dump de la estructura de la base de datos
--  Archivo: dump_estructura.sql
--  Que es : el volcado que pide la letra ("Dump de la estructura de la BD"),
--           tal como lo escribe el motor (solo sin los AUTO_INCREMENT).
--
--  ESTE ARCHIVO NO SE EDITA A MANO. Se regenera con:
--      herramientas\xampp\mysql\bin\mysqldump.exe -u root --no-data
--          --skip-comments --skip-set-charset sigeru
--  se le quitan los ' AUTO_INCREMENT=N' (cuantas filas se crearon alguna vez:
--  cambian con cada corrida de pruebas y no son estructura) y se le vuelve
--  a poner esta cabecera.
--
--  El DDL comentado, con las decisiones de modelado explicadas una por una,
--  esta en schema.sql: ese es el archivo que se lee. Este es el volcado.
--
--  Actualizado el 09/09/2026: pasa de las 7 tablas de la 1ra entrega a las 15
--  del modelo vigente (las 14 del pasaje a tablas mas maquinaria, ver la
--  Adenda de Alcance). Hasta hoy seguia siendo el volcado de julio, y era la
--  ultima pieza de la base que quedaba desactualizada.
--
--  Actualizado el 12/10/2026: pasa de 15 a 16 tablas del modelo (suma
--  historial_incidencia, ver programacion-full-stack/ampliacion-incidencias-3ra.md),
--  con las columnas y los estados nuevos de incidencia y los cuatro triggers que
--  hacen de solo agregado a los dos historiales. Mas las tablas tecnicas migracion
--  e intento_login.
--
--  Actualizado el 17/10/2026: pasa de 18 a 22 tablas fisicas. Suma las cuatro
--  tablas tecnicas de las migraciones 004 a 008 (enlace_clave, foto_incidencia,
--  auditoria y limite_peticion) y el estado 'pendiente' de usuario (005). Las
--  16 tablas del modelo no cambian. Desde esta version va sin AUTO_INCREMENT.
--
--  Actualizado el 19/10/2026 (extras, parte 12): pasa de 22 a 25 tablas fisicas. Suma
--  recepcion, movimiento_stock e historial_maquinaria (migraciones 009 y 010), la
--  columna usuario.id_centro y los cuatro triggers de solo agregado de las dos
--  ultimas. Las tablas del modelo pasan de 16 a 19.
--
--  Actualizado el 19/10/2026 (extras, parte 13): pasa de 25 a 26 tablas fisicas. Suma
--  notificacion (migracion 011) y la columna usuario.avisos_por_correo. Las
--  tablas del modelo pasan de 19 a 20.
--
--  Actualizado el 19/10/2026 (extras, parte 14): pasa de 26 a 28 tablas fisicas. Suma
--  calificacion y movimiento_punto (migraciones 012 y 013) y sus cuatro triggers
--  de solo agregado. Las tablas del modelo pasan de 20 a 22.
--
--  Actualizado el 19/10/2026 (extras, parte 15): la tabla usuario suma id_zona
--  (la zona que elige el vecino; migracion 014). Siguen 28 tablas fisicas.
-- ============================================================================

/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `auditoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `auditoria` (
  `id_auditoria` bigint(20) NOT NULL AUTO_INCREMENT,
  `fecha_hora` datetime NOT NULL DEFAULT current_timestamp(),
  `id_usuario` int(11) DEFAULT NULL,
  `rol` varchar(20) DEFAULT NULL,
  `metodo` varchar(6) NOT NULL,
  `ruta` varchar(200) NOT NULL,
  `codigo` smallint(5) unsigned NOT NULL,
  `ip` varchar(45) NOT NULL,
  PRIMARY KEY (`id_auditoria`),
  KEY `idx_auditoria_fecha` (`fecha_hora`),
  KEY `idx_auditoria_usuario` (`id_usuario`,`fecha_hora`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `calificacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `calificacion` (
  `id_calificacion` int(11) NOT NULL AUTO_INCREMENT,
  `id_incidencia` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `puntaje` tinyint(4) NOT NULL,
  `comentario` varchar(300) DEFAULT NULL,
  `fecha_hora` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_calificacion`),
  UNIQUE KEY `id_incidencia` (`id_incidencia`),
  KEY `fk_calificacion_usuario` (`id_usuario`),
  CONSTRAINT `fk_calificacion_incidencia` FOREIGN KEY (`id_incidencia`) REFERENCES `incidencia` (`id_incidencia`) ON DELETE CASCADE,
  CONSTRAINT `fk_calificacion_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE,
  CONSTRAINT `chk_calificacion_puntaje` CHECK (`puntaje` between 1 and 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_calificacion_sin_update BEFORE UPDATE ON calificacion
FOR EACH ROW SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 'Una calificacion no se edita' */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_calificacion_sin_delete BEFORE DELETE ON calificacion
FOR EACH ROW SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 'Una calificacion no se borra' */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
DROP TABLE IF EXISTS `camion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `camion` (
  `id_camion` int(11) NOT NULL AUTO_INCREMENT,
  `matricula` varchar(20) NOT NULL,
  `marca` varchar(60) DEFAULT NULL,
  `modelo` varchar(60) DEFAULT NULL,
  `capacidad` int(11) DEFAULT NULL COMMENT 'Capacidad de carga en kg',
  `estado` enum('operativo','en_ruta','mantenimiento','fuera_servicio') NOT NULL DEFAULT 'operativo',
  PRIMARY KEY (`id_camion`),
  UNIQUE KEY `matricula` (`matricula`),
  KEY `idx_camion_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `centro_acopio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `centro_acopio` (
  `id_centro` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(120) NOT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `latitud` decimal(10,7) DEFAULT NULL,
  `longitud` decimal(10,7) DEFAULT NULL,
  `capacidad` int(11) DEFAULT NULL COMMENT 'Capacidad en kg',
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  PRIMARY KEY (`id_centro`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contenedor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contenedor` (
  `id_contenedor` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(30) NOT NULL,
  `direccion` varchar(200) NOT NULL,
  `latitud` decimal(10,7) DEFAULT NULL,
  `longitud` decimal(10,7) DEFAULT NULL,
  `capacidad` int(11) DEFAULT NULL COMMENT 'Capacidad en litros',
  `estado` enum('disponible','lleno','mantenimiento','retirado') NOT NULL DEFAULT 'disponible',
  `id_zona` int(11) NOT NULL,
  `id_tipo_residuo` int(11) NOT NULL,
  PRIMARY KEY (`id_contenedor`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `fk_contenedor_zona` (`id_zona`),
  KEY `fk_contenedor_tipo` (`id_tipo_residuo`),
  KEY `idx_contenedor_estado` (`estado`),
  CONSTRAINT `fk_contenedor_tipo` FOREIGN KEY (`id_tipo_residuo`) REFERENCES `tipo_residuo` (`id_tipo_residuo`),
  CONSTRAINT `fk_contenedor_zona` FOREIGN KEY (`id_zona`) REFERENCES `zona` (`id_zona`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cuadrilla`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cuadrilla` (
  `id_cuadrilla` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) NOT NULL,
  `turno` enum('matutino','vespertino','nocturno') NOT NULL DEFAULT 'matutino',
  `estado` enum('activa','inactiva') NOT NULL DEFAULT 'activa',
  PRIMARY KEY (`id_cuadrilla`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `enlace_clave`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `enlace_clave` (
  `id_enlace` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `vence_en` datetime NOT NULL,
  PRIMARY KEY (`id_enlace`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `idx_enlace_usuario` (`id_usuario`),
  CONSTRAINT `fk_enlace_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `estado_contenedor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `estado_contenedor` (
  `id_registro` int(11) NOT NULL AUTO_INCREMENT,
  `id_contenedor` int(11) NOT NULL,
  `fecha_hora` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` enum('disponible','lleno','mantenimiento','retirado') NOT NULL,
  `observacion` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id_registro`),
  KEY `idx_estadocont_fecha` (`id_contenedor`,`fecha_hora`),
  CONSTRAINT `fk_estadocont_contenedor` FOREIGN KEY (`id_contenedor`) REFERENCES `contenedor` (`id_contenedor`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_estadocont_sin_update BEFORE UPDATE ON estado_contenedor
FOR EACH ROW SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 'El historial de un contenedor es de solo agregado' */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_estadocont_sin_delete BEFORE DELETE ON estado_contenedor
FOR EACH ROW SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 'El historial de un contenedor es de solo agregado' */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
DROP TABLE IF EXISTS `foto_incidencia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `foto_incidencia` (
  `id_foto` int(11) NOT NULL AUTO_INCREMENT,
  `id_incidencia` int(11) NOT NULL,
  `archivo` char(32) NOT NULL,
  `bytes` int(11) NOT NULL,
  `ancho` smallint(5) unsigned NOT NULL,
  `alto` smallint(5) unsigned NOT NULL,
  `fecha_hora` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_foto`),
  UNIQUE KEY `id_incidencia` (`id_incidencia`),
  UNIQUE KEY `archivo` (`archivo`),
  CONSTRAINT `fk_foto_incidencia` FOREIGN KEY (`id_incidencia`) REFERENCES `incidencia` (`id_incidencia`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `historial_incidencia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `historial_incidencia` (
  `id_registro` int(11) NOT NULL AUTO_INCREMENT,
  `id_incidencia` int(11) NOT NULL,
  `fecha_hora` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` enum('abierta','en_curso','resuelta','descartada') NOT NULL,
  `observacion` varchar(300) DEFAULT NULL,
  `id_usuario` int(11) NOT NULL,
  PRIMARY KEY (`id_registro`),
  KEY `fk_histinc_usuario` (`id_usuario`),
  KEY `idx_histinc_incidencia_fecha` (`id_incidencia`,`fecha_hora`),
  CONSTRAINT `fk_histinc_incidencia` FOREIGN KEY (`id_incidencia`) REFERENCES `incidencia` (`id_incidencia`) ON DELETE CASCADE,
  CONSTRAINT `fk_histinc_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_histinc_sin_update BEFORE UPDATE ON historial_incidencia
FOR EACH ROW SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 'El historial de una incidencia es de solo agregado' */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_histinc_sin_delete BEFORE DELETE ON historial_incidencia
FOR EACH ROW SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 'El historial de una incidencia es de solo agregado' */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
DROP TABLE IF EXISTS `historial_maquinaria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `historial_maquinaria` (
  `id_registro` int(11) NOT NULL AUTO_INCREMENT,
  `id_maquinaria` int(11) NOT NULL,
  `fecha_hora` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` enum('operativa','mantenimiento','fuera_servicio') NOT NULL,
  `observacion` varchar(300) DEFAULT NULL,
  `id_usuario` int(11) NOT NULL,
  PRIMARY KEY (`id_registro`),
  KEY `fk_histmaq_usuario` (`id_usuario`),
  KEY `idx_histmaq_maquinaria_fecha` (`id_maquinaria`,`fecha_hora`),
  CONSTRAINT `fk_histmaq_maquinaria` FOREIGN KEY (`id_maquinaria`) REFERENCES `maquinaria` (`id_maquinaria`) ON DELETE CASCADE,
  CONSTRAINT `fk_histmaq_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_histmaq_sin_update BEFORE UPDATE ON historial_maquinaria
FOR EACH ROW SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 'El historial de una maquina es de solo agregado' */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_histmaq_sin_delete BEFORE DELETE ON historial_maquinaria
FOR EACH ROW SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 'El historial de una maquina es de solo agregado' */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
DROP TABLE IF EXISTS `incidencia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `incidencia` (
  `id_incidencia` int(11) NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(500) NOT NULL,
  `tipo_problema` enum('desbordado','roto','residuo_no_permitido','ausente_o_mal_ubicado','otro') NOT NULL DEFAULT 'otro',
  `fecha_reporte` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_resolucion` datetime DEFAULT NULL,
  `estado` enum('abierta','en_curso','resuelta','descartada') NOT NULL DEFAULT 'abierta',
  `prioridad` enum('baja','media','alta') NOT NULL DEFAULT 'media',
  `id_usuario` int(11) NOT NULL,
  `id_contenedor` int(11) NOT NULL,
  `id_cuadrilla` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_incidencia`),
  KEY `fk_incidencia_usuario` (`id_usuario`),
  KEY `fk_incidencia_contenedor` (`id_contenedor`),
  KEY `fk_incidencia_cuadrilla` (`id_cuadrilla`),
  KEY `idx_incidencia_estado` (`estado`),
  CONSTRAINT `fk_incidencia_contenedor` FOREIGN KEY (`id_contenedor`) REFERENCES `contenedor` (`id_contenedor`),
  CONSTRAINT `fk_incidencia_cuadrilla` FOREIGN KEY (`id_cuadrilla`) REFERENCES `cuadrilla` (`id_cuadrilla`),
  CONSTRAINT `fk_incidencia_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `intento_login`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `intento_login` (
  `id_intento` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(120) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `fecha_hora` datetime NOT NULL DEFAULT current_timestamp(),
  `exitoso` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_intento`),
  KEY `idx_intento_email_fecha` (`email`,`fecha_hora`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `limite_peticion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `limite_peticion` (
  `id_limite` bigint(20) NOT NULL AUTO_INCREMENT,
  `clave` varchar(80) NOT NULL,
  `fecha_hora` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_limite`),
  KEY `idx_limite_clave` (`clave`,`fecha_hora`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maquinaria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maquinaria` (
  `id_maquinaria` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(30) NOT NULL COMMENT 'Codigo patrimonial del equipo',
  `tipo` enum('compactadora','pala_cargadora','cinta_clasificadora','retroexcavadora','montacargas','otra') NOT NULL DEFAULT 'otra',
  `marca` varchar(60) DEFAULT NULL,
  `modelo` varchar(60) DEFAULT NULL,
  `estado` enum('operativa','mantenimiento','fuera_servicio') NOT NULL DEFAULT 'operativa',
  `id_centro` int(11) NOT NULL,
  PRIMARY KEY (`id_maquinaria`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `fk_maquinaria_centro` (`id_centro`),
  KEY `idx_maquinaria_estado` (`estado`),
  CONSTRAINT `fk_maquinaria_centro` FOREIGN KEY (`id_centro`) REFERENCES `centro_acopio` (`id_centro`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migracion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migracion` (
  `nombre` varchar(120) NOT NULL,
  `aplicada_en` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `movimiento_punto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `movimiento_punto` (
  `id_movimiento` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `id_incidencia` int(11) NOT NULL,
  `motivo` enum('reclamo_resuelto','calificacion') NOT NULL,
  `puntos` smallint(6) NOT NULL,
  `fecha_hora` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_movimiento`),
  UNIQUE KEY `uq_punto_incidencia_motivo` (`id_incidencia`,`motivo`),
  KEY `idx_punto_usuario` (`id_usuario`,`fecha_hora`),
  CONSTRAINT `fk_punto_incidencia` FOREIGN KEY (`id_incidencia`) REFERENCES `incidencia` (`id_incidencia`) ON DELETE CASCADE,
  CONSTRAINT `fk_punto_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_punto_sin_update BEFORE UPDATE ON movimiento_punto
FOR EACH ROW SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 'Los puntos son de solo agregado' */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_punto_sin_delete BEFORE DELETE ON movimiento_punto
FOR EACH ROW SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 'Los puntos son de solo agregado' */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
DROP TABLE IF EXISTS `movimiento_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `movimiento_stock` (
  `id_movimiento` int(11) NOT NULL AUTO_INCREMENT,
  `id_centro` int(11) NOT NULL,
  `id_tipo_residuo` int(11) NOT NULL,
  `sentido` enum('entrada','salida') NOT NULL,
  `motivo` enum('inventario_inicial','recepcion','venta','traslado','disposicion','correccion') NOT NULL,
  `kg` decimal(10,2) NOT NULL,
  `id_recoleccion` int(11) DEFAULT NULL,
  `id_corregido` int(11) DEFAULT NULL,
  `id_usuario` int(11) NOT NULL,
  `fecha_hora` datetime NOT NULL DEFAULT current_timestamp(),
  `observacion` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id_movimiento`),
  UNIQUE KEY `id_corregido` (`id_corregido`),
  KEY `fk_movstock_tipo` (`id_tipo_residuo`),
  KEY `fk_movstock_recoleccion` (`id_recoleccion`),
  KEY `fk_movstock_usuario` (`id_usuario`),
  KEY `idx_movstock_centro_tipo` (`id_centro`,`id_tipo_residuo`),
  KEY `idx_movstock_centro_fecha` (`id_centro`,`fecha_hora`),
  CONSTRAINT `fk_movstock_centro` FOREIGN KEY (`id_centro`) REFERENCES `centro_acopio` (`id_centro`) ON DELETE CASCADE,
  CONSTRAINT `fk_movstock_corregido` FOREIGN KEY (`id_corregido`) REFERENCES `movimiento_stock` (`id_movimiento`) ON DELETE CASCADE,
  CONSTRAINT `fk_movstock_recoleccion` FOREIGN KEY (`id_recoleccion`) REFERENCES `recoleccion` (`id_recoleccion`) ON DELETE SET NULL,
  CONSTRAINT `fk_movstock_tipo` FOREIGN KEY (`id_tipo_residuo`) REFERENCES `tipo_residuo` (`id_tipo_residuo`),
  CONSTRAINT `fk_movstock_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_movstock_sin_update BEFORE UPDATE ON movimiento_stock
FOR EACH ROW SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 'Los movimientos de stock son de solo agregado' */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_movstock_sin_delete BEFORE DELETE ON movimiento_stock
FOR EACH ROW SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 'Los movimientos de stock son de solo agregado' */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
DROP TABLE IF EXISTS `notificacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notificacion` (
  `id_notificacion` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `tipo` varchar(40) NOT NULL,
  `titulo` varchar(120) NOT NULL,
  `mensaje` varchar(300) NOT NULL,
  `enlace` varchar(120) DEFAULT NULL,
  `leida` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_hora` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_notificacion`),
  KEY `idx_notificacion_usuario` (`id_usuario`,`leida`,`fecha_hora`),
  CONSTRAINT `fk_notificacion_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `recepcion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recepcion` (
  `id_recepcion` int(11) NOT NULL AUTO_INCREMENT,
  `id_recoleccion` int(11) NOT NULL,
  `id_centro` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `fecha_hora` datetime NOT NULL DEFAULT current_timestamp(),
  `observacion` varchar(300) DEFAULT NULL,
  PRIMARY KEY (`id_recepcion`),
  UNIQUE KEY `id_recoleccion` (`id_recoleccion`),
  KEY `fk_recepcion_centro` (`id_centro`),
  KEY `fk_recepcion_usuario` (`id_usuario`),
  CONSTRAINT `fk_recepcion_centro` FOREIGN KEY (`id_centro`) REFERENCES `centro_acopio` (`id_centro`) ON DELETE CASCADE,
  CONSTRAINT `fk_recepcion_recoleccion` FOREIGN KEY (`id_recoleccion`) REFERENCES `recoleccion` (`id_recoleccion`) ON DELETE CASCADE,
  CONSTRAINT `fk_recepcion_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `recoleccion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recoleccion` (
  `id_recoleccion` int(11) NOT NULL AUTO_INCREMENT,
  `fecha_hora_inicio` datetime NOT NULL,
  `fecha_hora_fin` datetime DEFAULT NULL,
  `estado` enum('planificada','en_curso','finalizada','cancelada') NOT NULL DEFAULT 'planificada',
  `observaciones` varchar(300) DEFAULT NULL,
  `id_ruta` int(11) NOT NULL,
  `id_camion` int(11) NOT NULL,
  `id_cuadrilla` int(11) NOT NULL,
  `id_centro` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_recoleccion`),
  KEY `fk_recoleccion_ruta` (`id_ruta`),
  KEY `fk_recoleccion_camion` (`id_camion`),
  KEY `fk_recoleccion_cuadrilla` (`id_cuadrilla`),
  KEY `fk_recoleccion_centro` (`id_centro`),
  CONSTRAINT `fk_recoleccion_camion` FOREIGN KEY (`id_camion`) REFERENCES `camion` (`id_camion`),
  CONSTRAINT `fk_recoleccion_centro` FOREIGN KEY (`id_centro`) REFERENCES `centro_acopio` (`id_centro`),
  CONSTRAINT `fk_recoleccion_cuadrilla` FOREIGN KEY (`id_cuadrilla`) REFERENCES `cuadrilla` (`id_cuadrilla`),
  CONSTRAINT `fk_recoleccion_ruta` FOREIGN KEY (`id_ruta`) REFERENCES `ruta` (`id_ruta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rol`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rol` (
  `id_rol` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id_rol`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ruta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ruta` (
  `id_ruta` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) NOT NULL,
  `dia_semana` enum('lunes','martes','miercoles','jueves','viernes','sabado','domingo') NOT NULL,
  `hora_inicio` time NOT NULL,
  `estado` enum('activa','inactiva') NOT NULL DEFAULT 'activa',
  `id_zona` int(11) NOT NULL,
  PRIMARY KEY (`id_ruta`),
  KEY `fk_ruta_zona` (`id_zona`),
  CONSTRAINT `fk_ruta_zona` FOREIGN KEY (`id_zona`) REFERENCES `zona` (`id_zona`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `solicitud_registro`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `solicitud_registro` (
  `id_solicitud` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) NOT NULL,
  `apellido` varchar(80) NOT NULL,
  `email` varchar(120) NOT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `estado` enum('pendiente','aprobada','rechazada') NOT NULL DEFAULT 'pendiente',
  `fecha_solicitud` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_solicitud`),
  KEY `idx_solicitud_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipo_residuo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tipo_residuo` (
  `id_tipo_residuo` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(60) NOT NULL,
  `descripcion` varchar(200) DEFAULT NULL,
  `reciclable` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_tipo_residuo`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuario` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) NOT NULL,
  `apellido` varchar(80) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `id_rol` int(11) NOT NULL,
  `id_cuadrilla` int(11) DEFAULT NULL,
  `id_centro` int(11) DEFAULT NULL,
  `id_zona` int(11) DEFAULT NULL,
  `id_solicitud` int(11) DEFAULT NULL,
  `estado` enum('activo','inactivo','pendiente') NOT NULL DEFAULT 'activo',
  `avisos_por_correo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_usuario_rol` (`id_rol`),
  KEY `fk_usuario_cuadrilla` (`id_cuadrilla`),
  KEY `fk_usuario_centro` (`id_centro`),
  KEY `fk_usuario_solicitud` (`id_solicitud`),
  KEY `idx_usuario_estado` (`estado`),
  KEY `fk_usuario_zona` (`id_zona`),
  CONSTRAINT `fk_usuario_centro` FOREIGN KEY (`id_centro`) REFERENCES `centro_acopio` (`id_centro`),
  CONSTRAINT `fk_usuario_cuadrilla` FOREIGN KEY (`id_cuadrilla`) REFERENCES `cuadrilla` (`id_cuadrilla`),
  CONSTRAINT `fk_usuario_rol` FOREIGN KEY (`id_rol`) REFERENCES `rol` (`id_rol`),
  CONSTRAINT `fk_usuario_solicitud` FOREIGN KEY (`id_solicitud`) REFERENCES `solicitud_registro` (`id_solicitud`),
  CONSTRAINT `fk_usuario_zona` FOREIGN KEY (`id_zona`) REFERENCES `zona` (`id_zona`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `vaciado`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vaciado` (
  `id_vaciado` int(11) NOT NULL AUTO_INCREMENT,
  `id_recoleccion` int(11) NOT NULL,
  `id_contenedor` int(11) NOT NULL,
  `fecha_hora` datetime NOT NULL DEFAULT current_timestamp(),
  `peso_kg` decimal(10,2) DEFAULT NULL,
  `volumen_l` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id_vaciado`),
  KEY `fk_vaciado_recoleccion` (`id_recoleccion`),
  KEY `fk_vaciado_contenedor` (`id_contenedor`),
  CONSTRAINT `fk_vaciado_contenedor` FOREIGN KEY (`id_contenedor`) REFERENCES `contenedor` (`id_contenedor`),
  CONSTRAINT `fk_vaciado_recoleccion` FOREIGN KEY (`id_recoleccion`) REFERENCES `recoleccion` (`id_recoleccion`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `zona`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `zona` (
  `id_zona` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) NOT NULL,
  `descripcion` varchar(200) DEFAULT NULL,
  `id_centro` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_zona`),
  UNIQUE KEY `nombre` (`nombre`),
  KEY `fk_zona_centro` (`id_centro`),
  CONSTRAINT `fk_zona_centro` FOREIGN KEY (`id_centro`) REFERENCES `centro_acopio` (`id_centro`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

