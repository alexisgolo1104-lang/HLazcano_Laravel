-- MySQL dump 10.13  Distrib 8.0.19, for Win64 (x86_64)
--
-- Host: localhost    Database: hlazcano_db
-- ------------------------------------------------------
-- Server version	8.0.30

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
-- Table structure for table `alertas_stock`
--

DROP TABLE IF EXISTS `alertas_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `alertas_stock` (
  `id_alerta` int NOT NULL AUTO_INCREMENT,
  `id_producto` int NOT NULL,
  `id_tienda` int NOT NULL,
  `tipo_alerta` varchar(150) NOT NULL,
  `atendida` tinyint NOT NULL DEFAULT '0',
  `accion_tomada` varchar(150) DEFAULT NULL,
  `id_usuario_aten` int DEFAULT NULL,
  `notas_atencion` varchar(150) DEFAULT NULL,
  `fecha_alerta` date NOT NULL,
  `fecha_atencion` date DEFAULT NULL,
  `stock_alerta` decimal(10,2) DEFAULT NULL,
  `fecha_movimiento_alerta` date DEFAULT NULL,
  PRIMARY KEY (`id_alerta`),
  KEY `fk_alertas_producto` (`id_producto`),
  KEY `fk_alertas_tienda` (`id_tienda`),
  KEY `fk_alertas_usuario` (`id_usuario_aten`),
  CONSTRAINT `fk_alertas_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`),
  CONSTRAINT `fk_alertas_tienda` FOREIGN KEY (`id_tienda`) REFERENCES `tiendas` (`id_tienda`),
  CONSTRAINT `fk_alertas_usuario` FOREIGN KEY (`id_usuario_aten`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `alertas_stock`
--

LOCK TABLES `alertas_stock` WRITE;
/*!40000 ALTER TABLE `alertas_stock` DISABLE KEYS */;
INSERT INTO `alertas_stock` VALUES (1,4,1,'stock_bajo',1,'resuelta',NULL,'Resuelta automáticamente al actualizarse el inventario.','2026-04-21','2026-11-04',NULL,NULL),(2,3,1,'stock_bajo',1,'resuelta',NULL,'Resuelta automáticamente al actualizarse el inventario.','2026-04-21','2026-11-04',NULL,NULL),(3,7,1,'stock_bajo',1,'resuelta',NULL,'Resuelta automáticamente al actualizarse el inventario.','2026-04-21','2026-11-04',NULL,NULL),(4,8,1,'sin_stock',1,'resuelta',NULL,'Resuelta automáticamente al actualizarse el inventario.','2026-04-21','2026-11-04',NULL,NULL),(5,13,1,'stock_critico',1,'resuelta',NULL,'Resuelta automáticamente al actualizarse el inventario.','2026-11-04','2026-11-05',5.00,NULL),(6,14,1,'sin_movimiento',0,NULL,NULL,NULL,'2026-11-04',NULL,100.00,'2026-05-17'),(7,23,1,'stock_minimo',1,'resuelta',NULL,'Resuelta automáticamente al actualizarse el inventario.','2026-11-04','2026-11-04',6.00,NULL),(8,24,1,'sin_movimiento',1,'resuelta',NULL,'Resuelta automáticamente al actualizarse el inventario.','2026-11-04','2026-11-04',120.00,'2026-05-13'),(9,25,1,'sin_movimiento',1,'resuelta',NULL,'Resuelta automáticamente al actualizarse el inventario.','2026-11-04','2026-11-04',115.00,'2026-05-13'),(10,27,1,'stock_critico',1,'resuelta',NULL,'Resuelta automáticamente al actualizarse el inventario.','2026-11-04','2026-11-04',20.00,NULL),(11,29,1,'sin_movimiento',1,'resuelta',NULL,'Resuelta automáticamente al actualizarse el inventario.','2026-11-04','2026-11-04',100.00,'2026-05-18'),(12,30,1,'sin_movimiento',0,NULL,NULL,NULL,'2026-11-04',NULL,85.00,'2026-05-18'),(13,31,1,'sin_movimiento',1,'resuelta',NULL,'Resuelta automáticamente al actualizarse el inventario.','2026-11-04','2026-11-05',105.00,'2026-05-18'),(14,36,1,'sin_movimiento',1,'resuelta',NULL,'Resuelta automáticamente al actualizarse el inventario.','2026-11-04','2026-11-05',70.00,'2026-05-25'),(15,1,1,'stock_minimo',1,'resuelta',NULL,'Resuelta automáticamente al actualizarse el inventario.','2026-11-04','2026-11-05',17.00,NULL),(16,23,1,'stock_critico',1,'resuelta',NULL,'Resuelta automáticamente al actualizarse el inventario.','2026-11-04','2026-11-05',6.00,NULL),(17,36,1,'stock_critico',1,'resuelta',NULL,'Resuelta automáticamente al actualizarse el inventario.','2026-11-04','2026-11-04',70.00,NULL),(18,1,1,'stock_critico',1,'resuelta',NULL,'Resuelta automáticamente al actualizarse el inventario.','2026-11-05','2026-11-05',15.00,NULL),(19,27,1,'stock_critico',1,'resuelta',NULL,'Resuelta automáticamente al actualizarse el inventario.','2026-11-05','2026-11-05',15.00,NULL);
/*!40000 ALTER TABLE `alertas_stock` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `anaqueles`
--

DROP TABLE IF EXISTS `anaqueles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `anaqueles` (
  `id_anaquel` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) NOT NULL,
  `descripcion` varchar(150) DEFAULT NULL,
  `id_tienda` int NOT NULL,
  `activo` tinyint NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_anaquel`),
  KEY `fk_anaqueles_tienda` (`id_tienda`),
  CONSTRAINT `fk_anaqueles_tienda` FOREIGN KEY (`id_tienda`) REFERENCES `tiendas` (`id_tienda`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `anaqueles`
--

LOCK TABLES `anaqueles` WRITE;
/*!40000 ALTER TABLE `anaqueles` DISABLE KEYS */;
INSERT INTO `anaqueles` VALUES (8,'A-1','Anaquel A, Sección 1 — Entrada principal Derecha',1,1),(9,'A-2','Anaquel A, Sección 2 — Entrada principal Izquierda',1,1),(10,'B-1','Anaquel B, Sección 1 — Continuación',1,1),(11,'B-2','Anaquel B, Sección 2',1,1),(12,'B-3','Anaquel B, Sección 3',1,1),(13,'B-4','Anaquel B, Sección 4',1,1),(14,'B-5','Anaquel B, Sección 5',1,1),(15,'C-1','Anaquel C, Sección 1',1,1);
/*!40000 ALTER TABLE `anaqueles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `catalogo_productos`
--

