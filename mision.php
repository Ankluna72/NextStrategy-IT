<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit();
}
if (!isset($_SESSION['id_empresa_actual'])) {
    header('Location: dashboard.php');
    exit();
}

require_once 'includes/db_connection.php';
require_once 'includes/header.php'; // Incluye Bootstrap CSS

$id_empresa_actual = $_SESSION['id_empresa_actual'];
$mensaje = '';
$mision_actual = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mision'])) {
        $nueva_mision = $_POST['mision'];
        $stmt = $mysqli->prepare("UPDATE empresa SET mision = ? WHERE id = ?");
        $stmt->bind_param("si", $nueva_mision, $id_empresa_actual);
        if ($stmt->execute()) {
            $mensaje = '<div class="alert alert-success">Misión guardada correctamente.</div>';
        } else {
            $mensaje = '<div class="alert alert-danger">Error al guardar la misión.</div>';
        }
        $stmt->close();
    }
}

$stmt_select = $mysqli->prepare("SELECT mision FROM empresa WHERE id = ?");
$stmt_select->bind_param("i", $id_empresa_actual);
$stmt_select->execute();
$stmt_select->bind_result($mision_actual_db);
$stmt_select->fetch();
$mision_actual = $mision_actual_db ?? ''; // Asigna el valor o un string vacío si es null
$stmt_select->close();

?>
<style>
    body { background-color: #1a1a2e; color: #e0e0e0; }
    .card { background-color: #16213e; color: white; border: 1px solid #0f3460; }
    .card-header, .module-title { color: #a4d6ffff; font-weight: bold; border-color: #0f3460; }
    .btn-primary { background-color: #e94560; border-color: #e94560; }
    .btn-secondary { background-color: #2a3a5e; border-color: #2a3a5e; }
    .form-control { background-color: #2a3a5e; color: white; border-color: #0f3460; }
    .form-control:focus { background-color: #2a3a5e; color: white; border-color: #e94560; box-shadow: 0 0 0 0.25rem rgba(233, 69, 96, 0.5); }
    .explanation-box { background-color: rgba(15, 52, 96, 0.3); border-left: 4px solid #a4d6ffff; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
</style>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-5">
            <h2 class="module-title">1. MISIÓN</h2>
            <div class="explanation-box">
                <p><strong>La MISIÓN es la razón de ser de la empresa u organización.</strong></p>
                <ul>
                    <li>Debe ser clara, concisa y compartida.</li>
                    <li>Siempre orientada hacia el cliente no hacia el producto o servicio.</li>
                    <li>Refleja el propósito fundamental de la empresa en el mercado.</li>
                </ul>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">EJEMPLOS</div>
                <div class="card-body">
                    <p><strong>Empresa de servicios:</strong><br><small>La gestión de servicios que contribuyen a la calidad de vida de las personas y generan valor para los grupos de interés.</small></p>
                    <hr>
                    <p><strong>Empresa productora de café:</strong><br><small>Gracias a nuestro entusiasmo, trabajo en equipo y valores, queremos deleitar a todos aquellos que, en el mundo, aman la calidad de vida, a través del mejor café que la naturaleza pueda ofrecer...</small></p>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card">
                <div class="card-header">
                    En este apartado describa la Misión de su empresa.
                </div>
                <div class="card-body">
                    <?php echo $mensaje; ?>
                    <form action="mision.php" method="POST">
                        <div class="mb-3">
                            <textarea class="form-control" name="mision" rows="10" placeholder="Escribe aquí la misión..."><?php echo htmlspecialchars($mision_actual); ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Guardar Misión</button>
                    </form>
                </div>
            </div>
            
            <div class="d-flex justify-content-between mt-4">
                <a href="dashboard.php" class="btn btn-secondary">&laquo; Volver al Índice</a>
                <a href="vision.php" class="btn btn-primary">Siguiente: Visión &raquo;</a>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
$mysqli->close();
?>