-- MySQL dump 10.13  Distrib 8.0.41, for Linux (x86_64)
--
-- Host: localhost    Database: vinculacion
-- ------------------------------------------------------
-- Server version	8.0.41-0ubuntu0.24.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `vinculacion`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `vinculacion` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `vinculacion`;

--
-- Table structure for table `actividad_empleado`
--

DROP TABLE IF EXISTS `actividad_empleado`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `actividad_empleado` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `actividad_id` bigint unsigned NOT NULL,
  `empleado_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `actividad_empleado_actividad_id_foreign` (`actividad_id`),
  KEY `actividad_empleado_empleado_id_foreign` (`empleado_id`),
  CONSTRAINT `actividad_empleado_actividad_id_foreign` FOREIGN KEY (`actividad_id`) REFERENCES `actividades` (`id`) ON DELETE CASCADE,
  CONSTRAINT `actividad_empleado_empleado_id_foreign` FOREIGN KEY (`empleado_id`) REFERENCES `empleado` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `actividades`
--

DROP TABLE IF EXISTS `actividades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `actividades` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `descripcion` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_finalizacion` date NOT NULL,
  `proyecto_id` bigint unsigned NOT NULL,
  `objetivos` longtext COLLATE utf8mb4_unicode_ci,
  `resultados` longtext COLLATE utf8mb4_unicode_ci,
  `horas` int DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `actividades_proyecto_id_foreign` (`proyecto_id`),
  CONSTRAINT `actividades_proyecto_id_foreign` FOREIGN KEY (`proyecto_id`) REFERENCES `proyecto` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `activity_log`
--

DROP TABLE IF EXISTS `activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `log_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` bigint unsigned DEFAULT NULL,
  `causer_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `causer_id` bigint unsigned DEFAULT NULL,
  `properties` json DEFAULT NULL,
  `batch_uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subject` (`subject_type`,`subject_id`),
  KEY `causer` (`causer_type`,`causer_id`),
  KEY `activity_log_log_name_index` (`log_name`)
) ENGINE=InnoDB AUTO_INCREMENT=436 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `administrador_centro_facultad`
--

DROP TABLE IF EXISTS `administrador_centro_facultad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `administrador_centro_facultad` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `centro_facultad_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `es_director` tinyint(1) NOT NULL,
  `estado` tinyint(1) NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `administrador_centro_facultad_centro_facultad_id_foreign` (`centro_facultad_id`),
  KEY `administrador_centro_facultad_user_id_foreign` (`user_id`),
  CONSTRAINT `administrador_centro_facultad_centro_facultad_id_foreign` FOREIGN KEY (`centro_facultad_id`) REFERENCES `centro_facultad` (`id`),
  CONSTRAINT `administrador_centro_facultad_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `aldea`
--

DROP TABLE IF EXISTS `aldea`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `aldea` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `municipio_id` int NOT NULL,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `anexo`
--

DROP TABLE IF EXISTS `anexo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `anexo` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `proyecto_id` bigint unsigned NOT NULL,
  `documento_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `anexo_proyecto_id_foreign` (`proyecto_id`),
  CONSTRAINT `anexo_proyecto_id_foreign` FOREIGN KEY (`proyecto_id`) REFERENCES `proyecto` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `campus`
--

DROP TABLE IF EXISTS `campus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `campus` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre_campus` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `direccion` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cargo_firma`
--

DROP TABLE IF EXISTS `cargo_firma`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cargo_firma` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_cargo_firma_id` bigint unsigned DEFAULT NULL,
  `tipo_estado_id` bigint unsigned DEFAULT NULL,
  `estado_siguiente_id` bigint unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cargo_firma_tipo_cargo_firma_id_foreign` (`tipo_cargo_firma_id`),
  KEY `cargo_firma_tipo_estado_id_foreign` (`tipo_estado_id`),
  KEY `cargo_firma_estado_siguiente_id_foreign` (`estado_siguiente_id`),
  CONSTRAINT `cargo_firma_estado_siguiente_id_foreign` FOREIGN KEY (`estado_siguiente_id`) REFERENCES `tipo_estado` (`id`),
  CONSTRAINT `cargo_firma_tipo_cargo_firma_id_foreign` FOREIGN KEY (`tipo_cargo_firma_id`) REFERENCES `tipo_cargo_firma` (`id`),
  CONSTRAINT `cargo_firma_tipo_estado_id_foreign` FOREIGN KEY (`tipo_estado_id`) REFERENCES `tipo_estado` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `carrera`
