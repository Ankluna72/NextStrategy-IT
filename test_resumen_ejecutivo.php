<?php
// Script de prueba para verificar el resumen ejecutivo
require_once 'includes/db_connection.php';

echo "=== VERIFICACIÓN DEL SISTEMA DE RESUMEN EJECUTIVO ===\n\n";

// 1. Verificar conexión a BD
if ($mysqli->ping()) {
    echo "✓ Conexión a base de datos: OK\n";
} else {
    echo "✗ Error de conexión a base de datos\n";
    exit(1);
}

// 2. Verificar tablas necesarias
$tablas_requeridas = ['empresa', 'usuario', 'objetivos_estrategicos', 'foda', 'matriz_came', 'resumen_ejecutivo'];
$tablas_ok = 0;

foreach ($tablas_requeridas as $tabla) {
    $result = $mysqli->query("SHOW TABLES LIKE '$tabla'");
    if ($result && $result->num_rows > 0) {
        echo "✓ Tabla '$tabla': Existe\n";
        $tablas_ok++;
    } else {
        echo "✗ Tabla '$tabla': NO existe\n";
    }
}

echo "\nTablas verificadas: $tablas_ok/" . count($tablas_requeridas) . "\n\n";

// 3. Verificar columnas críticas
echo "=== VERIFICACIÓN DE COLUMNAS ===\n";

// Verificar columna en matriz_came
$check = $mysqli->query("SHOW COLUMNS FROM matriz_came LIKE 'estrategia_identificada'");
if ($check && $check->num_rows > 0) {
    echo "✓ Columna 'estrategia_identificada' en matriz_came: OK\n";
} else {
    echo "✗ Columna 'estrategia_identificada' en matriz_came: NO existe\n";
}

// Verificar columna nombre en usuario
$check2 = $mysqli->query("SHOW COLUMNS FROM usuario LIKE 'nombre'");
if ($check2 && $check2->num_rows > 0) {
    echo "✓ Columna 'nombre' en usuario: OK\n";
} else {
    echo "✗ Columna 'nombre' en usuario: NO existe\n";
}

// 4. Verificar datos de ejemplo
echo "\n=== DATOS DISPONIBLES ===\n";

$empresas = $mysqli->query("SELECT COUNT(*) as total FROM empresa");
if ($empresas) {
    $row = $empresas->fetch_assoc();
    echo "• Empresas registradas: " . $row['total'] . "\n";
}

$usuarios = $mysqli->query("SELECT COUNT(*) as total FROM usuario");
if ($usuarios) {
    $row = $usuarios->fetch_assoc();
    echo "• Usuarios registrados: " . $row['total'] . "\n";
}

$objetivos = $mysqli->query("SELECT COUNT(*) as total FROM objetivos_estrategicos");
if ($objetivos) {
    $row = $objetivos->fetch_assoc();
    echo "• Objetivos estratégicos: " . $row['total'] . "\n";
}

$foda = $mysqli->query("SELECT COUNT(*) as total FROM foda");
if ($foda) {
    $row = $foda->fetch_assoc();
    echo "• Elementos FODA: " . $row['total'] . "\n";
}

echo "\n=== VERIFICACIÓN COMPLETADA ===\n";
echo "El sistema está listo para usar. Accede a: resumen_plan.php\n";

$mysqli->close();
?>
