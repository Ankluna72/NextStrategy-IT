<?php
// Script para configurar las tablas necesarias para el resumen ejecutivo
require_once 'includes/db_connection.php';

echo "Configurando tablas para el Resumen Ejecutivo...\n\n";

// 1. Crear tabla resumen_ejecutivo
$sql_resumen = "CREATE TABLE IF NOT EXISTS resumen_ejecutivo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_empresa INT NOT NULL,
    fecha_elaboracion DATE,
    estrategia_identificada TEXT,
    conclusiones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_empresa) REFERENCES empresa(id) ON DELETE CASCADE,
    UNIQUE KEY unique_empresa (id_empresa)
)";

if ($mysqli->query($sql_resumen)) {
    echo "✓ Tabla 'resumen_ejecutivo' creada o ya existe.\n";
} else {
    echo "✗ Error al crear tabla 'resumen_ejecutivo': " . $mysqli->error . "\n";
}

// 2. Agregar columna estrategia_identificada a matriz_came si no existe
$check_column = "SHOW COLUMNS FROM matriz_came LIKE 'estrategia_identificada'";
$result = $mysqli->query($check_column);

if ($result && $result->num_rows == 0) {
    $sql_alter = "ALTER TABLE matriz_came ADD COLUMN estrategia_identificada TEXT AFTER acciones_e";
    if ($mysqli->query($sql_alter)) {
        echo "✓ Columna 'estrategia_identificada' agregada a 'matriz_came'.\n";
    } else {
        echo "✗ Error al agregar columna: " . $mysqli->error . "\n";
    }
} else {
    echo "✓ Columna 'estrategia_identificada' ya existe en 'matriz_came'.\n";
}

// 3. Verificar que la tabla usuario tenga la columna nombre
$check_usuarios = "SHOW COLUMNS FROM usuario LIKE 'nombre'";
$result_usuarios = $mysqli->query($check_usuarios);

if ($result_usuarios && $result_usuarios->num_rows == 0) {
    // Si no existe, agregarla
    $sql_usuarios = "ALTER TABLE usuario ADD COLUMN nombre VARCHAR(255) AFTER id";
    if ($mysqli->query($sql_usuarios)) {
        echo "✓ Columna 'nombre' agregada a 'usuario'.\n";
    } else {
        echo "✗ Error al agregar columna a usuario: " . $mysqli->error . "\n";
    }
} else {
    echo "✓ Columna 'nombre' ya existe en 'usuario'.\n";
}

echo "\n¡Configuración completada!\n";
echo "Puedes acceder al resumen ejecutivo desde: resumen_plan.php\n";

$mysqli->close();
?>
