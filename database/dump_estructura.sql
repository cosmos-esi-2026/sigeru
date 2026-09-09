-- ============================================================================
--  SiGeRU 2026 - Dump de la estructura de la base de datos
--  Archivo: dump_estructura.sql
--  Que es : el volcado que pide la letra ("Dump de la estructura de la BD"),
--           tal como lo escribe el motor, sin retoques.
--
--  ESTE ARCHIVO NO SE EDITA A MANO. Se regenera con:
--      herramientas\xampp\mysql\bin\mysqldump.exe -u root --no-data
--          --skip-comments --skip-set-charset sigeru
--  y se le vuelve a poner esta cabecera.
--
--  El DDL comentado, con las decisiones de modelado explicadas una por una,
--  esta en schema.sql: ese es el archivo que se lee. Este es el volcado.
--
--  Actualizado el 09/09/2026: pasa de las 7 tablas de la 1ra entrega a las 15
--  del modelo vigente (las 14 del pasaje a tablas mas maquinaria, ver la
--  Adenda de Alcance). Hasta hoy seguia siendo el volcado de julio, y era la
--  ultima pieza de la base que quedaba desactualizada.
-- ============================================================================

/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `incidencia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `incidencia` (
  `id_incidencia` int(11) NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(500) NOT NULL,
  `fecha_reporte` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_resolucion` datetime DEFAULT NULL,
  `estado` enum('reportada','en_proceso','resuelta','descartada') NOT NULL DEFAULT 'reportada',
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
  `id_solicitud` int(11) DEFAULT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_usuario_rol` (`id_rol`),
  KEY `fk_usuario_cuadrilla` (`id_cuadrilla`),
  KEY `fk_usuario_solicitud` (`id_solicitud`),
  KEY `idx_usuario_estado` (`estado`),
  CONSTRAINT `fk_usuario_cuadrilla` FOREIGN KEY (`id_cuadrilla`) REFERENCES `cuadrilla` (`id_cuadrilla`),
  CONSTRAINT `fk_usuario_rol` FOREIGN KEY (`id_rol`) REFERENCES `rol` (`id_rol`),
  CONSTRAINT `fk_usuario_solicitud` FOREIGN KEY (`id_solicitud`) REFERENCES `solicitud_registro` (`id_solicitud`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