DROP TABLE IF EXISTS `catalogo_productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `catalogo_productos` (
  `id_catalogo` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(160) NOT NULL,
  `id_tipo` int NOT NULL,
  `id_color` int NOT NULL,
  `id_unidad` int NOT NULL,
  `presentacion` varchar(40) DEFAULT NULL,
  `descripcion` varchar(150) DEFAULT NULL,
  `activo` tinyint NOT NULL DEFAULT '1',
  `creado_en` date NOT NULL,
  PRIMARY KEY (`id_catalogo`),
  KEY `fk_catalogo_tipo` (`id_tipo`),
  KEY `fk_catalogo_color` (`id_color`),
  KEY `fk_catalogo_unidad` (`id_unidad`),
  CONSTRAINT `fk_catalogo_color` FOREIGN KEY (`id_color`) REFERENCES `colores` (`id_color`),
  CONSTRAINT `fk_catalogo_tipo` FOREIGN KEY (`id_tipo`) REFERENCES `tipos_hilo` (`id_tipo`),
  CONSTRAINT `fk_catalogo_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `unidades` (`id_unidad`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `catalogo_productos`
--

LOCK TABLES `catalogo_productos` WRITE;
/*!40000 ALTER TABLE `catalogo_productos` DISABLE KEYS */;
INSERT INTO `catalogo_productos` VALUES (1,'Hilo Acrílico Blanco 500g',1,1,1,'500g','Hilo acrílico blanco en presentación 500g',1,'2026-04-21'),(2,'Hilo Acrílico Negro 500g',1,2,1,'500g','Hilo acrílico negro en presentación 500g',1,'2026-04-21'),(3,'Hilo Algodón Rojo 250g',3,3,1,'250g','Hilo algodón rojo en presentación 250g',1,'2026-04-21'),(4,'Hilo Nylon Negro 1kg',2,2,1,'1kg','Hilo nylon negro en presentación 1kg',1,'2026-04-21'),(5,'Hilo Poliéster Azul 500g',4,4,1,'500g','Hilo poliéster azul en presentación 500g',1,'2026-04-21'),(6,'Hilo Lana Verde 100g',5,5,2,'100g','Hilo lana verde en presentación 100g',1,'2026-04-21'),(7,'Hilo Mercerizado Rosa 100g',6,7,2,'100g','Hilo mercerizado rosa en presentación 100g',1,'2026-04-21'),(8,'Hilo Elastano Café 50g',7,8,2,'50g','Hilo elastano café en presentación 50g',1,'2026-04-21'),(9,'Hilo Seda Lavanda 50g',8,9,2,'50g','Hilo seda lavanda en presentación 50g',1,'2026-04-21'),(10,'Hilo Licra Blanco 500g',9,1,1,NULL,NULL,1,'2026-11-05');
/*!40000 ALTER TABLE `catalogo_productos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clientes`
--

DROP TABLE IF EXISTS `clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clientes` (
  `id_cliente` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(120) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `rfc` varchar(20) DEFAULT NULL,
  `tipo_cliente` varchar(150) DEFAULT NULL,
  `id_tienda` int NOT NULL,
  `notas` varchar(150) DEFAULT NULL,
  `activo` tinyint NOT NULL DEFAULT '1',
  `creado_en` date NOT NULL,
  `actualizado_en` date DEFAULT NULL,
  PRIMARY KEY (`id_cliente`),
  KEY `fk_clientes_tienda` (`id_tienda`),
  CONSTRAINT `fk_clientes_tienda` FOREIGN KEY (`id_tienda`) REFERENCES `tiendas` (`id_tienda`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clientes`
--

LOCK TABLES `clientes` WRITE;
/*!40000 ALTER TABLE `clientes` DISABLE KEYS */;
INSERT INTO `clientes` VALUES (1,'María González','222-111-0001','','','','Comprador individual',1,'',1,'2026-04-21','2026-05-08'),(2,'J. Pérez','222-111-0002',NULL,NULL,NULL,'Comprador individual',1,NULL,0,'2026-04-21','2026-05-08'),(3,'Artesanías del Valle','222-333-0010','contacto@artval.mx',NULL,NULL,'Taller / Negocio',1,NULL,1,'2026-04-21',NULL),(4,'Ana López Martínez','222-444-5566','ana.lopez@gmail.com','Calle Hidalgo 45, Col. Centro, Puebla','LOMA850312AB3','Comprador individual',1,'Cliente frecuente, prefiere hilo de algodón',1,'2026-05-04',NULL),(5,'Armando Hoyos','2481002236','eugenio@gmail.com','Domingo Arenas #2','HOAR148956CD4','Fisico',1,NULL,0,'2026-10-19','2026-10-19'),(6,'Jhonathan Pérez','2228794561','jhony94@gmail.com',NULL,'CPJH947861D21','Fisico',1,NULL,1,'2026-10-29','2026-10-29');
/*!40000 ALTER TABLE `clientes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `colores`
--

DROP TABLE IF EXISTS `colores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `colores` (
  `id_color` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(60) NOT NULL,
  `codigo_hex` char(7) DEFAULT NULL,
  `activo` tinyint NOT NULL DEFAULT '1',
  `creado_en` date NOT NULL,
  PRIMARY KEY (`id_color`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `colores`
--

LOCK TABLES `colores` WRITE;
/*!40000 ALTER TABLE `colores` DISABLE KEYS */;
INSERT INTO `colores` VALUES (1,'Blanco','#FFFFFF',1,'2026-04-21'),(2,'Negro','#111111',1,'2026-04-21'),(3,'Rojo','#C0392B',1,'2026-04-21'),(4,'Azul','#1976D2',1,'2026-04-21'),(5,'Verde','#388E3C',1,'2026-04-21'),(6,'Amarillo','#F9A825',1,'2026-04-21'),(7,'Rosa','#E91E8C',1,'2026-04-21'),(8,'Café','#795548',1,'2026-04-21'),(9,'Lavanda','#9C27B0',1,'2026-04-21'),(10,'Gris','#9E9E9E',1,'2026-04-21'),(11,'Turquesa','#5dc1b9',1,'2026-11-05');
/*!40000 ALTER TABLE `colores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `compras`
--

DROP TABLE IF EXISTS `compras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `compras` (
  `id_compra` int NOT NULL AUTO_INCREMENT,
  `folio` varchar(30) DEFAULT NULL,
  `id_proveedor` int NOT NULL,
  `id_usuario` int NOT NULL,
  `id_tienda` int NOT NULL,
  `total` decimal(12,2) DEFAULT NULL,
  `notas` varchar(150) DEFAULT NULL,
  `fecha` date NOT NULL,
  PRIMARY KEY (`id_compra`),
  KEY `fk_compras_proveedor` (`id_proveedor`),
  KEY `fk_compras_usuario` (`id_usuario`),
  KEY `fk_compras_tienda` (`id_tienda`),
  CONSTRAINT `fk_compras_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`),
  CONSTRAINT `fk_compras_tienda` FOREIGN KEY (`id_tienda`) REFERENCES `tiendas` (`id_tienda`),
  CONSTRAINT `fk_compras_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compras`
--

LOCK TABLES `compras` WRITE;
/*!40000 ALTER TABLE `compras` DISABLE KEYS */;
INSERT INTO `compras` VALUES (1,'FAC-2026-0042',3,6,1,3000.00,'Pruebas','2026-10-14'),(2,'FAC-2026-0043',1,1,1,26100.00,NULL,'2026-11-05'),(3,NULL,2,1,1,1200.00,NULL,'2026-11-05');
/*!40000 ALTER TABLE `compras` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalle_compra`
--

DROP TABLE IF EXISTS `detalle_compra`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalle_compra` (
  `id_detalle` int NOT NULL AUTO_INCREMENT,
  `id_compra` int NOT NULL,
  `id_producto` int NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `precio_unit` decimal(10,2) NOT NULL,
  `subtotal` decimal(12,2) DEFAULT NULL,
  PRIMARY KEY (`id_detalle`),
  KEY `fk_dcompra_compra` (`id_compra`),
  KEY `fk_dcompra_producto` (`id_producto`),
  CONSTRAINT `fk_dcompra_compra` FOREIGN KEY (`id_compra`) REFERENCES `compras` (`id_compra`),
  CONSTRAINT `fk_dcompra_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_compra`
--

LOCK TABLES `detalle_compra` WRITE;
/*!40000 ALTER TABLE `detalle_compra` DISABLE KEYS */;
INSERT INTO `detalle_compra` VALUES (1,1,23,20.00,120.00,2400.00),(2,1,1,15.00,40.00,600.00),(3,2,27,100.00,69.00,6900.00),(4,2,1,120.00,40.00,4800.00),(5,2,13,50.00,120.00,6000.00),(6,2,23,70.00,120.00,8400.00),(7,3,27,15.00,80.00,1200.00);
/*!40000 ALTER TABLE `detalle_compra` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalle_venta`
--

DROP TABLE IF EXISTS `detalle_venta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalle_venta` (
  `id_detalle` int NOT NULL AUTO_INCREMENT,
  `id_venta` int NOT NULL,
  `id_producto` int NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `precio_unit` decimal(10,2) NOT NULL,
  `subtotal` decimal(12,2) DEFAULT NULL,
  PRIMARY KEY (`id_detalle`),
  KEY `fk_dventa_venta` (`id_venta`),
  KEY `fk_dventa_producto` (`id_producto`),
  CONSTRAINT `fk_dventa_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`),
  CONSTRAINT `fk_dventa_venta` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id_venta`)
) ENGINE=InnoDB AUTO_INCREMENT=95 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_venta`
--

LOCK TABLES `detalle_venta` WRITE;
/*!40000 ALTER TABLE `detalle_venta` DISABLE KEYS */;
INSERT INTO `detalle_venta` VALUES (1,1,2,1.00,65.00,65.00),(2,2,6,1.00,130.00,130.00),(3,3,11,1.00,89.00,89.00),(4,4,14,5.00,102.00,510.00),(5,4,1,20.00,65.00,1300.00),(6,4,11,10.00,89.00,890.00),(7,5,1,5.00,65.00,325.00),(8,5,2,25.00,65.00,1625.00),(9,6,1,19.00,65.00,1235.00),(10,6,2,1.00,65.00,65.00),(11,7,16,10.00,180.00,1800.00),(12,7,11,5.00,89.00,445.00),(13,8,16,20.00,180.00,3600.00),(14,8,13,30.00,170.00,5100.00),(15,9,13,20.00,170.00,3400.00),(16,10,18,50.00,115.00,5750.00),(17,11,16,20.00,180.00,3600.00),(18,12,18,25.00,115.00,2875.00),(19,13,18,5.00,115.00,575.00),(20,14,1,5.00,65.00,325.00),(21,15,2,4.00,65.00,260.00),(22,16,1,5.00,65.00,325.00),(23,17,26,15.00,95.00,1425.00),(24,18,27,25.00,85.00,2125.00),(25,19,27,25.00,85.00,2125.00),(26,20,6,16.00,130.00,2080.00),(27,21,28,5.00,85.00,425.00),(28,22,22,50.00,100.00,5000.00),(29,23,15,1.00,85.00,85.00),(30,24,15,1.00,85.00,85.00),(31,25,27,5.00,85.00,425.00),(32,26,26,10.00,95.00,950.00),(33,27,16,10.00,180.00,1800.00),(34,28,22,10.00,100.00,1000.00),(35,29,13,10.00,170.00,1700.00),(36,30,16,5.00,180.00,900.00),(37,31,2,5.00,65.00,325.00),(38,32,13,10.00,170.00,1700.00),(39,33,1,10.00,65.00,650.00),(40,34,27,5.00,85.00,425.00),(41,35,27,5.00,85.00,425.00),(42,36,1,10.00,65.00,650.00),(43,37,15,10.00,85.00,850.00),(44,38,16,5.00,180.00,900.00),(45,39,15,10.00,85.00,850.00),(46,40,1,50.00,65.00,3250.00),(47,41,11,2.00,89.00,178.00),(48,42,26,21.00,95.00,1995.00),(49,43,26,9.00,95.00,855.00),(50,44,22,10.00,100.00,1000.00),(51,45,27,15.00,85.00,1275.00),(52,46,1,5.00,65.00,325.00),(53,47,2,5.00,65.00,325.00),(54,48,15,3.00,85.00,255.00),(55,49,6,10.00,130.00,1300.00),(56,50,13,10.00,170.00,1700.00),(57,51,14,12.00,102.00,1224.00),(58,52,27,50.00,85.00,4250.00),(59,53,14,33.00,102.00,3366.00),(60,54,27,20.00,85.00,1700.00),(61,55,28,5.00,85.00,425.00),(62,56,6,12.00,130.00,1560.00),(63,57,6,8.00,130.00,1040.00),(64,58,15,10.00,85.00,850.00),(65,59,6,10.00,130.00,1300.00),(66,60,1,5.00,65.00,325.00),(67,61,18,1.00,115.00,115.00),(68,62,15,10.00,85.00,850.00),(69,63,6,10.00,130.00,1300.00),(70,64,22,15.00,100.00,1500.00),(71,65,6,10.00,130.00,1300.00),(72,66,15,5.00,85.00,425.00),(73,67,15,10.00,85.00,850.00),(74,68,13,10.00,170.00,1700.00),(75,69,22,15.00,100.00,1500.00),(76,70,1,32.00,65.00,2080.00),(77,70,22,15.00,100.00,1500.00),(78,71,30,15.00,75.00,1125.00),(79,72,31,15.00,75.00,1125.00),(80,73,27,10.00,85.00,850.00),(81,74,36,15.00,75.00,1125.00),(82,75,36,15.00,75.00,1125.00),(83,76,11,1.00,89.00,89.00),(84,77,13,5.00,170.00,850.00),(85,78,27,5.00,100.00,500.00),(86,79,13,5.00,170.00,850.00),(87,80,27,15.00,100.00,1500.00),(88,80,23,10.00,156.00,1560.00),(89,80,1,5.00,52.00,260.00),(90,81,23,5.00,156.00,780.00),(91,82,27,5.00,100.00,500.00),(92,82,1,2.00,52.00,104.00),(93,82,36,20.00,75.00,1500.00),(94,82,31,35.00,75.00,2625.00);
/*!40000 ALTER TABLE `detalle_venta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `equipo_trabajo`
--

DROP TABLE IF EXISTS `equipo_trabajo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `equipo_trabajo` (
  `id_empleado` int unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `puesto` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sueldo` decimal(12,2) DEFAULT NULL,
  `id_tienda` int unsigned NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `creado_en` date NOT NULL,
  `actualizado_en` date DEFAULT NULL,
  PRIMARY KEY (`id_empleado`),
  KEY `equipo_trabajo_id_tienda_activo_index` (`id_tienda`,`activo`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `equipo_trabajo`
--

LOCK TABLES `equipo_trabajo` WRITE;
/*!40000 ALTER TABLE `equipo_trabajo` DISABLE KEYS */;
INSERT INTO `equipo_trabajo` VALUES (1,'José Hernández','Montacarguista','2489635471',2000.00,1,1,'2026-11-05','2026-11-05'),(2,'Víctor Meneses','Cargador','2223124796',2000.00,1,1,'2026-11-05',NULL);
/*!40000 ALTER TABLE `equipo_trabajo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gastos`
--

DROP TABLE IF EXISTS `gastos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gastos` (
  `id_gastos` int NOT NULL AUTO_INCREMENT,
  `concepto` varchar(150) NOT NULL,
  `categoria` varchar(100) DEFAULT NULL,
  `monto` decimal(12,2) NOT NULL,
  `id_tienda` int NOT NULL,
  `id_usuario` int NOT NULL,
  `notas` varchar(150) DEFAULT NULL,
  `fecha` date NOT NULL,
  `id_empleado` int unsigned DEFAULT NULL,
  `origen_empleado` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id_gastos`),
  KEY `fk_gastos_tienda` (`id_tienda`),
  KEY `fk_gastos_usuario` (`id_usuario`),
  KEY `gastos_id_empleado_index` (`id_empleado`),
  KEY `gastos_origen_empleado_index` (`origen_empleado`),
  CONSTRAINT `fk_gastos_tienda` FOREIGN KEY (`id_tienda`) REFERENCES `tiendas` (`id_tienda`),
  CONSTRAINT `fk_gastos_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gastos`
--

LOCK TABLES `gastos` WRITE;
/*!40000 ALTER TABLE `gastos` DISABLE KEYS */;
INSERT INTO `gastos` VALUES (1,'Recibo de Luz','servicio_luz',1500.00,1,1,NULL,'2026-11-02',NULL,NULL),(2,'Recibo de Agua','servicio_agua',82.00,1,1,'Pago Mensual','2026-11-02',NULL,NULL),(3,'Nómina quincenal','sueldo',3500.00,1,1,NULL,'2026-11-02',6,'usuario');
/*!40000 ALTER TABLE `gastos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `logs_respaldo`
--

DROP TABLE IF EXISTS `logs_respaldo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `logs_respaldo` (
  `id_log` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `fecha_generacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `nombre_archivo` varchar(255) NOT NULL,
  `tamano_bytes` bigint NOT NULL,
  `tipo_operacion` varchar(50) NOT NULL DEFAULT 'Desconocido',
  `exito` tinyint(1) NOT NULL DEFAULT '1',
  `notas` text,
  PRIMARY KEY (`id_log`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `logs_respaldo_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logs_respaldo`
--

LOCK TABLES `logs_respaldo` WRITE;
/*!40000 ALTER TABLE `logs_respaldo` DISABLE KEYS */;
INSERT INTO `logs_respaldo` VALUES (2,1,'2026-05-11 15:16:08','respaldo_hlazcano_2026-05-11_21-16-07.sql',32104,'Desconocido',1,'Respaldo generado exitosamente'),(3,1,'2026-05-12 23:34:55','respaldo_hlazcano_2026-05-13_05-34-55.sql',32976,'Desconocido',1,'Respaldo generado exitosamente'),(4,7,'2026-05-13 13:58:51','respaldo_hlazcano_2026-05-13_19-58-51.sql',33702,'Desconocido',1,'Respaldo generado exitosamente'),(5,9,'2026-05-13 16:32:18','respaldo_hlazcano_2026-05-13_22-32-17.sql',34023,'Desconocido',1,'Respaldo generado exitosamente'),(6,6,'2026-05-13 21:57:58','respaldo_hlazcano_2026-05-14_03-57-58.zip',6198,'Desconocido',1,'Respaldo ZIP generado exitosamente'),(7,6,'2026-05-14 14:37:49','respaldo_hlazcano_2026-05-14_20-37-48.zip',6268,'Exportar',1,'Respaldo ZIP generado exitosamente'),(8,6,'2026-05-15 21:52:15','respaldo_hlazcano_2026-05-16_03-52-14.sql',35470,'Desconocido',1,'Respaldo generado exitosamente'),(9,6,'2026-05-15 21:58:52','respaldo_hlazcano_2026-05-16_03-58-51.sql',35597,'Desconocido',1,'Respaldo generado exitosamente'),(10,6,'2026-05-15 22:14:28','respaldo_hlazcano_2026-05-16T04-14-04.zip',6394,'Desconocido',1,'Importado desde ZIP'),(11,10,'2026-05-18 00:08:17','respaldo_hlazcano_2026-05-18T06-07-54.zip',7514,'Desconocido',1,'Importado desde ZIP'),(12,10,'2026-05-18 00:09:49','respaldo_hlazcano_2026-05-18T06-09-32.zip',7544,'Desconocido',1,'Importado desde ZIP'),(13,6,'2026-05-18 12:09:58','respaldo_hlazcano_2026-05-18_18-09-57.sql',41173,'Desconocido',1,'Respaldo generado exitosamente'),(14,6,'2026-05-18 14:03:47','respaldo_hlazcano_2026-05-18T18-39-40.zip',7584,'Desconocido',1,'Importado desde ZIP'),(15,10,'2026-05-18 15:17:56','respaldo_hlazcano_2026-05-18T21-17-38.zip',7665,'Desconocido',1,'Importado desde ZIP'),(16,12,'2026-05-18 15:45:03','respaldo_hlazcano_2026-05-18T21-44-41.zip',7776,'Desconocido',1,'Importado desde ZIP'),(17,12,'2026-08-20 14:06:39','respaldo_hlazcano_2026-08-20T20-06-20.zip',8554,'Desconocido',1,'Importado desde ZIP'),(18,6,'2026-10-15 19:48:25','respaldo_hlazcano_2026-10-16_01-48-23.zip',25710,'exportacion',1,NULL),(19,6,'2026-10-15 19:53:40','respaldo_hlazcano_2026-10-16_01-53-39.zip',25786,'exportacion',1,NULL),(20,1,'2026-11-04 17:20:11','respaldo_hlazcano_2026-11-04_00-48-37.zip',25880,'restauracion',1,'Restauradas 30 tablas en hlazcano_db.'),(21,1,'2026-11-04 17:44:45','respaldo_hlazcano_2026-11-04_23-44-44.zip',27117,'exportacion',1,NULL),(22,1,'2026-11-05 22:01:08','respaldo_hlazcano_2026-11-06_04-01-07.zip',28024,'exportacion',1,NULL);
/*!40000 ALTER TABLE `logs_respaldo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_10_14_000000_expand_usuarios_password_column',1),(5,'2026_10_14_000001_create_logs_respaldo_table',2),(6,'2026_10_28_000000_consolidate_to_single_store',3),(7,'2026_10_28_000001_rename_single_store',4),(8,'2026_10_29_000000_add_telefono_to_usuarios_table',5),(9,'2026_11_03_000000_create_password_reset_tokens_table_if_missing',6),(10,'2026_11_03_000001_add_force_password_change_to_usuarios',6),(11,'2026_11_03_000002_add_alert_state_snapshots',7),(12,'2026_11_03_000003_add_employee_to_gastos_table',7),(13,'2026_11_03_000004_add_salary_and_create_team_members',7),(14,'2026_11_03_000005_add_employee_source_to_gastos',7);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `movimientos_inventario`
--

DROP TABLE IF EXISTS `movimientos_inventario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `movimientos_inventario` (
  `id_movimiento` int NOT NULL AUTO_INCREMENT,
  `id_producto` int NOT NULL,
  `tipo_movimiento` varchar(150) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `stock_anterior` decimal(10,2) NOT NULL,
  `stock_nuevo` decimal(10,2) NOT NULL,
  `referencia_id` int DEFAULT NULL,
  `referencia_tipo` varchar(150) DEFAULT NULL,
  `id_usuario` int NOT NULL,
  `notas` varchar(150) DEFAULT NULL,
  `fecha` date NOT NULL,
  PRIMARY KEY (`id_movimiento`),
  KEY `fk_movimientos_producto` (`id_producto`),
  KEY `fk_movimientos_usuario` (`id_usuario`),
  CONSTRAINT `fk_movimientos_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`),
  CONSTRAINT `fk_movimientos_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `movimientos_inventario`
--

LOCK TABLES `movimientos_inventario` WRITE;
/*!40000 ALTER TABLE `movimientos_inventario` DISABLE KEYS */;
/*!40000 ALTER TABLE `movimientos_inventario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notificaciones`
--

DROP TABLE IF EXISTS `notificaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notificaciones` (
  `id_notif` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `tipo` varchar(150) DEFAULT NULL,
  `mensaje` varchar(250) NOT NULL,
  `referencia_id` int DEFAULT NULL,
  `referencia_tipo` varchar(30) DEFAULT NULL,
  `leida` tinyint NOT NULL DEFAULT '0',
  `fecha` date NOT NULL,
  PRIMARY KEY (`id_notif`),
  KEY `fk_notif_usuario` (`id_usuario`),
  CONSTRAINT `fk_notif_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notificaciones`
--

LOCK TABLES `notificaciones` WRITE;
/*!40000 ALTER TABLE `notificaciones` DISABLE KEYS */;
/*!40000 ALTER TABLE `notificaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
INSERT INTO `password_reset_tokens` VALUES ('alexis.golo1104@gmail.com','$2y$12$QfF1nkSd9/22tUZ49MgQruqwOovvnYBZpqD.mJ7uIH.44UgkWF6Su','2026-11-04 05:38:15');
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `productos`
--

DROP TABLE IF EXISTS `productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `productos` (
  `id_producto` int NOT NULL AUTO_INCREMENT,
  `id_catalogo` int NOT NULL,
  `id_tipo` int NOT NULL,
  `id_color` int NOT NULL,
  `id_unidad` int NOT NULL,
  `id_proveedor` int DEFAULT NULL,
  `id_anaquel` int DEFAULT NULL,
  `id_tienda` int NOT NULL,
  `presentacion` varchar(40) DEFAULT NULL,
  `posicion_anaquel` varchar(50) DEFAULT NULL,
  `stock_actual` decimal(10,2) NOT NULL DEFAULT '0.00',
  `stock_minimo` decimal(10,2) NOT NULL DEFAULT '0.00',
  `precio_compra` decimal(10,2) DEFAULT NULL,
  `precio_venta` decimal(10,2) DEFAULT NULL,
  `activo` tinyint NOT NULL DEFAULT '1',
  `creado_en` date NOT NULL,
  `actualizado_en` date DEFAULT NULL,
  PRIMARY KEY (`id_producto`),
  KEY `fk_productos_catalogo` (`id_catalogo`),
  KEY `fk_productos_tipo` (`id_tipo`),
  KEY `fk_productos_color` (`id_color`),
  KEY `fk_productos_unidad` (`id_unidad`),
  KEY `fk_productos_proveedor` (`id_proveedor`),
  KEY `fk_productos_anaquel` (`id_anaquel`),
  KEY `fk_productos_tienda` (`id_tienda`),
  CONSTRAINT `fk_productos_anaquel` FOREIGN KEY (`id_anaquel`) REFERENCES `anaqueles` (`id_anaquel`),
  CONSTRAINT `fk_productos_catalogo` FOREIGN KEY (`id_catalogo`) REFERENCES `catalogo_productos` (`id_catalogo`),
  CONSTRAINT `fk_productos_color` FOREIGN KEY (`id_color`) REFERENCES `colores` (`id_color`),
  CONSTRAINT `fk_productos_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`),
  CONSTRAINT `fk_productos_tienda` FOREIGN KEY (`id_tienda`) REFERENCES `tiendas` (`id_tienda`),
  CONSTRAINT `fk_productos_tipo` FOREIGN KEY (`id_tipo`) REFERENCES `tipos_hilo` (`id_tipo`),
  CONSTRAINT `fk_productos_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `unidades` (`id_unidad`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productos`
--

LOCK TABLES `productos` WRITE;
/*!40000 ALTER TABLE `productos` DISABLE KEYS */;
INSERT INTO `productos` VALUES (1,2,1,2,1,NULL,NULL,1,'500g',NULL,135.00,15.00,40.00,52.00,1,'2026-04-21','2026-11-05'),(2,2,1,2,1,1,NULL,1,'800g',NULL,10.00,5.00,40.00,65.00,0,'2026-04-21','2026-10-14'),(3,3,3,3,1,3,NULL,1,'250g',NULL,0.00,5.00,50.00,78.00,0,'2026-04-21','2026-05-08'),(4,4,2,2,1,1,NULL,1,'1kg',NULL,2.00,5.00,70.00,110.00,0,'2026-04-21','2026-05-05'),(5,5,4,4,1,3,NULL,1,'500g',NULL,10.00,5.00,35.00,55.00,0,'2026-04-21','2026-05-07'),(6,6,5,5,1,2,NULL,1,'100g',NULL,10.00,4.00,85.00,130.00,0,'2026-04-21','2026-05-18'),(7,7,6,7,2,2,NULL,1,'100g',NULL,0.00,5.00,60.00,90.00,0,'2026-04-21','2026-05-07'),(8,8,7,8,2,1,NULL,1,'50g',NULL,0.00,3.00,45.00,70.00,0,'2026-04-21','2026-05-05'),(9,9,8,9,2,2,NULL,1,'50g',NULL,6.00,3.00,95.00,145.00,0,'2026-04-21','2026-05-05'),(10,2,1,2,1,2,NULL,1,'1000kg',NULL,0.00,5.00,50.00,80.00,0,'2026-05-04','2026-05-08'),(11,3,3,3,1,3,NULL,1,'20kg',NULL,1.00,5.00,45.00,89.00,0,'2026-05-05','2026-10-14'),(12,6,5,5,1,2,NULL,1,'50kg',NULL,15.00,5.00,80.00,120.00,0,'2026-05-05','2026-05-05'),(13,8,7,8,2,NULL,NULL,1,'50g',NULL,55.00,15.00,120.00,170.00,1,'2026-05-05','2026-11-05'),(14,9,8,9,2,NULL,NULL,1,'50g',NULL,100.00,15.00,85.00,102.00,1,'2026-05-08','2026-11-04'),(15,5,4,4,1,1,NULL,1,'500g',NULL,35.00,10.00,65.00,85.00,0,'2026-05-08','2026-10-14'),(16,1,1,1,1,3,NULL,1,'MG',NULL,30.00,20.00,140.00,200.00,0,'2026-05-08','2026-05-18'),(17,6,5,5,5,3,NULL,1,'hu',NULL,120.00,100.00,100.00,180.00,0,'2026-05-08','2026-05-08'),(18,4,2,2,1,3,NULL,1,'1Kg',NULL,1.00,5.00,85.00,115.00,0,'2026-05-12','2026-10-14'),(22,7,6,7,3,3,NULL,1,'100g',NULL,35.00,20.00,85.00,100.00,0,'2026-05-13','2026-10-14'),(23,7,6,7,2,NULL,NULL,1,'100g',NULL,76.00,15.00,120.00,156.00,1,'2026-05-13','2026-11-05'),(24,5,4,4,1,NULL,NULL,1,'500g',NULL,120.00,15.00,25.00,30.00,1,'2026-05-13','2026-11-04'),(25,8,7,8,1,1,NULL,1,'50g',NULL,115.00,15.00,79.00,95.00,0,'2026-05-13','2026-11-04'),(26,3,3,3,1,3,NULL,1,'250g',NULL,60.00,15.00,79.00,95.00,0,'2026-05-13','2026-10-14'),(27,1,1,1,1,NULL,NULL,1,'500g',NULL,130.00,15.00,80.00,95.00,1,'2026-05-13','2026-11-05'),(28,9,8,9,4,1,NULL,1,'50g',NULL,90.00,20.00,65.00,85.00,0,'2026-05-15','2026-10-14'),(29,5,4,4,1,NULL,NULL,1,'500g',NULL,100.00,15.00,150.00,210.00,0,'2026-05-18','2026-11-04'),(30,6,5,5,2,NULL,NULL,1,'100g',NULL,85.00,15.00,54.00,75.00,1,'2026-05-18','2026-11-04'),(31,4,2,2,1,3,NULL,1,'1Kg',NULL,70.00,15.00,59.00,75.00,1,'2026-05-18','2026-11-05'),(32,6,5,5,1,3,NULL,1,'',NULL,40.00,5.00,85.00,90.00,0,'2026-05-19','2026-10-14'),(34,3,3,3,1,3,NULL,1,'250',NULL,120.00,5.00,55.00,85.00,0,'2026-05-22','2026-10-14'),(35,3,3,3,1,3,NULL,1,'250',NULL,200.00,5.00,40.00,70.00,0,'2026-05-22','2026-05-22'),(36,3,3,3,1,NULL,NULL,1,'250g',NULL,50.00,15.00,45.00,75.00,1,'2026-05-25','2026-11-05'),(37,1,1,1,1,3,NULL,1,'',NULL,60.00,20.00,56.00,80.00,0,'2026-09-28','2026-10-14');
/*!40000 ALTER TABLE `productos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `proveedores`
--

DROP TABLE IF EXISTS `proveedores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `proveedores` (
  `id_proveedor` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(120) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `notas` varchar(150) DEFAULT NULL,
  `activo` tinyint NOT NULL DEFAULT '1',
  `creado_en` date NOT NULL,
  PRIMARY KEY (`id_proveedor`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `proveedores`
--

LOCK TABLES `proveedores` WRITE;
/*!40000 ALTER TABLE `proveedores` DISABLE KEYS */;
INSERT INTO `proveedores` VALUES (1,'Textiles del Norte S.A.','222-100-2000','ventas@textilesnorte.mx','Blvd. Industrial 45, Puebla','Proveedor principal hilos sintéticos',1,'2026-04-21'),(2,'Hilos Premium MX','222-300-4000','contacto@hilospremium.mx','Calle Textil 88, Tlaxcala','Especialistas en lana y seda',1,'2026-04-21'),(3,'Distribuidora Central','800-555-1234','pedidos@distcentral.com.mx','Av. Reforma 200, CDMX','Distribución a nivel nacional',1,'2026-04-21');
/*!40000 ALTER TABLE `proveedores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sesiones`
--

DROP TABLE IF EXISTS `sesiones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sesiones` (
  `id_sesion` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `ip` varchar(45) NOT NULL,
  `dispositivo` varchar(200) DEFAULT NULL,
  `inicio` date NOT NULL,
  `fin` date DEFAULT NULL,
  PRIMARY KEY (`id_sesion`),
  KEY `fk_sesiones_usuario` (`id_usuario`),
  CONSTRAINT `fk_sesiones_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sesiones`
--

LOCK TABLES `sesiones` WRITE;
/*!40000 ALTER TABLE `sesiones` DISABLE KEYS */;
/*!40000 ALTER TABLE `sesiones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('XJw5fPyeIY07Q726Z1ApRJiJYKDglEi5lg5gxMBW',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoieDJ1TFU0elJMbXV3YkE3THpEQzBBV2Fla1dGRDZReVUyMmxBaDZMSCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mzg6Imh0dHA6Ly9taXByb3llY3RvaW50ZWdyYWRvci50ZXN0L2xvZ2luIjt9fQ==',1793922383),('ZGujvBF18O39t7yno26WOd5NMtAwMrV6KIEoJIzx',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoibXFka2FkOEFSdG9tamY2Q05PaEdndlpjbjVta1hTcWpCaWpHenphayI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NDI6Imh0dHA6Ly9taXByb3llY3RvaW50ZWdyYWRvci50ZXN0L3Jlc3BhbGRvcyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7fQ==',1793937668);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tickets`
--

DROP TABLE IF EXISTS `tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tickets` (
  `id_tickets` int NOT NULL AUTO_INCREMENT,
  `id_venta` int NOT NULL,
  `contenido` varchar(250) DEFAULT NULL,
  `canal_envio` varchar(150) DEFAULT NULL,
  `enviado` tinyint NOT NULL DEFAULT '0',
  `fecha` date NOT NULL,
  PRIMARY KEY (`id_tickets`),
  KEY `fk_tickets_venta` (`id_venta`),
  CONSTRAINT `fk_tickets_venta` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id_venta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tickets`
--

LOCK TABLES `tickets` WRITE;
/*!40000 ALTER TABLE `tickets` DISABLE KEYS */;
/*!40000 ALTER TABLE `tickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tiendas`
--

DROP TABLE IF EXISTS `tiendas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tiendas` (
  `id_tienda` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(120) NOT NULL,
  `direccion` varchar(200) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `activo` tinyint NOT NULL DEFAULT '1',
  `creado_en` date NOT NULL,
  PRIMARY KEY (`id_tienda`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tiendas`
--

LOCK TABLES `tiendas` WRITE;
/*!40000 ALTER TABLE `tiendas` DISABLE KEYS */;
INSERT INTO `tiendas` VALUES (1,'HLazcano','Av. Principal 100, Santa María Texmelucan','222-555-0001',1,'2026-04-21');
/*!40000 ALTER TABLE `tiendas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipos_hilo`
--

DROP TABLE IF EXISTS `tipos_hilo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_hilo` (
  `id_tipo` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) NOT NULL,
  `descripcion` varchar(200) DEFAULT NULL,
  `activo` tinyint NOT NULL DEFAULT '1',
  `creado_en` date NOT NULL,
  PRIMARY KEY (`id_tipo`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tipos_hilo`
--

LOCK TABLES `tipos_hilo` WRITE;
/*!40000 ALTER TABLE `tipos_hilo` DISABLE KEYS */;
INSERT INTO `tipos_hilo` VALUES (1,'Acrílico','Fibra sintética, lavable y resistente',1,'2026-04-21'),(2,'Nylon','Alta resistencia y elasticidad',1,'2026-04-21'),(3,'Algodón','Fibra natural, hipoalergénica',1,'2026-04-21'),(4,'Poliéster','Resistente a la humedad y desgaste',1,'2026-04-21'),(5,'Lana','Fibra animal, aislante térmica',1,'2026-04-21'),(6,'Mercerizado','Algodón tratado con brillo sedoso',1,'2026-04-21'),(7,'Elastano','Alta elasticidad, mezcla sintética',1,'2026-04-21'),(8,'Seda','Fibra natural de alta calidad y brillo',1,'2026-04-21'),(9,'Licra','Alta elasticidad, base del calcetín',1,'2026-11-05');
/*!40000 ALTER TABLE `tipos_hilo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `unidades`
--

DROP TABLE IF EXISTS `unidades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `unidades` (
  `id_unidad` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) NOT NULL,
  `nombre` varchar(60) NOT NULL,
  PRIMARY KEY (`id_unidad`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `unidades`
--

LOCK TABLES `unidades` WRITE;
/*!40000 ALTER TABLE `unidades` DISABLE KEYS */;
INSERT INTO `unidades` VALUES (1,'kg','Kilogramo'),(2,'g','Gramo'),(3,'pieza','Pieza'),(4,'rollo','Rollo'),(5,'cono','Cono');
/*!40000 ALTER TABLE `unidades` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id_usuario` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(120) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `rol` varchar(100) NOT NULL,
  `id_tienda` int NOT NULL,
  `activo` tinyint NOT NULL DEFAULT '1',
  `ultimo_acceso` date DEFAULT NULL,
  `ip_ultimo` varchar(45) DEFAULT NULL,
  `creado_en` date NOT NULL,
  `reset_code` varchar(6) DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `debe_cambiar_contrasena` tinyint(1) NOT NULL DEFAULT '0',
  `sueldo` decimal(12,2) DEFAULT NULL,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `correo` (`correo`),
  KEY `fk_usuarios_tienda` (`id_tienda`),
  CONSTRAINT `fk_usuarios_tienda` FOREIGN KEY (`id_tienda`) REFERENCES `tiendas` (`id_tienda`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'Hernán Meneses','hilossistema@gmail.com','$2y$12$SgcWqv54Dnly7qX4J4Qq1e.td/ew2QsV1mTyiy.c6R2ylmUlAIOyK','superadmin',1,1,'2026-11-06','127.0.0.1','2026-04-21',NULL,NULL,'2482077665',0,NULL),(6,'Alexis Godoy','alexis.golo1104@gmail.com','$2y$12$iAdWP3qGPiKn.ou0Qm4rn.gZ2lrYOeGARv2H2.xZOhLm7ElRdssqy','admin',1,1,'2026-11-05','127.0.0.1','2026-05-13','132792','2026-05-13 19:36:46','2482286663',0,3500.00),(7,'Ahirys Cano','ahirysdairacanoestrada@gmail.com','Ari-2026','admin',1,1,'2026-05-13','127.0.0.1','2026-05-13',NULL,NULL,'2481190198',0,3500.00),(9,'Tomas Gonzalez','alexisgl0104@gmail.com','Tomas123','admin',1,1,'2026-05-13','127.0.0.1','2026-05-13',NULL,NULL,'2487891456',0,3500.00),(10,'Admin','hilossitema@gmail.com','Admin123','admin',1,1,'2026-05-18','127.0.0.1','2026-05-18',NULL,NULL,'2481225850',0,3500.00),(12,'Administrador','sistemahilos094@gmail.com','Administrador123','admin',1,1,'2026-10-14','127.0.0.1','2026-05-18',NULL,NULL,'2481002236',0,3500.00),(16,'Clark Kent','kalelkrypton2976@gmail.com','$2y$12$Qo41MMmqCXVZfvHUtGpa1uUCotXOfQd.d9PlV6d57n8Dso.DcprN.','empleado',1,1,'2026-11-04','127.0.0.1','2026-10-19',NULL,NULL,'2481568987',0,2500.00);
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ventas`
--

DROP TABLE IF EXISTS `ventas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ventas` (
  `id_venta` int NOT NULL AUTO_INCREMENT,
  `folio` varchar(20) NOT NULL,
  `id_usuario` int NOT NULL,
  `id_cliente` int DEFAULT NULL,
  `id_tienda` int NOT NULL,
  `descuento` decimal(5,2) DEFAULT '0.00',
  `subtotal` decimal(12,2) DEFAULT NULL,
  `total` decimal(12,2) DEFAULT NULL,
  `notas` varchar(150) DEFAULT NULL,
  `fecha` date NOT NULL,
  PRIMARY KEY (`id_venta`),
  KEY `fk_ventas_usuario` (`id_usuario`),
  KEY `fk_ventas_cliente` (`id_cliente`),
  KEY `fk_ventas_tienda` (`id_tienda`),
  CONSTRAINT `fk_ventas_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`),
  CONSTRAINT `fk_ventas_tienda` FOREIGN KEY (`id_tienda`) REFERENCES `tiendas` (`id_tienda`),
  CONSTRAINT `fk_ventas_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=83 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ventas`
--

LOCK TABLES `ventas` WRITE;
/*!40000 ALTER TABLE `ventas` DISABLE KEYS */;
INSERT INTO `ventas` VALUES (1,'V-20260505-8442',1,NULL,1,0.00,65.00,65.00,'','2026-05-05'),(2,'V-20260505-2820',1,4,1,0.00,130.00,130.00,'','2026-05-05'),(3,'V-20260508-9054',1,NULL,1,0.00,89.00,89.00,'','2026-05-08'),(4,'V-20260508-6437',1,1,1,0.00,2700.00,2700.00,'','2026-05-08'),(5,'V-20260508-2674',1,4,1,0.00,1950.00,1950.00,'','2026-05-08'),(6,'V-20260508-0730',1,NULL,1,0.00,1300.00,1300.00,'','2026-05-08'),(7,'V-20260508-4158',1,NULL,1,0.00,2245.00,2245.00,'','2026-05-08'),(8,'V-20260513-5990',1,NULL,1,0.00,8700.00,8700.00,'','2026-05-12'),(9,'V-20260513-9559',1,3,1,0.00,3400.00,3400.00,'','2026-05-12'),(10,'V-20260513-5102',1,NULL,1,0.00,5750.00,5750.00,'','2026-05-12'),(11,'V-20260513-8642',1,3,1,0.00,3600.00,3600.00,'','2026-05-12'),(12,'V-20260513-6407',1,NULL,1,0.00,2875.00,2875.00,'','2026-05-12'),(13,'V-20260513-7787',1,NULL,1,0.00,575.00,575.00,'','2026-05-12'),(14,'V-20260513-3006',7,1,1,0.00,325.00,325.00,'','2026-05-13'),(15,'V-20260513-6431',9,4,1,0.00,260.00,260.00,'','2026-05-13'),(16,'V-20260513-4830',9,NULL,1,0.00,325.00,325.00,'','2026-05-13'),(17,'V-20260514-2741',6,NULL,1,0.00,1425.00,1425.00,'','2026-05-13'),(18,'V-20260514-6947',6,1,1,0.00,2125.00,2125.00,'','2026-05-14'),(19,'V-20260516-9906',6,4,1,0.00,2125.00,2125.00,'','2026-05-15'),(20,'V-20260516-3629',6,3,1,0.00,2080.00,2080.00,'','2026-05-15'),(21,'V-20260516-0221',6,1,1,0.00,425.00,425.00,'','2026-05-15'),(22,'V-20260516-0121',6,3,1,0.00,5000.00,5000.00,'','2026-05-15'),(23,'V-20260516-5435',6,1,1,0.00,85.00,85.00,'','2026-05-15'),(24,'V-20260516-1624',6,1,1,0.00,85.00,85.00,'','2026-05-15'),(25,'V-20260516-6523',6,4,1,0.00,425.00,425.00,'','2026-05-15'),(26,'V-20260517-2111',6,4,1,0.00,950.00,950.00,'','2026-05-17'),(27,'V-20260517-0733',6,1,1,0.00,1800.00,1800.00,'','2026-05-17'),(28,'V-20260517-1104',6,3,1,0.00,1000.00,1000.00,'','2026-05-17'),(29,'V-20260517-8083',6,NULL,1,0.00,1700.00,1700.00,'','2026-05-17'),(30,'V-20260517-7513',6,4,1,0.00,900.00,900.00,'','2026-05-17'),(31,'V-20260517-7292',6,1,1,0.00,325.00,325.00,'','2026-05-17'),(32,'V-20260517-1662',6,3,1,0.00,1700.00,1700.00,'','2026-05-17'),(33,'V-20260517-0823',6,NULL,1,0.00,650.00,650.00,'','2026-05-17'),(34,'V-20260517-9375',6,3,1,0.00,425.00,425.00,'','2026-05-17'),(35,'V-20260517-9719',6,3,1,0.00,425.00,425.00,'','2026-05-17'),(36,'V-20260517-1211',6,3,1,0.00,650.00,650.00,'','2026-05-17'),(37,'V-20260518-3503',6,4,1,0.00,850.00,850.00,'','2026-05-17'),(38,'V-20260518-6519',6,4,1,0.00,900.00,900.00,'','2026-05-17'),(39,'V-20260518-6152',6,4,1,0.00,850.00,850.00,'','2026-05-17'),(40,'V-20260518-6499',6,4,1,0.00,3250.00,3250.00,'','2026-05-17'),(41,'V-20260518-9482',6,3,1,0.00,178.00,178.00,'','2026-05-17'),(42,'V-20260518-7223',6,1,1,0.00,1995.00,1995.00,'','2026-05-17'),(43,'V-20260518-6496',6,3,1,0.00,855.00,855.00,'','2026-05-17'),(44,'V-20260518-6160',6,NULL,1,0.00,1000.00,1000.00,'','2026-05-17'),(45,'V-20260518-5978',6,4,1,0.00,1275.00,1275.00,'','2026-05-17'),(46,'V-20260518-7491',6,NULL,1,0.00,325.00,325.00,'','2026-05-17'),(47,'V-20260518-9174',6,NULL,1,0.00,325.00,325.00,'','2026-05-17'),(48,'V-20260518-5085',6,4,1,0.00,255.00,255.00,'','2026-05-17'),(49,'V-20260518-8150',6,3,1,0.00,1300.00,1300.00,'','2026-05-17'),(50,'V-20260518-1918',6,4,1,0.00,1700.00,1700.00,'','2026-05-17'),(51,'V-20260518-7036',6,1,1,0.00,1224.00,1224.00,'','2026-05-17'),(52,'V-20260518-4572',6,4,1,0.00,4250.00,4250.00,'','2026-05-17'),(53,'V-20260518-3010',6,3,1,0.00,3366.00,3366.00,'','2026-05-17'),(54,'V-20260518-0202',6,3,1,0.00,1700.00,1700.00,'','2026-05-17'),(55,'V-20260518-0480',6,NULL,1,0.00,425.00,425.00,'','2026-05-17'),(56,'V-20260518-7934',6,3,1,0.00,1560.00,1560.00,'','2026-05-17'),(57,'V-20260518-1654',6,3,1,0.00,1040.00,1040.00,'','2026-05-17'),(58,'V-20260518-2562',6,NULL,1,0.00,850.00,850.00,'','2026-05-17'),(59,'V-20260518-6894',6,1,1,0.00,1300.00,1300.00,'','2026-05-17'),(60,'V-20260518-7601',6,4,1,10.00,325.00,292.50,'','2026-05-17'),(61,'V-20260518-6241',6,3,1,1.00,115.00,113.85,'','2026-05-17'),(62,'V-20260518-1424',6,NULL,1,12.00,850.00,748.00,'','2026-05-17'),(63,'V-20260518-6889',6,NULL,1,10.00,1300.00,1170.00,'','2026-05-17'),(64,'V-20260518-8161',6,NULL,1,12.00,1500.00,1320.00,'','2026-05-17'),(65,'V-20260518-6800',6,NULL,1,14.00,1300.00,1118.00,'','2026-05-17'),(66,'V-20260518-0149',6,4,1,5.00,425.00,403.75,'','2026-05-17'),(67,'V-20260518-6983',6,4,1,15.00,850.00,722.50,'','2026-05-17'),(68,'V-20260518-7241',6,3,1,12.00,1700.00,1496.00,'','2026-05-17'),(69,'V-20260518-8102',10,4,1,10.00,1500.00,1350.00,'','2026-05-18'),(70,'V-20260518-5984',10,3,1,5.00,3580.00,3401.00,'','2026-05-18'),(71,'V-20260518-8955',10,4,1,10.00,1125.00,1012.50,'','2026-05-18'),(72,'V-20260518-1088',12,3,1,12.00,1125.00,990.00,'','2026-05-18'),(73,'V-20260522-3682',12,3,1,0.00,850.00,850.00,'','2026-05-22'),(74,'V-20260525-9226',12,4,1,5.00,1125.00,1068.75,'','2026-05-25'),(75,'V-20260525-2344',12,4,1,5.00,1125.00,1068.75,'','2026-05-25'),(76,'V-20260820-7704',12,4,1,0.00,89.00,89.00,'','2026-08-20'),(77,'V-20261016-9543',6,4,1,0.00,850.00,850.00,'CANCELADA: adasda','2026-10-16'),(78,'V-20261019-6990',1,NULL,1,0.00,500.00,500.00,NULL,'2026-10-19'),(79,'V-20261019-2168',16,4,1,0.00,850.00,850.00,NULL,'2026-10-19'),(80,'V-20261029-6262',1,6,1,0.00,3320.00,3320.00,NULL,'2026-10-29'),(81,'V-20261029-4509',1,NULL,1,0.00,780.00,780.00,NULL,'2026-10-29'),(82,'V-20261105-9103',1,1,1,0.00,4729.00,4729.00,NULL,'2026-11-05');
/*!40000 ALTER TABLE `ventas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'hlazcano_db'
--

--
-- Dumping routines for database 'hlazcano_db'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-05 22:07:43
