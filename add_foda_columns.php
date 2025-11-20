<?php
require_once 'includes/db_connection.php';

// Agregar columnas FODA
$mysqli->query("ALTER TABLE resumen_ejecutivo ADD COLUMN foda_debilidades TEXT");
$mysqli->query("ALTER TABLE resumen_ejecutivo ADD COLUMN foda_amenazas TEXT");
$mysqli->query("ALTER TABLE resumen_ejecutivo ADD COLUMN foda_fortalezas TEXT");
$mysqli->query("ALTER TABLE resumen_ejecutivo ADD COLUMN foda_oportunidades TEXT");

echo "Columnas FODA agregadas correctamente";
$mysqli->close();
?>
