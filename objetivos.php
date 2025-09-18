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

$pageStyles = ['css/modules.css'];
require_once 'includes/db_connection.php';
require_once 'includes/header.php';

$id_empresa_actual = $_SESSION['id_empresa_actual'];
$mensaje = '';
$datos_guardados = [
    'uens' => '',
    'objetivos_especificos' => []
];

$stmt_empresa = $mysqli->prepare("SELECT mision, vision, valores FROM empresa WHERE id = ?");
$stmt_empresa->bind_param("i", $id_empresa_actual);
$stmt_empresa->execute();
$resultado_empresa = $stmt_empresa->get_result();
$empresa_data = $resultado_empresa->fetch_assoc();
$stmt_empresa->close();

$valores_lista = !empty($empresa_data['valores']) ? explode("\n", trim($empresa_data['valores'])) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uens = $_POST['uens'] ?? '';
    $objetivos_especificos_post = $_POST['objetivos_especificos'] ?? [];
    
    $contenido_a_guardar = [
        'uens' => $uens,
        'objetivos_especificos' => $objetivos_especificos_post
    ];

    $json_contenido = json_encode($contenido_a_guardar);
    $tipo_analisis = 'Objetivos Estrategicos';

    $stmt_check = $mysqli->prepare("SELECT id FROM empresa_detalle WHERE id_empresa = ? AND tipo_analisis = ?");
    $stmt_check->bind_param("is", $id_empresa_actual, $tipo_analisis);
    $stmt_check->execute();
    $resultado_check = $stmt_check->get_result();
    $stmt_check->close();

    if ($resultado_check->num_rows > 0) {
        $stmt_update = $mysqli->prepare("UPDATE empresa_detalle SET contenido = ? WHERE id_empresa = ? AND tipo_analisis = ?");
        $stmt_update->bind_param("sis", $json_contenido, $id_empresa_actual, $tipo_analisis);
        $exito = $stmt_update->execute();
        $stmt_update->close();
    } else {
        $stmt_insert = $mysqli->prepare("INSERT INTO empresa_detalle (id_empresa, tipo_analisis, contenido) VALUES (?, ?, ?)");
        $stmt_insert->bind_param("iss", $id_empresa_actual, $tipo_analisis, $json_contenido);
        $exito = $stmt_insert->execute();
        $stmt_insert->close();
    }

    if ($exito) {
        $mensaje = '<div class="alert alert-success">Objetivos Estratégicos guardados correctamente.</div>';
    } else {
        $mensaje = '<div class="alert alert-danger">Error al guardar los datos.</div>';
    }
}

$stmt_select = $mysqli->prepare("SELECT contenido FROM empresa_detalle WHERE id_empresa = ? AND tipo_analisis = 'Objetivos Estrategicos'");
$stmt_select->bind_param("i", $id_empresa_actual);
$stmt_select->execute();
$resultado_select = $stmt_select->get_result();
if ($fila = $resultado_select->fetch_assoc()) {
    $datos_guardados = json_decode($fila['contenido'], true);
}
$stmt_select->close();

?>
<div class="container mt-4">
    <div class="module-container">
        <div class="module-header">
            <h2 class="module-title">4. OBJETIVOS ESTRATÉGICOS</h2>
        </div>
        <div class="module-content">
            <div class="row">
                <div class="col-lg-5">
                    <div class="explanation-box">
                        <p>Un <strong>OBJETIVO ESTRATÉGICO</strong> es un fin deseado, clave para la organización y para la consecución de su visión. Para una correcta planificación construya los objetivos formando una pirámide.</p>
                    </div>

                    <div class="examples-card">
                        <div class="examples-header">Unidades Estratégicas de Negocio (UEN)</div>
                        <div class="examples-body">
                            <p><small>En empresas de gran tamaño, se pueden formular los objetivos en función de sus diferentes UEN. Se entiende por UEN un conjunto homogéneo de actividades o negocios, desde el punto de vista estratégico, para el cual es posible formular una estrategia común.</small></p>
                        </div>
                    </div>
                    
                    <div class="examples-card">
                        <div class="examples-header">EJEMPLOS</div>
                        <div class="examples-body">
                            <p><small>• Alcanzar los niveles de ventas previstos para los nuevos productos.</small></p>
                            <p><small>• Reducir la rotación del personal del almacén.</small></p>
                            <p><small>• Reducir el plazo de cobro de los clientes.</small></p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <form action="objetivos.php" method="POST">
                        <?php echo $mensaje; ?>
                        <div class="form-card mb-4">
                            <div class="form-header">En su caso, comente en este apartado las distintas UEN que tiene su empresa</div>
                            <div class="form-body">
                                <div class="mb-3">
                                    <label for="uens" class="form-label">Unidades Estratégicas de Negocio</label>
                                    <textarea class="form-control" name="uens" id="uens" rows="5" placeholder="Describe las UEN de tu empresa..."><?php echo htmlspecialchars($datos_guardados['uens']); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="form-card">
                            <div class="form-header">A continuación reflexione sobre la misión, visión y valores definidos y establezca los objetivos</div>
                            <div class="form-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-module">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th>OBJETIVOS GENERALES O ESTRATÉGICOS</th>
                                                <th>OBJETIVOS ESPECÍFICOS</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <th>MISIÓN</th>
                                                <td class="table-info-cell"><?php echo htmlspecialchars($empresa_data['mision']); ?></td>
                                                <td><input type="text" name="objetivos_especificos[mision]" class="form-control" value="<?php echo htmlspecialchars($datos_guardados['objetivos_especificos']['mision'] ?? ''); ?>" placeholder="Objetivo específico..."></td>
                                            </tr>
                                            <tr>
                                                <th>VISIÓN</th>
                                                <td class="table-info-cell"><?php echo htmlspecialchars($empresa_data['vision']); ?></td>
                                                <td><input type="text" name="objetivos_especificos[vision]" class="form-control" value="<?php echo htmlspecialchars($datos_guardados['objetivos_especificos']['vision'] ?? ''); ?>" placeholder="Objetivo específico..."></td>
                                            </tr>
                                            <?php foreach ($valores_lista as $index => $valor): if(empty(trim($valor))) continue; ?>
                                            <tr>
                                                <?php if($index === 0): ?>
                                                <th rowspan="<?php echo count($valores_lista); ?>">VALORES</th>
                                                <?php endif; ?>
                                                <td class="table-info-cell"><?php echo htmlspecialchars(trim($valor)); ?></td>
                                                <td><input type="text" name="objetivos_especificos[valor_<?php echo $index; ?>]" class="form-control" value="<?php echo htmlspecialchars($datos_guardados['objetivos_especificos']['valor_'.$index] ?? ''); ?>" placeholder="Objetivo específico..."></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-save btn-lg">Guardar Objetivos Estratégicos</button>
                        </div>
                    </form>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="valores.php" class="btn btn-nav">&laquo; Anterior: Valores</a>
                        <a href="dashboard.php" class="btn btn-nav-outline">Volver al Índice</a>
                        <a href="analisis_foda.php" class="btn btn-save">Siguiente: Análisis FODA &raquo;</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
$mysqli->close();
?>