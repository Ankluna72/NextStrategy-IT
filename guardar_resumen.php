<?php
session_start();
require_once 'includes/db_connection.php';

if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['id_empresa_actual'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$id_empresa = $_SESSION['id_empresa_actual'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo'] ?? '';
    $contenido = $_POST['contenido'] ?? '';
    
    if ($tipo === 'estrategia') {
        // Guardar estrategia identificada
        $stmt = $mysqli->prepare("INSERT INTO resumen_ejecutivo (id_empresa, estrategia_identificada) 
                                  VALUES (?, ?) 
                                  ON DUPLICATE KEY UPDATE estrategia_identificada=VALUES(estrategia_identificada)");
        $stmt->bind_param("is", $id_empresa, $contenido);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Estrategia guardada correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al guardar estrategia']);
        }
        $stmt->close();
        
    } elseif ($tipo === 'conclusiones') {
        // Guardar conclusiones
        $stmt = $mysqli->prepare("INSERT INTO resumen_ejecutivo (id_empresa, conclusiones) 
                                  VALUES (?, ?) 
                                  ON DUPLICATE KEY UPDATE conclusiones=VALUES(conclusiones)");
        $stmt->bind_param("is", $id_empresa, $contenido);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Conclusiones guardadas correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al guardar conclusiones']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Tipo no válido']);
    }
}

$mysqli->close();
?>