--

DROP TABLE IF EXISTS `carrera`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `carrera` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `facultad_centro_id` bigint unsigned NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `carrera_facultad_centro_id_foreign` (`facultad_centro_id`),
  CONSTRAINT `carrera_facultad_centro_id_foreign` FOREIGN KEY (`facultad_centro_id`) REFERENCES `centro_facultad` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `carrera_facultad_centro`
--

DROP TABLE IF EXISTS `carrera_facultad_centro`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `carrera_facultad_centro` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `carrera_id` bigint unsigned NOT NULL,
  `facultad_centro_id` bigint unsigned NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `carrera_facultad_centro_carrera_id_foreign` (`carrera_id`),
  KEY `carrera_facultad_centro_facultad_centro_id_foreign` (`facultad_centro_id`),
  CONSTRAINT `carrera_facultad_centro_carrera_id_foreign` FOREIGN KEY (`carrera_id`) REFERENCES `carrera` (`id`),
  CONSTRAINT `carrera_facultad_centro_facultad_centro_id_foreign` FOREIGN KEY (`facultad_centro_id`) REFERENCES `centro_facultad` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `categoria`
--

DROP TABLE IF EXISTS `categoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categoria` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `categoria_comentario`
--

DROP TABLE IF EXISTS `categoria_comentario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categoria_comentario` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre_categoria` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `categorias`
--

DROP TABLE IF EXISTS `categorias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categorias` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categorias_nombre_unique` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `centro_facultad`
--

DROP TABLE IF EXISTS `centro_facultad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `centro_facultad` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `es_facultad` tinyint(1) NOT NULL,
  `siglas` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `campus_id` bigint unsigned NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `centro_facultad_campus_id_foreign` (`campus_id`),
  CONSTRAINT `centro_facultad_campus_id_foreign` FOREIGN KEY (`campus_id`) REFERENCES `campus` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ciudad`
--

