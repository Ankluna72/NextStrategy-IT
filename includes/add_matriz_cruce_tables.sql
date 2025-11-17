-- Tabla para almacenar valores de matrices cruzadas (FO, FA, DO, DA)
CREATE TABLE IF NOT EXISTS `matriz_cruce_valores` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `id_empresa` INT(11) NOT NULL,
  `id_usuario` INT(11) NOT NULL,
  `tipo` ENUM('FO','FA','DO','DA') NOT NULL,
  `fila` TINYINT(1) NOT NULL,
  `columna` TINYINT(1) NOT NULL,
  `valor` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_empresa_usuario_tipo` (`id_empresa`, `id_usuario`, `tipo`),
  CONSTRAINT `fk_mcv_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresa`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_mcv_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY `uniq_matriz_cell` (`id_empresa`, `id_usuario`, `tipo`, `fila`, `columna`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;