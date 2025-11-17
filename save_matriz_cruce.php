<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['id_empresa_actual'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autenticado']);
    exit();
}

require_once __DIR__ . '/includes/db_connection.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['tipo']) || !isset($data['values']) || !is_array($data['values'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Datos inválidos']);
    exit();
}

$tipo = strtoupper(trim($data['tipo']));
$permitidos = ['FO','FA','DO','DA'];
if (!in_array($tipo, $permitidos, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Tipo no permitido']);
    exit();
}

$id_empresa = (int)$_SESSION['id_empresa_actual'];
$id_usuario = (int)$_SESSION['id_usuario'];

$mysqli->begin_transaction();
try {
    // Eliminar los valores previos de esta matriz (empresa+usuario+tipo)
    $del = $mysqli->prepare("DELETE FROM matriz_cruce_valores WHERE id_empresa=? AND id_usuario=? AND tipo=?");
    $del->bind_param('iis', $id_empresa, $id_usuario, $tipo);
    $del->execute();
    $del->close();

    // Insertar los 16 valores
    $ins = $mysqli->prepare("INSERT INTO matriz_cruce_valores (id_empresa, id_usuario, tipo, fila, columna, valor) VALUES (?,?,?,?,?,?)");

    foreach ($data['values'] as $cell) {
        $fila = isset($cell['row']) ? (int)$cell['row'] : 0;
        $col = isset($cell['col']) ? (int)$cell['col'] : 0;
        $val = isset($cell['val']) ? (int)$cell['val'] : 0;
        if ($fila < 1 || $fila > 4 || $col < 1 || $col > 4 || $val < 0 || $val > 4) {
            throw new Exception('Valor fuera de rango');
        }
        $ins->bind_param('iisiii', $id_empresa, $id_usuario, $tipo, $fila, $col, $val);
        $ins->execute();
    }

    $ins->close();
    $mysqli->commit();

    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    $mysqli->rollback();
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error al guardar', 'detalle' => $e->getMessage()]);
}