DROP TABLE IF EXISTS `ciudad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ciudad` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `municipio_id` int NOT NULL,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo_postal` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `comentario`
--

DROP TABLE IF EXISTS `comentario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comentario` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `contenido` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `empleado_id` bigint unsigned NOT NULL,
  `categoria_comentario_id` bigint unsigned NOT NULL,
  `visto` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `comentario_empleado_id_foreign` (`empleado_id`),
  KEY `comentario_categoria_comentario_id_foreign` (`categoria_comentario_id`),
  CONSTRAINT `comentario_categoria_comentario_id_foreign` FOREIGN KEY (`categoria_comentario_id`) REFERENCES `categoria_comentario` (`id`),
  CONSTRAINT `comentario_empleado_id_foreign` FOREIGN KEY (`empleado_id`) REFERENCES `empleado` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `departamento`
--

DROP TABLE IF EXISTS `departamento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `departamento` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `pais_id` int NOT NULL,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo_departamento` int DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `departamento_academico`
--

DROP TABLE IF EXISTS `departamento_academico`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `departamento_academico` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `centro_facultad_id` bigint unsigned NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `departamento_academico_centro_facultad_id_foreign` (`centro_facultad_id`),
  CONSTRAINT `departamento_academico_centro_facultad_id_foreign` FOREIGN KEY (`centro_facultad_id`) REFERENCES `centro_facultad` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `empleado`
--

DROP TABLE IF EXISTS `empleado`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `empleado` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre_completo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_empleado` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `celular` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `categoria_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `centro_facultad_id` int DEFAULT NULL,
  `departamento_academico_id` int DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empleado_numero_empleado_unique` (`numero_empleado`),
  KEY `empleado_user_id_foreign` (`user_id`),
  KEY `empleado_categoria_id_foreign` (`categoria_id`),
  CONSTRAINT `empleado_categoria_id_foreign` FOREIGN KEY (`categoria_id`) REFERENCES `categoria` (`id`) ON DELETE CASCADE,
  CONSTRAINT `empleado_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `empleado_proyecto`
--

DROP TABLE IF EXISTS `empleado_proyecto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `empleado_proyecto` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `empleado_id` bigint unsigned NOT NULL,
  `proyecto_id` bigint unsigned NOT NULL,
  `rol` enum('Coordinador','Subcoordinador','Integrante') COLLATE utf8mb4_unicode_ci NOT NULL,
  `hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `empleado_proyecto_empleado_id_foreign` (`empleado_id`),
  KEY `empleado_proyecto_proyecto_id_foreign` (`proyecto_id`),
  CONSTRAINT `empleado_proyecto_empleado_id_foreign` FOREIGN KEY (`empleado_id`) REFERENCES `empleado` (`id`),
  CONSTRAINT `empleado_proyecto_proyecto_id_foreign` FOREIGN KEY (`proyecto_id`) REFERENCES `proyecto` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `entidad_contraparte`
--

DROP TABLE IF EXISTS `entidad_contraparte`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `entidad_contraparte` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `proyecto_id` bigint unsigned NOT NULL,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `correo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_contacto` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `es_internacional` tinyint(1) NOT NULL DEFAULT '0',
  `aporte` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `entidad_contraparte_proyecto_id_foreign` (`proyecto_id`),
  CONSTRAINT `entidad_contraparte_proyecto_id_foreign` FOREIGN KEY (`proyecto_id`) REFERENCES `proyecto` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `estado_proyecto`
--

DROP TABLE IF EXISTS `estado_proyecto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `estado_proyecto` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `empleado_id` bigint unsigned NOT NULL,
  `tipo_estado_id` bigint unsigned NOT NULL,
  `fecha` date NOT NULL,
  `comentario` text COLLATE utf8mb4_unicode_ci,
  `es_actual` tinyint(1) NOT NULL DEFAULT '1',
  `estadoable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estadoable_id` bigint unsigned NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `estado_proyecto_empleado_id_foreign` (`empleado_id`),
  KEY `estado_proyecto_tipo_estado_id_foreign` (`tipo_estado_id`),
  KEY `estado_proyecto_estadoable_type_estadoable_id_index` (`estadoable_type`,`estadoable_id`),
  CONSTRAINT `estado_proyecto_empleado_id_foreign` FOREIGN KEY (`empleado_id`) REFERENCES `empleado` (`id`),
  CONSTRAINT `estado_proyecto_tipo_estado_id_foreign` FOREIGN KEY (`tipo_estado_id`) REFERENCES `tipo_estado` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `estudiante`
--

DROP TABLE IF EXISTS `estudiante`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `estudiante` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `apellido` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cuenta` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `centro_facultdad_id` bigint unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `estudiante_user_id_foreign` (`user_id`),
  KEY `estudiante_centro_facultdad_id_foreign` (`centro_facultdad_id`),
  CONSTRAINT `estudiante_centro_facultdad_id_foreign` FOREIGN KEY (`centro_facultdad_id`) REFERENCES `centro_facultad` (`id`),
  CONSTRAINT `estudiante_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `estudiante_proyecto`
--

DROP TABLE IF EXISTS `estudiante_proyecto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `estudiante_proyecto` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `estudiante_id` bigint unsigned NOT NULL,
  `proyecto_id` bigint unsigned NOT NULL,
  `tipo_participacion_estudiante` enum('Servicio Social Universitario','Practica Profesional','Voluntariado','Practica de Clase') COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `estudiante_proyecto_estudiante_id_foreign` (`estudiante_id`),
  KEY `estudiante_proyecto_proyecto_id_foreign` (`proyecto_id`),
  CONSTRAINT `estudiante_proyecto_estudiante_id_foreign` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiante` (`id`),
  CONSTRAINT `estudiante_proyecto_proyecto_id_foreign` FOREIGN KEY (`proyecto_id`) REFERENCES `proyecto` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `firma_proyecto`
