-- Crear tabla para almacenar información del resumen ejecutivo
CREATE TABLE IF NOT EXISTS resumen_ejecutivo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_empresa INT NOT NULL,
    fecha_elaboracion DATE DEFAULT CURRENT_DATE,
    estrategia_identificada TEXT,
    conclusiones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_empresa) REFERENCES empresa(id) ON DELETE CASCADE,
    UNIQUE KEY unique_empresa (id_empresa)
);

-- Agregar columna para estrategia identificada en la tabla matriz_came si no existe
ALTER TABLE matriz_came 
ADD COLUMN IF NOT EXISTS estrategia_identificada TEXT AFTER acciones_e;
