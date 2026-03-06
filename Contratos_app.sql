CREATE DATABASE  IF NOT EXISTS `contrato_app` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;
USE `contrato_app`;
-- MySQL dump 10.13  Distrib 8.0.44, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: contrato_app
-- ------------------------------------------------------
-- Server version	5.5.5-10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `busqueda_provincias`
--

DROP TABLE IF EXISTS `busqueda_provincias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `busqueda_provincias` (
  `busqueda_id` int(11) NOT NULL,
  `provincia_id` int(11) NOT NULL,
  `nivel_interes` enum('alto','medio','bajo') DEFAULT 'medio',
  PRIMARY KEY (`busqueda_id`,`provincia_id`),
  KEY `provincia_id` (`provincia_id`),
  CONSTRAINT `busqueda_provincias_ibfk_1` FOREIGN KEY (`busqueda_id`) REFERENCES `busquedas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `busqueda_provincias_ibfk_2` FOREIGN KEY (`provincia_id`) REFERENCES `provincias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `busqueda_provincias`
--

LOCK TABLES `busqueda_provincias` WRITE;
/*!40000 ALTER TABLE `busqueda_provincias` DISABLE KEYS */;
/*!40000 ALTER TABLE `busqueda_provincias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `busquedas`
--

DROP TABLE IF EXISTS `busquedas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `busquedas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `tipo` enum('licitacion','subvencion','premio','todos') DEFAULT 'todos',
  `palabras_clave` text NOT NULL COMMENT 'separadas por comas',
  `palabras_excluir` text DEFAULT NULL,
  `presupuesto_min` decimal(15,2) DEFAULT NULL,
  `presupuesto_max` decimal(15,2) DEFAULT NULL,
  `cpv_incluir` text DEFAULT NULL COMMENT 'códigos CPV separados por comas',
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  CONSTRAINT `busquedas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `busquedas`
--

LOCK TABLES `busquedas` WRITE;
/*!40000 ALTER TABLE `busquedas` DISABLE KEYS */;
INSERT INTO `busquedas` VALUES (1,1,'Prueba inicial','todos','producción de vídeo, servicios de producción, grabación de eventos, streaming, retransmisión, cobertura audiovisual, realización de vídeos, producción multimedia, contenidos audiovisuales, vídeo institucional, documental, postproducción, edición de vídeo, animación, infografía, vídeo divulgativo, divulgación científica, comunicación científica, producción científica',NULL,NULL,NULL,NULL,1,'2026-03-03 18:28:39','2026-03-05 16:24:04'),(2,1,'2','todos','medio ambiente, contratación, agrícola',NULL,NULL,NULL,NULL,1,'2026-03-04 14:50:59','2026-03-04 14:51:51'),(3,2,'Audiovisual','todos','producción de vídeo, servicios audiovisuales, cine, vídeo institucional, grabación de eventos, streaming, contenido multimedia, diseño gráfico, publicidad, comunicación institucional, campaña audiovisual, 92100000, 92112000, 79341000',NULL,NULL,NULL,NULL,1,'2026-03-04 18:26:18',NULL),(4,1,'Servicios audiovisuales - producción y streaming','todos','producción de vídeo, servicios de producción, grabación de eventos, streaming, retransmisión, cobertura audiovisual, realización de vídeos, producción multimedia, contenidos audiovisuales, vídeo institucional, documental, postproducción, edición de vídeo, animación, infografía',NULL,NULL,NULL,NULL,1,'2026-03-05 16:13:36',NULL),(5,1,'Divulgación científica audiovisual','todos','vídeo divulgativo, divulgación científica, contenido científico, documental científico, animación científica, infografía científica, comunicación científica, producción científica, material didáctico audiovisual, vídeo educativo, congreso científico streaming, jornadas científicas grabación',NULL,NULL,NULL,NULL,1,'2026-03-05 16:23:27',NULL);
/*!40000 ALTER TABLE `busquedas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ejecuciones_log`
--

DROP TABLE IF EXISTS `ejecuciones_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ejecuciones_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `fuente_id` int(11) DEFAULT NULL,
  `busqueda_id` int(11) DEFAULT NULL,
  `resultados_encontrados` int(11) DEFAULT 0,
  `tiempo_ejecucion` float DEFAULT NULL,
  `errores` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fuente_id` (`fuente_id`),
  CONSTRAINT `ejecuciones_log_ibfk_1` FOREIGN KEY (`fuente_id`) REFERENCES `fuentes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ejecuciones_log`
--

LOCK TABLES `ejecuciones_log` WRITE;
/*!40000 ALTER TABLE `ejecuciones_log` DISABLE KEYS */;
INSERT INTO `ejecuciones_log` VALUES (1,'2026-03-03 18:33:21',1,1,0,0.718511,'No se pudo cargar el RSS'),(2,'2026-03-03 18:36:15',1,1,0,0.117614,'No se pudo parsear el RSS');
/*!40000 ALTER TABLE `ejecuciones_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fuentes`
--

DROP TABLE IF EXISTS `fuentes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fuentes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `nombre_corto` varchar(50) NOT NULL,
  `tipo` enum('licitacion','subvencion','ambos') DEFAULT 'licitacion',
  `url_base` varchar(500) DEFAULT NULL,
  `script_asociado` varchar(200) NOT NULL,
  `activa` tinyint(1) DEFAULT 1,
  `orden_prioridad` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre_corto` (`nombre_corto`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fuentes`