--

DROP TABLE IF EXISTS `firma_proyecto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `firma_proyecto` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `empleado_id` bigint unsigned NOT NULL,
  `firma_id` bigint unsigned DEFAULT NULL,
  `sello_id` bigint unsigned DEFAULT NULL,
  `cargo_firma_id` bigint unsigned NOT NULL,
  `fecha_firma` datetime DEFAULT NULL,
  `estado_revision` enum('Pendiente','Rechazado','Aprobado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pendiente',
  `hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `firmable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `firmable_id` bigint unsigned NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `firma_proyecto_empleado_id_foreign` (`empleado_id`),
  KEY `firma_proyecto_cargo_firma_id_foreign` (`cargo_firma_id`),
  KEY `firma_proyecto_firmable_type_firmable_id_index` (`firmable_type`,`firmable_id`),
  CONSTRAINT `firma_proyecto_cargo_firma_id_foreign` FOREIGN KEY (`cargo_firma_id`) REFERENCES `cargo_firma` (`id`),
  CONSTRAINT `firma_proyecto_empleado_id_foreign` FOREIGN KEY (`empleado_id`) REFERENCES `empleado` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `firma_sello_empleado`
--

DROP TABLE IF EXISTS `firma_sello_empleado`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `firma_sello_empleado` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `empleado_id` bigint unsigned NOT NULL,
  `tipo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ruta_storage` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `firma_sello_empleado_empleado_id_foreign` (`empleado_id`),
  CONSTRAINT `firma_sello_empleado_empleado_id_foreign` FOREIGN KEY (`empleado_id`) REFERENCES `empleado` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `instrumento_formalizacion`
--

DROP TABLE IF EXISTS `instrumento_formalizacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `instrumento_formalizacion` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `entidad_contraparte_id` bigint unsigned NOT NULL,
  `documento_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `instrumento_formalizacion_entidad_contraparte_id_foreign` (`entidad_contraparte_id`),
  CONSTRAINT `instrumento_formalizacion_entidad_contraparte_id_foreign` FOREIGN KEY (`entidad_contraparte_id`) REFERENCES `entidad_contraparte` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `modalidad`
--

DROP TABLE IF EXISTS `modalidad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `modalidad` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `municipio`
--

