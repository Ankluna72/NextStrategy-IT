-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Versión del servidor:         10.4.27-MariaDB - mariadb.org binary distribution
-- SO del servidor:              Win64
-- HeidiSQL Versión:             12.4.0.6659
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Volcando estructura de base de datos para planti
CREATE DATABASE IF NOT EXISTS `planti` /*!40100 DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci */;
USE `planti`;

-- Volcando estructura para tabla planti.empresa
CREATE TABLE IF NOT EXISTS `empresa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `nombre_empresa` varchar(255) DEFAULT NULL,
  `mision` text DEFAULT NULL,
  `vision` text DEFAULT NULL,
  `valores` text DEFAULT NULL,
  `objetivos` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `empresa_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla planti.empresa: ~1 rows (aproximadamente)
DELETE FROM `empresa`;
INSERT INTO `empresa` (`id`, `id_usuario`, `nombre_empresa`, `mision`, `vision`, `valores`, `objetivos`) VALUES
	(1, 1, 'Tech Solutions', 'Ofrecer soluciones tecnológicas innovadoras para optimizar la gestión empresarial.', 'Ser líderes en el mercado de desarrollo de software en América Latina para 2030.', 'Innovación, Integridad, Orientación al cliente.', 'Incrementar la cuota de mercado en un 15% el próximo año y expandir operaciones a 3 nuevos países.');

-- Volcando estructura para tabla planti.empresa_detalle
CREATE TABLE IF NOT EXISTS `empresa_detalle` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_empresa` int(11) NOT NULL,
  `tipo_analisis` varchar(100) NOT NULL,
  `contenido` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`contenido`)),
  PRIMARY KEY (`id`),
  KEY `id_empresa` (`id_empresa`),
  CONSTRAINT `empresa_detalle_ibfk_1` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla planti.empresa_detalle: ~6 rows (aproximadamente)
DELETE FROM `empresa_detalle`;
INSERT INTO `empresa_detalle` (`id`, `id_empresa`, `tipo_analisis`, `contenido`) VALUES
	(1, 1, 'Analisis FODA', '{\r\n  "fortalezas": [\r\n    "Equipo de desarrollo altamente calificado.",\r\n    "Amplia cartera de clientes leales.",\r\n    "Rápida adaptación a nuevas tecnologías."\r\n  ],\r\n  "oportunidades": [\r\n    "Crecimiento del mercado de soluciones SaaS.",\r\n    "Necesidad de digitalización en pequeñas empresas.",\r\n    "Subsidios gubernamentales para tecnología."\r\n  ],\r\n  "debilidades": [\r\n    "Falta de presencia en mercados internacionales.",\r\n    "Dependencia de pocos clientes grandes.",\r\n    "Costo elevado de adquisición de nuevos clientes."\r\n  ],\r\n  "amenazas": [\r\n    "Competencia de grandes empresas del sector.",\r\n    "Cambios rápidos en las regulaciones de datos.",\r\n    "Inestabilidad económica regional."\r\n  ]\r\n}'),
	(2, 1, 'Cadena de Valor', '{\r\n  "actividades_primarias": {\r\n    "logistica_entrada": "Procesos de adquisición de licencias de software y hardware.",\r\n    "operaciones": "Desarrollo y pruebas de software ágiles.",\r\n    "logistica_salida": "Implementación y despliegue de soluciones en la nube.",\r\n    "marketing_ventas": "Marketing digital y estrategias de venta consultiva.",\r\n    "servicios": "Soporte técnico 24/7 y formación continua."\r\n  },\r\n  "actividades_apoyo": {\r\n    "infraestructura": "Servidores y data centers seguros y de alto rendimiento.",\r\n    "recursos_humanos": "Programas de retención de talento y formación especializada.",\r\n    "desarrollo_tecnologico": "Investigación en Inteligencia Artificial y Machine Learning.",\r\n    "abastecimiento": "Negociación de contratos con proveedores de nube."\r\n  }\r\n}'),
	(3, 1, 'Matriz BCG', '{\r\n  "productos": [\r\n    {\r\n      "nombre": "Software de Contabilidad",\r\n      "clasificacion": "Vaca",\r\n      "descripcion": "Bajo crecimiento del mercado, pero alta cuota de mercado."\r\n    },\r\n    {\r\n      "nombre": "Plataforma de IA",\r\n      "clasificacion": "Estrella",\r\n      "descripcion": "Alto crecimiento y alta cuota de mercado."\r\n    },\r\n    {\r\n      "nombre": "App de Gestión de Proyectos",\r\n      "clasificacion": "Interrogante",\r\n      "descripcion": "Alto crecimiento, pero baja cuota de mercado."\r\n    }\r\n  ]\r\n}'),
	(4, 1, 'Analisis PEST', '{\r\n  "politicos": [\r\n    "Nuevas políticas de protección de datos.",\r\n    "Incentivos fiscales para empresas tecnológicas."\r\n  ],\r\n  "economicos": [\r\n    "Inflación que afecta los costos operativos.",\r\n    "Crecimiento del PIB regional."\r\n  ],\r\n  "sociales": [\r\n    "Aumento del teletrabajo y la demanda de herramientas digitales.",\r\n    "Mayor conciencia sobre la ciberseguridad."\r\n  ],\r\n  "tecnologicos": [\r\n    "Avances en la computación cuántica.",\r\n    "Democratización de la inteligencia artificial."\r\n  ]\r\n}'),
	(5, 1, 'Analisis PEST', '{\r\n  "politicos": [\r\n    "Nuevas políticas de protección de datos.",\r\n    "Incentivos fiscales para empresas tecnológicas."\r\n  ],\r\n  "economicos": [\r\n    "Inflación que afecta los costos operativos.",\r\n    "Crecimiento del PIB regional."\r\n  ],\r\n  "sociales": [\r\n    "Aumento del teletrabajo y la demanda de herramientas digitales.",\r\n    "Mayor conciencia sobre la ciberseguridad."\r\n  ],\r\n  "tecnologicos": [\r\n    "Avances en la computación cuántica.",\r\n    "Democratización de la inteligencia artificial."\r\n  ]\r\n}'),
	(6, 1, 'Matriz CAME', '{\r\n  "corregir_debilidades": [\r\n    "Desarrollar una estrategia de marketing de entrada a nuevos mercados.",\r\n    "Implementar un CRM para fortalecer la relación con clientes existentes."\r\n  ],\r\n  "afrontar_amenazas": [\r\n    "Invertir en I+D para mantener la competitividad.",\r\n    "Contratar a un experto en regulaciones de datos."\r\n  ],\r\n  "mantener_fortalezas": [\r\n    "Continuar con la formación del equipo de desarrollo.",\r\n    "Promocionar la marca como experta en innovación."\r\n  ],\r\n  "explotar_oportunidades": [\r\n    "Crear un nuevo producto para el mercado de pequeñas empresas.",\r\n    "Participar en programas de subsidios gubernamentales."\r\n  ]\r\n}');

-- Volcando estructura para tabla planti.usuario
CREATE TABLE IF NOT EXISTS `usuario` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo_user` int(11) DEFAULT NULL,
  `nombre` varchar(50) DEFAULT NULL,
  `apellido` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` text DEFAULT NULL,
  `pais` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_unico` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcando datos para la tabla planti.usuario: ~1 rows (aproximadamente)
DELETE FROM `usuario`;
INSERT INTO `usuario` (`id`, `tipo_user`, `nombre`, `apellido`, `email`, `password`, `pais`) VALUES
	(1, 1, 'Jefferson', 'Rosas', 'jefferson.rosas@ejemplo.com', 'hashed_password_123', 'Perú');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
