CREATE DATABASE proyectoti

USE proyectoti


CREATE TABLE `usuario` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `tipo_user` INT(11) DEFAULT NULL,
  `nombre` VARCHAR(50) DEFAULT NULL,
  `apellido` VARCHAR(50) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `password` TEXT DEFAULT NULL,
  `pais` VARCHAR(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_unico` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Estructura de la tabla `empresa`
--
CREATE TABLE `empresa` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` INT(11) NOT NULL,
  `nombre_empresa` VARCHAR(255) DEFAULT NULL,
  `mision` TEXT DEFAULT NULL,
  `vision` TEXT DEFAULT NULL,
  `valores` TEXT DEFAULT NULL,
  `objetivos` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_usuario`) REFERENCES `usuario`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Estructura de la tabla `empresa_detalle`
--
CREATE TABLE `empresa_detalle` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `id_empresa` INT(11) NOT NULL,
  `tipo_analisis` VARCHAR(100) NOT NULL,
  `contenido` JSON DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_empresa`) REFERENCES `empresa`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Inserciones de datos de ejemplo
-- --------------------------------------------------------

--
-- Inserta un usuario: Jefferson Rosas
--
INSERT INTO `usuario` (`id`, `tipo_user`, `nombre`, `apellido`, `email`, `password`, `pais`) VALUES
(1, 1, 'Jefferson', 'Rosas', 'jefferson.rosas@ejemplo.com', 'hashed_password_123', 'Perú');

--
-- Inserta la información general de la empresa de Jefferson
--
INSERT INTO `empresa` (`id_usuario`, `nombre_empresa`, `mision`, `vision`, `valores`, `objetivos`) VALUES
(1, 'Tech Solutions', 'Ofrecer soluciones tecnológicas innovadoras para optimizar la gestión empresarial.', 'Ser líderes en el mercado de desarrollo de software en América Latina para 2030.', 'Innovación, Integridad, Orientación al cliente.', 'Incrementar la cuota de mercado en un 15% el próximo año y expandir operaciones a 3 nuevos países.');

--
-- Inserta el análisis FODA de la empresa
--
INSERT INTO `empresa_detalle` (`id_empresa`, `tipo_analisis`, `contenido`) VALUES
(1, 'Analisis FODA', '{
  "fortalezas": [
    "Equipo de desarrollo altamente calificado.",
    "Amplia cartera de clientes leales.",
    "Rápida adaptación a nuevas tecnologías."
  ],
  "oportunidades": [
    "Crecimiento del mercado de soluciones SaaS.",
    "Necesidad de digitalización en pequeñas empresas.",
    "Subsidios gubernamentales para tecnología."
  ],
  "debilidades": [
    "Falta de presencia en mercados internacionales.",
    "Dependencia de pocos clientes grandes.",
    "Costo elevado de adquisición de nuevos clientes."
  ],
  "amenazas": [
    "Competencia de grandes empresas del sector.",
    "Cambios rápidos en las regulaciones de datos.",
    "Inestabilidad económica regional."
  ]
}');

--
-- Inserta la cadena de valor de la empresa
--
INSERT INTO `empresa_detalle` (`id_empresa`, `tipo_analisis`, `contenido`) VALUES
(1, 'Cadena de Valor', '{
  "actividades_primarias": {
    "logistica_entrada": "Procesos de adquisición de licencias de software y hardware.",
    "operaciones": "Desarrollo y pruebas de software ágiles.",
    "logistica_salida": "Implementación y despliegue de soluciones en la nube.",
    "marketing_ventas": "Marketing digital y estrategias de venta consultiva.",
    "servicios": "Soporte técnico 24/7 y formación continua."
  },
  "actividades_apoyo": {
    "infraestructura": "Servidores y data centers seguros y de alto rendimiento.",
    "recursos_humanos": "Programas de retención de talento y formación especializada.",
    "desarrollo_tecnologico": "Investigación en Inteligencia Artificial y Machine Learning.",
    "abastecimiento": "Negociación de contratos con proveedores de nube."
  }
}');

--
-- Inserta la matriz BCG de la empresa
--
INSERT INTO `empresa_detalle` (`id_empresa`, `tipo_analisis`, `contenido`) VALUES
(1, 'Matriz BCG', '{
  "productos": [
    {
      "nombre": "Software de Contabilidad",
      "clasificacion": "Vaca",
      "descripcion": "Bajo crecimiento del mercado, pero alta cuota de mercado."
    },
    {
      "nombre": "Plataforma de IA",
      "clasificacion": "Estrella",
      "descripcion": "Alto crecimiento y alta cuota de mercado."
    },
    {
      "nombre": "App de Gestión de Proyectos",
      "clasificacion": "Interrogante",
      "descripcion": "Alto crecimiento, pero baja cuota de mercado."
    }
  ]
}');

--
-- Inserta el análisis PEST de la empresa
--
INSERT INTO `empresa_detalle` (`id_empresa`, `tipo_analisis`, `contenido`) VALUES
(1, 'Analisis PEST', '{
  "politicos": [
    "Nuevas políticas de protección de datos.",
    "Incentivos fiscales para empresas tecnológicas."
  ],
  "economicos": [
    "Inflación que afecta los costos operativos.",
    "Crecimiento del PIB regional."
  ],
  "sociales": [
    "Aumento del teletrabajo y la demanda de herramientas digitales.",
    "Mayor conciencia sobre la ciberseguridad."
  ],
  "tecnologicos": [
    "Avances en la computación cuántica.",
    "Democratización de la inteligencia artificial."
  ]
}');

--
-- Inserta la matriz CAME de la empresa
--
INSERT INTO `empresa_detalle` (`id_empresa`, `tipo_analisis`, `contenido`) VALUES
(1, 'Matriz CAME', '{
  "corregir_debilidades": [
    "Desarrollar una estrategia de marketing de entrada a nuevos mercados.",
    "Implementar un CRM para fortalecer la relación con clientes existentes."
  ],
  "afrontar_amenazas": [
    "Invertir en I+D para mantener la competitividad.",
    "Contratar a un experto en regulaciones de datos."
  ],
  "mantener_fortalezas": [
    "Continuar con la formación del equipo de desarrollo.",
    "Promocionar la marca como experta en innovación."
  ],
  "explotar_oportunidades": [
    "Crear un nuevo producto para el mercado de pequeñas empresas.",
    "Participar en programas de subsidios gubernamentales."
  ]
}');