DROP TABLE IF EXISTS `municipio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `municipio` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `departamento_id` int NOT NULL,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo_municipio` int DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=301 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ods`
--

DROP TABLE IF EXISTS `ods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ods` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ods_nombre_unique` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pais`
--

DROP TABLE IF EXISTS `pais`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pais` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo_area` int NOT NULL,
  `codigo_iso` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo_iso_numerico` int NOT NULL,
  `codigo_iso_alpha_2` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gentilicio` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `presupuesto`
--

DROP TABLE IF EXISTS `presupuesto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `presupuesto` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `proyecto_id` bigint unsigned NOT NULL,
  `aporte_estudiantes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aporte_profesores` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aporte_academico_unah` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aporte_transporte_unah` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aporte_contraparte` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aporte_comunidad` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `otros_aportes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `presupuesto_proyecto_id_unique` (`proyecto_id`),
  CONSTRAINT `presupuesto_proyecto_id_foreign` FOREIGN KEY (`proyecto_id`) REFERENCES `proyecto` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `proyecto`
--

DROP TABLE IF EXISTS `proyecto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `proyecto` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre_proyecto` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modalidad_id` bigint unsigned DEFAULT NULL,
  `aldea` longtext COLLATE utf8mb4_unicode_ci,
  `resumen` longtext COLLATE utf8mb4_unicode_ci,
  `objetivo_general` longtext COLLATE utf8mb4_unicode_ci,
  `objetivos_especificos` longtext COLLATE utf8mb4_unicode_ci,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_finalizacion` date DEFAULT NULL,
  `evaluacion_intermedia` date DEFAULT NULL,
  `evaluacion_final` date DEFAULT NULL,
  `poblacion_participante` decimal(8,2) DEFAULT NULL,
  `modalidad_ejecucion` enum('Distancia','Presencial','Bimodal') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `resultados_esperados` longtext COLLATE utf8mb4_unicode_ci,
  `indicadores_medicion_resultados` longtext COLLATE utf8mb4_unicode_ci,
  `fecha_registro` date DEFAULT NULL,
  `responsable_revision_id` bigint unsigned DEFAULT NULL,
  `user_director_id` bigint unsigned DEFAULT NULL,
  `fecha_aprobacion` date DEFAULT NULL,
  `numero_libro` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_tomo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_folio` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_dictamen` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `proyecto_modalidad_id_foreign` (`modalidad_id`),
  KEY `proyecto_responsable_revision_id_foreign` (`responsable_revision_id`),
  KEY `proyecto_user_director_id_foreign` (`user_director_id`),
  CONSTRAINT `proyecto_modalidad_id_foreign` FOREIGN KEY (`modalidad_id`) REFERENCES `modalidad` (`id`),
  CONSTRAINT `proyecto_responsable_revision_id_foreign` FOREIGN KEY (`responsable_revision_id`) REFERENCES `empleado` (`id`),
  CONSTRAINT `proyecto_user_director_id_foreign` FOREIGN KEY (`user_director_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `proyecto_categoria`
--

DROP TABLE IF EXISTS `proyecto_categoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `proyecto_categoria` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `proyecto_id` bigint unsigned NOT NULL,
  `categoria_id` bigint unsigned NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `proyecto_categoria_proyecto_id_foreign` (`proyecto_id`),
  KEY `proyecto_categoria_categoria_id_foreign` (`categoria_id`),
  CONSTRAINT `proyecto_categoria_categoria_id_foreign` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`),
  CONSTRAINT `proyecto_categoria_proyecto_id_foreign` FOREIGN KEY (`proyecto_id`) REFERENCES `proyecto` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `proyecto_centro_facultad`
--

DROP TABLE IF EXISTS `proyecto_centro_facultad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `proyecto_centro_facultad` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `proyecto_id` bigint unsigned NOT NULL,
  `centro_facultad_id` bigint unsigned NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `proyecto_centro_facultad_proyecto_id_foreign` (`proyecto_id`),
  KEY `proyecto_centro_facultad_centro_facultad_id_foreign` (`centro_facultad_id`),
  CONSTRAINT `proyecto_centro_facultad_centro_facultad_id_foreign` FOREIGN KEY (`centro_facultad_id`) REFERENCES `centro_facultad` (`id`),
  CONSTRAINT `proyecto_centro_facultad_proyecto_id_foreign` FOREIGN KEY (`proyecto_id`) REFERENCES `proyecto` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `proyecto_departamento`
--

DROP TABLE IF EXISTS `proyecto_departamento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `proyecto_departamento` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `proyecto_id` bigint unsigned NOT NULL,
  `departamento_id` bigint unsigned NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `proyecto_departamento_proyecto_id_foreign` (`proyecto_id`),
  KEY `proyecto_departamento_departamento_id_foreign` (`departamento_id`),
  CONSTRAINT `proyecto_departamento_departamento_id_foreign` FOREIGN KEY (`departamento_id`) REFERENCES `departamento` (`id`),
  CONSTRAINT `proyecto_departamento_proyecto_id_foreign` FOREIGN KEY (`proyecto_id`) REFERENCES `proyecto` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `proyecto_depto_ac`
--

