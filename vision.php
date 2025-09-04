<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit();
}
if (!isset($_SESSION['id_empresa_actual'])) {
    header('Location: dashboard.php'); // Si no hay empresa, al dashboard para seleccionarla
    exit();
}

require_once 'includes/db_connection.php';
require_once 'includes/header.php'; // Incluye Bootstrap CSS

$id_empresa_actual = $_SESSION['id_empresa_actual'];
$mensaje = '';
$vision_actual = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['vision'])) {
        $nueva_vision = $_POST['vision'];
        $stmt = $mysqli->prepare("UPDATE empresa SET vision = ? WHERE id = ?");
        $stmt->bind_param("si", $nueva_vision, $id_empresa_actual);
        if ($stmt->execute()) {
            $mensaje = '<div class="alert alert-success">Visión guardada correctamente.</div>';
        } else {
            $mensaje = '<div class="alert alert-danger">Error al guardar la visión.</div>';
        }
        $stmt->close();
    }
}

$stmt_select = $mysqli->prepare("SELECT vision FROM empresa WHERE id = ?");
$stmt_select->bind_param("i", $id_empresa_actual);
$stmt_select->execute();
$stmt_select->bind_result($vision_actual_db);
$stmt_select->fetch();
$vision_actual = $vision_actual_db ?? ''; 
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
            <h2 class="module-title">2. VISIÓN</h2>
            <div class="explanation-box">
                <p><strong>La VISIÓN de una empresa define lo que la empresa/organización quiere lograr en el futuro. Es lo que la organización aspira llegar a ser en torno a 2-3 años.</strong></p>
                <ul>
                    <li>Debe ser retadora, positiva, compartida y coherente con la misión.</li>
                    <li>Marca el fin último que la estrategia debe seguir.</li>
                    <li>Proyecta la imagen de destino que se pretende alcanzar.</li>
                </ul>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">EJEMPLOS</div>
                <div class="card-body">
                    <p><strong>Empresa de servicios:</strong><br><small>Ser el gurpo empresarial de referencia en nuestras áreas de actividad.</small></p>
                    <hr>
                    <p><strong>Empresa productora de café:</strong><br><small>Queremos ser en el mundo el punto de referencia de la cultura y de la excelencia del café. Una empresa innovadora que porpone los mejores productos y lugares de consumo y que, gracias a ello, crece y se convierte en líder de la alta gama.</small></p>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card">
                <div class="card-header">
                    En este apartado describa la Visión de su empresa.
                </div>
                <div class="card-body">
                    <?php echo $mensaje; ?>
                    <form action="vision.php" method="POST">
                        <div class="mb-3">
                            <textarea class="form-control" name="vision" rows="10" placeholder="Escribe aquí la visión..."><?php echo htmlspecialchars($vision_actual); ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Guardar Visión</button>
                    </form>
                </div>
            </div>
            
            <div class="d-flex justify-content-between mt-4">
                <a href="mision.php" class="btn btn-secondary">&laquo; Anterior: Misión</a>
                <a href="dashboard.php" class="btn btn-outline-light">Volver al Índice</a>
                <a href="valores.php" class="btn btn-primary">Siguiente: Valores &raquo;</a>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
$mysqli->close();
?>