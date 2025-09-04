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
require_once 'includes/header.php';

$id_empresa_actual = $_SESSION['id_empresa_actual'];
$mensaje = '';
$valores_actuales = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['valores'])) {
        $nuevos_valores = $_POST['valores'];
        $stmt = $mysqli->prepare("UPDATE empresa SET valores = ? WHERE id = ?");
        $stmt->bind_param("si", $nuevos_valores, $id_empresa_actual);
        if ($stmt->execute()) {
            $mensaje = '<div class="alert alert-success">Valores guardados correctamente.</div>';
        } else {
            $mensaje = '<div class="alert alert-danger">Error al guardar los valores.</div>';
        }
        $stmt->close();
    }
}

$stmt_select = $mysqli->prepare("SELECT valores FROM empresa WHERE id = ?");
$stmt_select->bind_param("i", $id_empresa_actual);
$stmt_select->execute();
$stmt_select->bind_result($valores_actuales_db);
$stmt_select->fetch();
$valores_actuales = $valores_actuales_db ?? '';
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
            <h2 class="module-title">3. VALORES</h2>
            <div class="explanation-box">
                <p><strong>Los VALORES de una empresa son el conjunto de principios, reglas y aspectos culturales con los que se rige la organización. Son las pautas de comportamiento de la empresa y generalmente son pocos, entre 3 y 6.</strong></p>
                <ul>
                    <li>Integridad</li>
                    <li>Compromiso con el desarrollo humano</li>
                    <li>Ética profesional</li>
                    <li>Responsabilidad social</li>
                    <li>Innovación</li>
                </ul>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">EJEMPLOS</div>
                <div class="card-body">
                    <p><strong>Empresa de servicios:</strong><br><small>La excelencia en la prestación de servicios. La innovación orientada a la mejora continua de procesos productos y servicios.</small></p>
                    <hr>
                    <p><strong>Agencia de certificación:</strong><br><small>Integridad y ética. Consejo y validación imparciales. Respeto por todas las personas.</small></p>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card">
                <div class="card-header">
                    En este apartado exponga los Valores de su empresa.
                </div>
                <div class="card-body">
                    <?php echo $mensaje; ?>
                    <form action="valores.php" method="POST">
                        <div class="mb-3">
                            <textarea class="form-control" name="valores" rows="10" placeholder="Escribe aquí los valores, uno por línea..."><?php echo htmlspecialchars($valores_actuales); ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Guardar Valores</button>
                    </form>
                </div>
            </div>
            
            <div class="d-flex justify-content-between mt-4">
                <a href="vision.php" class="btn btn-secondary">&laquo; Anterior: Visión</a>
                <a href="dashboard.php" class="btn btn-outline-light">Volver al Índice</a>
                <a href="objetivos.php" class="btn btn-primary">Siguiente: Objetivos &raquo;</a>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
$mysqli->close();
?>