DROP TABLE IF EXISTS `proyecto_depto_ac`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `proyecto_depto_ac` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `proyecto_id` bigint unsigned NOT NULL,
  `departamento_academico_id` bigint unsigned NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `proyecto_depto_ac_proyecto_id_foreign` (`proyecto_id`),
  KEY `proyecto_depto_ac_departamento_academico_id_foreign` (`departamento_academico_id`),
  CONSTRAINT `proyecto_depto_ac_departamento_academico_id_foreign` FOREIGN KEY (`departamento_academico_id`) REFERENCES `departamento_academico` (`id`),
  CONSTRAINT `proyecto_depto_ac_proyecto_id_foreign` FOREIGN KEY (`proyecto_id`) REFERENCES `proyecto` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `proyecto_documento`
--

DROP TABLE IF EXISTS `proyecto_documento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `proyecto_documento` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `proyecto_id` bigint unsigned NOT NULL,
  `tipo_documento` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `documento_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `proyecto_documento_proyecto_id_foreign` (`proyecto_id`),
  CONSTRAINT `proyecto_documento_proyecto_id_foreign` FOREIGN KEY (`proyecto_id`) REFERENCES `proyecto` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `proyecto_municipio`
--

DROP TABLE IF EXISTS `proyecto_municipio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `proyecto_municipio` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `proyecto_id` bigint unsigned NOT NULL,
  `municipio_id` bigint unsigned NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `proyecto_municipio_proyecto_id_foreign` (`proyecto_id`),
  KEY `proyecto_municipio_municipio_id_foreign` (`municipio_id`),
  CONSTRAINT `proyecto_municipio_municipio_id_foreign` FOREIGN KEY (`municipio_id`) REFERENCES `municipio` (`id`),
  CONSTRAINT `proyecto_municipio_proyecto_id_foreign` FOREIGN KEY (`proyecto_id`) REFERENCES `proyecto` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `proyecto_ods`
--

DROP TABLE IF EXISTS `proyecto_ods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `proyecto_ods` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `proyecto_id` bigint unsigned NOT NULL,
  `ods_id` bigint unsigned NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `proyecto_ods_proyecto_id_foreign` (`proyecto_id`),
  KEY `proyecto_ods_ods_id_foreign` (`ods_id`),
  CONSTRAINT `proyecto_ods_ods_id_foreign` FOREIGN KEY (`ods_id`) REFERENCES `ods` (`id`),
  CONSTRAINT `proyecto_ods_proyecto_id_foreign` FOREIGN KEY (`proyecto_id`) REFERENCES `proyecto` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `respuesta_comentario`
--

DROP TABLE IF EXISTS `respuesta_comentario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `respuesta_comentario` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `contenido` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `empleado_id` bigint unsigned NOT NULL,
  `comentario_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `respuesta_comentario_empleado_id_foreign` (`empleado_id`),
  KEY `respuesta_comentario_comentario_id_foreign` (`comentario_id`),
  CONSTRAINT `respuesta_comentario_comentario_id_foreign` FOREIGN KEY (`comentario_id`) REFERENCES `comentario` (`id`),
  CONSTRAINT `respuesta_comentario_empleado_id_foreign` FOREIGN KEY (`empleado_id`) REFERENCES `empleado` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `slide`
--

DROP TABLE IF EXISTS `slide`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `slide` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `image_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `superavit_proyecto`
--

DROP TABLE IF EXISTS `superavit_proyecto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `superavit_proyecto` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `proyecto_id` bigint unsigned NOT NULL,
  `inversion` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `monto` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `superavit_proyecto_proyecto_id_foreign` (`proyecto_id`),
  CONSTRAINT `superavit_proyecto_proyecto_id_foreign` FOREIGN KEY (`proyecto_id`) REFERENCES `proyecto` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tipo_cargo_firma`
--

DROP TABLE IF EXISTS `tipo_cargo_firma`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipo_cargo_firma` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tipo_estado`
--

DROP TABLE IF EXISTS `tipo_estado`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipo_estado` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `microsoft_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `given_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `surname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `active_role_id` bigint unsigned DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_microsoft_id_unique` (`microsoft_id`),
  KEY `users_active_role_id_index` (`active_role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-04-01 10:03:14