--

LOCK TABLES `fuentes` WRITE;
/*!40000 ALTER TABLE `fuentes` DISABLE KEYS */;
INSERT INTO `fuentes` VALUES (1,'Plataforma de Contratación del Estado','contratacion_estado','licitacion','https://contrataciondelestado.es','ContratacionEstado.php',1,1,'2026-03-03 16:25:13'),(2,'Diario Oficial de Galicia','doge','licitacion','https://www.xunta.gal/diario-oficial-galicia','DOGE.php',1,0,'2026-03-03 18:40:28'),(3,'Boletín Oficial del Estado','boe','licitacion','https://www.boe.es','BOE.php',1,0,'2026-03-03 19:20:31'),(4,'Diputació de Barcelona - Contractació','diba','licitacion','https://api.diba.cat','DIBAAPI.php',1,0,'2026-03-04 19:08:01'),(5,'Butlletí Oficial de la Província de Barcelona','bopb','licitacion','https://bop.diba.cat','BOPB_RSS.php',1,0,'2026-03-05 14:48:46'),(8,'Ayuntamiento de Zaragoza - Contratación','zaragoza','licitacion','https://www.zaragoza.es','ZaragozaAPI.php',1,0,'2026-03-05 15:41:02'),(9,'CSIC - Contratación','csic','licitacion','https://contrataciondelestado.es','CSIC.php',1,0,'2026-03-05 16:39:34'),(10,'Base de Datos Nacional de Subvenciones','bdns','subvencion','https://www.pap.hacienda.gob.es','buscar_bdns_servicios.php',1,0,'2026-03-05 16:51:16'),(11,'Plataforma de Contratación','placsp','licitacion','https://contrataciondelestado.es','PLACSP.php',1,0,'2026-03-06 15:04:45'),(12,'CSIC (vía PLACSP)','csic_placsp','licitacion','https://contrataciondelsectorpublico.gob.es','CSIC_PLACSP.php',1,0,'2026-03-06 16:22:58');
/*!40000 ALTER TABLE `fuentes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `provincias`
--

DROP TABLE IF EXISTS `provincias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `provincias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `codigo` varchar(2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `provincias`
--

LOCK TABLES `provincias` WRITE;
/*!40000 ALTER TABLE `provincias` DISABLE KEYS */;
INSERT INTO `provincias` VALUES (1,'Nacional','00'),(2,'Álava','01'),(3,'Albacete','02'),(4,'Alicante','03'),(5,'Almería','04'),(6,'Asturias','33'),(7,'Ávila','05'),(8,'Badajoz','06'),(9,'Barcelona','08'),(10,'Burgos','09'),(11,'Cáceres','10'),(12,'Cádiz','11'),(13,'Cantabria','39'),(14,'Castellón','12'),(15,'Ciudad Real','13'),(16,'Córdoba','14'),(17,'Cuenca','16'),(18,'Girona','17'),(19,'Granada','18'),(20,'Guadalajara','19'),(21,'Guipúzcoa','20'),(22,'Huelva','21'),(23,'Huesca','22'),(24,'Illes Balears','07'),(25,'Jaén','23'),(26,'La Rioja','26'),(27,'Las Palmas','35'),(28,'León','24'),(29,'Lleida','25'),(30,'Lugo','27'),(31,'Madrid','28'),(32,'Málaga','29'),(33,'Murcia','30'),(34,'Navarra','31'),(35,'Ourense','32'),(36,'Palencia','34'),(37,'Pontevedra','36'),(38,'Salamanca','37'),(39,'Santa Cruz de Tenerife','38'),(40,'Segovia','40'),(41,'Sevilla','41'),(42,'Soria','42'),(43,'Tarragona','43'),(44,'Teruel','44'),(45,'Toledo','45'),(46,'Valencia','46'),(47,'Valladolid','47'),(48,'Vizcaya','48'),(49,'Zamora','49'),(50,'Zaragoza','50'),(51,'Ceuta','51'),(52,'Melilla','52');
/*!40000 ALTER TABLE `provincias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `resultados`
--

DROP TABLE IF EXISTS `resultados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `resultados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `busqueda_id` int(11) NOT NULL,
  `fuente_id` int(11) NOT NULL,
  `titulo` varchar(500) NOT NULL,
  `descripcion_corta` text DEFAULT NULL,
  `organismo` varchar(300) DEFAULT NULL,
  `presupuesto` decimal(15,2) DEFAULT NULL,
  `fecha_publicacion` date DEFAULT NULL,
  `fecha_limite` date DEFAULT NULL,
  `url_detalle` varchar(700) NOT NULL,
  `codigo_bdns` varchar(20) DEFAULT NULL,
  `lugar_ejecucion` varchar(200) DEFAULT NULL,
  `provincia_id` int(11) DEFAULT NULL,
  `codigos_cpv` text DEFAULT NULL,
  `tipo_contrato` varchar(100) DEFAULT NULL,
  `relevancia` int(11) DEFAULT 0,
  `palabras_coincidentes` text DEFAULT NULL,
  `leido` tinyint(1) DEFAULT 0,
  `interesante` tinyint(1) DEFAULT NULL,
  `descartado` tinyint(1) DEFAULT 0,
  `favorito` tinyint(1) DEFAULT 0,
  `fecha_deteccion` timestamp NOT NULL DEFAULT current_timestamp(),
  `ultima_deteccion` timestamp NULL DEFAULT NULL,
  `estado_usuario` int(11) DEFAULT 0 COMMENT '0-pendiente, 1-interesante, 2-descartado',
  PRIMARY KEY (`id`),
  KEY `fuente_id` (`fuente_id`),
  KEY `provincia_id` (`provincia_id`),
  KEY `idx_busqueda` (`busqueda_id`),
  KEY `idx_fecha_limite` (`fecha_limite`),
  KEY `idx_leido` (`leido`),
  KEY `idx_ultima_deteccion` (`ultima_deteccion`),
  KEY `idx_estado_usuario` (`estado_usuario`),
  FULLTEXT KEY `idx_busqueda_texto` (`titulo`,`descripcion_corta`),
  CONSTRAINT `resultados_ibfk_1` FOREIGN KEY (`busqueda_id`) REFERENCES `busquedas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `resultados_ibfk_2` FOREIGN KEY (`fuente_id`) REFERENCES `fuentes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `resultados_ibfk_3` FOREIGN KEY (`provincia_id`) REFERENCES `provincias` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=75 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resultados`
--

LOCK TABLES `resultados` WRITE;
/*!40000 ALTER TABLE `resultados` DISABLE KEYS */;
INSERT INTO `resultados` VALUES (65,1,8,'CONCESIÓN DEMANIAL DE LA UNIDAD DE GESTIÓN DE RESTAURACIÓN DEL ECOSISTEMA AUDIOVISUAL - DISTRITO 7','','Ayuntamiento de Zaragoza',1170763.78,'2026-03-06',NULL,'https://www.zaragoza.es/sede/servicio/contratacion-publica/7765',NULL,NULL,NULL,NULL,NULL,1,'[\"DISTRITO 7\"]',0,NULL,0,0,'2026-03-06 14:39:22',NULL,0),(66,1,8,'CONCESIÓN DEMANIAL DE LA UNIDAD DE GESTIÓN DE PRODUCCIÓN DEL ECOSISTEMA AUDIOVISUAL - DISTRITO 7','','Ayuntamiento de Zaragoza',8357253.65,'2026-03-06',NULL,'https://www.zaragoza.es/sede/servicio/contratacion-publica/7764',NULL,NULL,NULL,NULL,NULL,1,'[\"DISTRITO 7\"]',0,NULL,0,0,'2026-03-06 14:39:22',NULL,0),(67,1,8,'SUMINISTRO E INSTALACIÓN PARA EL EQUIPAMIENTO DE SISTEMAS MÓVILES DE ESCENARIO Y BUTACAS DE LA SALA DE EXHIBICIÓN PARA DISTRITO 7 - ECOSISTEMA AUDIOVISUAL DE ZARAGOZA.','','Ayuntamiento de Zaragoza',785123.87,'2026-03-06',NULL,'https://www.zaragoza.es/sede/servicio/contratacion-publica/7759',NULL,NULL,NULL,NULL,NULL,1,'[\"DISTRITO 7\"]',0,NULL,0,0,'2026-03-06 14:39:22',NULL,0),(68,1,10,'Convenio regulador de subvención nominativa a favor de la Peña Philips para las actividades generales de animación durante las fiestas de marzo y agosto 2026 y otros eventos.','Convenio regulador de subvención nominativa a favor de la Peña Philips para las actividades generales de animación durante las fiestas de marzo y agosto 2026 y otros eventos.','LOCAL - CALAHORRA - AYUNTAMIENTO DE CALAHORRA',NULL,'2026-03-05',NULL,'https://www.pap.hacienda.gob.es/bdnstrans/GE/es/convocatorias/891440','891440',NULL,NULL,NULL,NULL,1,'[\"animación\"]',0,NULL,0,0,'2026-03-06 14:39:53',NULL,2),(69,1,10,'Convenio regulador de subvención nominativa a favor de la Peña Riojana para las actividades generales de animación durante las fiestas de marzo y agosto 2026 y otros eventos.','Convenio regulador de subvención nominativa a favor de la Peña Riojana para las actividades generales de animación durante las fiestas de marzo y agosto 2026 y otros eventos.','LOCAL - CALAHORRA - AYUNTAMIENTO DE CALAHORRA',NULL,'2026-03-05',NULL,'https://www.pap.hacienda.gob.es/bdnstrans/GE/es/convocatorias/891406','891406',NULL,NULL,NULL,NULL,1,'[\"animación\"]',0,NULL,0,0,'2026-03-06 14:39:53',NULL,2),(70,1,10,'Convenio de subvención nominativa a favor de la Peña El Sol para las actividades generales de animación durante las fiestas de marzo y agosto 2026 y otros eventos.','Convenio de subvención nominativa a favor de la Peña El Sol para las actividades generales de animación durante las fiestas de marzo y agosto 2026 y otros eventos.','LOCAL - CALAHORRA - AYUNTAMIENTO DE CALAHORRA',NULL,'2026-03-05',NULL,'https://www.pap.hacienda.gob.es/bdnstrans/GE/es/convocatorias/891393','891393',NULL,NULL,NULL,NULL,1,'[\"animación\"]',0,NULL,0,0,'2026-03-06 14:39:53',NULL,2),(71,1,11,'El objeto del Acuerdo Marco es la selección de entidades especializadas en los servicios de retransmisión en directo para la difusión online de eventos y jornadas de especial interés para la audiencia de EOI, así como para la comercialización de sus actividades y programas','Id licitación: 2026_003_AM; Órgano de Contratación: Dirección General de la Fundación EOI; Importe: 30000 EUR; Estado: EV','Organismo contratante',NULL,'2026-03-05',NULL,'https://contrataciondelestado.es/wps/poc?uri=deeplink:detalle_licitacion&idEvl=DOLSUZfUufgeIBJRHQiPkQ%3D%3D',NULL,NULL,NULL,NULL,NULL,1,'[\"retransmisión\"]',0,NULL,0,0,'2026-03-06 15:22:33',NULL,0),(72,1,11,'Servicio de grabación y producción de vídeos de carácter institucional para el gabinete de prensa del Ayuntamiento de Gijón/Xixón ','Id licitación: 8134M/2026; Órgano de Contratación: Alcaldía del Ayuntamiento de Gijón ; Importe: 72000 EUR; Estado: EV','Organismo contratante',NULL,'2026-03-05',NULL,'https://contrataciondelestado.es/wps/poc?uri=deeplink:detalle_licitacion&idEvl=QdrKxksUdx2FlFRHfEzEaw%3D%3D',NULL,NULL,NULL,NULL,NULL,1,'[\"producción de vídeo\"]',0,NULL,0,0,'2026-03-06 15:22:33',NULL,0),(73,1,11,'Contratación de los servicios de análisis clínicos, anestesia y reanimación, cardiología, cirugía ortopédica y traumatología, dermatología, enfermería, fisioterapia, hospitalización y quirófano, medicina interna, neurocirugía, neurología, oftalmología, otorrinolaringología, radiodiagnóstico, rehabilitación y urgencias en Palma de Mallorca (Illes Balears)','Id licitación: 17/2026; Órgano de Contratación: Dirección Gerencia de Unión de Mutuas, Mutua Colaboradora con la Seguridad Social Nº 267; Importe: 38075 EUR; Estado: PUB','Organismo contratante',NULL,'2026-03-05',NULL,'https://contrataciondelestado.es/wps/poc?uri=deeplink:detalle_licitacion&idEvl=mH0m8uXGrWmopEMYCmrbmw%3D%3D',NULL,NULL,NULL,NULL,NULL,1,'[\"animación\"]',0,NULL,0,0,'2026-03-06 15:22:33',NULL,2),(74,2,10,'Subvención directa a la SCDR Cemborain para financiar gastos de contratación de socorristas','Subvención directa a la SCDR Cemborain para financiar gastos de contratación de socorristas','LOCAL - ORÍSOAIN - AYUNTAMIENTO DE ORÍSOAIN',NULL,'2026-03-06',NULL,'https://www.pap.hacienda.gob.es/bdnstrans/GE/es/convocatorias/891623','891623',NULL,NULL,NULL,NULL,1,'[\"contratación\"]',0,NULL,0,0,'2026-03-06 15:42:57',NULL,0);
/*!40000 ALTER TABLE `resultados` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'Usuario Prueba','test@prueba.com','$2y$10$5znZqcm0NLSP1kgnSsEsp.Gp0U0y3KS8z1gDYTxnpdCXyW.Zpbc3i',1,'2026-03-03 18:28:39',NULL),(2,'Pablo','patata@gm.com','$2y$10$qufAYfFfipC8KY5hoF/iiOSBhUmdh6HTrjYjbMnl4H.RFs.032DQG',1,'2026-03-04 15:59:09','2026-03-06 15:47:58');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-06 17:40:50
