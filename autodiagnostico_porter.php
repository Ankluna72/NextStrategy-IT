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

$pageStyles = ['css/modules.css', 'css/porter.css']; 
require_once 'includes/db_connection.php';
require_once 'includes/header.php';

$id_empresa_actual = $_SESSION['id_empresa_actual'];
$id_usuario_actual = $_SESSION['id_usuario'];
$mensaje = '';

$criterios = [
    1 => 'Crecimiento',
    2 => 'Naturaleza de los competidores',
    3 => 'Posibilidad capacidad productiva',
    4 => 'Rentabilidad modis distributivos',
    5 => 'Diferenciación del producto',
    6 => 'Barreras de salida',
    7 => 'Economías de escala',
    8 => 'Necesidad de capital',
    9 => 'Acceso a la tecnología',
    10 => 'Reglamentos o leyes limitativos',
    11 => 'Trámites burocráticos',
    12 => 'Reacción esperada actuales competidores',
    13 => 'Número de clientes',
    14 => 'Posibilidad de integración ascendente',
    15 => 'Rentabilidad de los clientes',
    16 => 'Costo de cambio de proveedor para cliente',
    17 => 'Disponibilidad de Productos Sustitutivos'
];
$total_criterios = count($criterios);

$valor_labels = [0 => 'Hostil', 1 => 'Nada', 2 => 'Poco', 3 => 'Medio', 4 => 'Alto', 5 => 'Muy Alto'];

$labels_extremos = [
    1 => ['Lento', 'Rápido'],
    2 => ['Muchos', 'Pocos'],
    3 => ['Sí', 'No'],
    4 => ['Baja', 'Alta'],
    5 => ['Escasa', 'Elevada'],
    6 => ['Bajas', 'Altas'],
    7 => ['No', 'Sí'],
    8 => ['Bajas', 'Altas'],
    9 => ['Fácil', 'Difícil'],
    10 => ['No', 'Sí'],
    11 => ['No', 'Sí'],
    12 => ['Escasa', 'Enérgica'],
    13 => ['Pocos', 'Muchos'],
    14 => ['Pequeña', 'Grande'],
    15 => ['Baja', 'Alta'],
    16 => ['Bajo', 'Alto'],
    17 => ['Grande', 'Pequeña']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mysqli->begin_transaction();
    try {
        // Guardar respuestas de Porter (si existen)
        $stmt_porter = $mysqli->prepare("INSERT INTO porter_respuestas (id_empresa, criterio_id, valor) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
        $criterios_guardados = 0;
        foreach ($criterios as $id => $descripcion) {
            if (isset($_POST['criterio_' . $id])) {
                $valor = intval($_POST['criterio_' . $id]);
                $stmt_porter->bind_param("iii", $id_empresa_actual, $id, $valor);
                $stmt_porter->execute();
                $criterios_guardados++;
            }
        }
        $stmt_porter->close();

        $foda_items = [
            'oportunidad' => [1 => trim($_POST['o1'] ?? ''), 2 => trim($_POST['o2'] ?? '')],
            'amenaza' => [1 => trim($_POST['a1'] ?? ''), 2 => trim($_POST['a2'] ?? '')]
        ];
        
        $stmt_delete_foda = $mysqli->prepare("DELETE FROM foda WHERE id_empresa = ? AND id_usuario = ? AND origen = 'porter'");
        $stmt_delete_foda->bind_param("ii", $id_empresa_actual, $id_usuario_actual);
        $stmt_delete_foda->execute();
        $stmt_delete_foda->close();

        $stmt_foda = $mysqli->prepare("INSERT INTO foda (id_empresa, id_usuario, tipo, descripcion, origen, posicion) VALUES (?, ?, ?, ?, 'porter', ?)");
        foreach ($foda_items as $tipo => $items) {
            foreach ($items as $posicion => $descripcion) {
                if (!empty(trim($descripcion))) {
                    $stmt_foda->bind_param("iissi", $id_empresa_actual, $id_usuario_actual, $tipo, $descripcion, $posicion);
                    $stmt_foda->execute();
                }
            }
        }
        $stmt_foda->close();

        $mysqli->commit();
        $mensaje = '<div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>✓ Análisis guardado correctamente.</strong> Se guardaron ' . $criterios_guardados . ' criterios Porter y las oportunidades/amenazas FODA.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    } catch (Exception $e) {
        $mysqli->rollback();
        error_log("ERROR PORTER: " . $e->getMessage());
        error_log("ERROR PORTER Trace: " . $e->getTraceAsString());
        $mensaje = '<div class="alert alert-danger">Error al guardar el análisis: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

$respuestas_guardadas = [];
$stmt_load = $mysqli->prepare("SELECT criterio_id, valor FROM porter_respuestas WHERE id_empresa = ?");
$stmt_load->bind_param("i", $id_empresa_actual);
$stmt_load->execute();
$result = $stmt_load->get_result();
while ($row = $result->fetch_assoc()) {
    $respuestas_guardadas[$row['criterio_id']] = $row['valor'];
}
$stmt_load->close();

$foda_guardado = ['o1' => '', 'o2' => '', 'a1' => '', 'a2' => ''];
$stmt_foda_load = $mysqli->prepare("SELECT tipo, descripcion, posicion FROM foda WHERE id_empresa = ? AND id_usuario = ? AND origen = 'porter'");
$stmt_foda_load->bind_param("ii", $id_empresa_actual, $id_usuario_actual);
$stmt_foda_load->execute();
$result_foda = $stmt_foda_load->get_result();
while ($row = $result_foda->fetch_assoc()) {
    if ($row['tipo'] == 'oportunidad' && $row['posicion'] == 1) $foda_guardado['o1'] = $row['descripcion'];
    if ($row['tipo'] == 'oportunidad' && $row['posicion'] == 2) $foda_guardado['o2'] = $row['descripcion'];
    if ($row['tipo'] == 'amenaza' && $row['posicion'] == 1) $foda_guardado['a1'] = $row['descripcion'];
    if ($row['tipo'] == 'amenaza' && $row['posicion'] == 2) $foda_guardado['a2'] = $row['descripcion'];
}
$stmt_foda_load->close();

$total_score = 0;
foreach ($respuestas_guardadas as $valor) {
    $total_score += $valor;
}
$todos_completos = count($respuestas_guardadas) === $total_criterios;

$conclusion = '';
if ($total_score < 30) {
    $conclusion = "Estamos en un mercado altamente competitivo, en el que es muy difícil hacerse un hueco en el mercado.";
} else if ($total_score < 45) {
    $conclusion = "El mercado presenta competitividad moderada con algunas oportunidades.";
} else if ($total_score < 60) {
    $conclusion = "La situación actual del mercado es favorable a la empresa.";
} else {
    $conclusion = "El mercado es altamente favorable y presenta excelentes oportunidades de crecimiento.";
}

?>

<div class="container mt-4">
    <div class="module-container">
        <div class="module-header">
            <h2 class="module-title">Autodiagnóstico: 5 Fuerzas de Porter</h2>
        </div>
        <div class="module-content">
            <div class="explanation-box p-3 mb-4">
                <p>A continuación marque con una X en las casillas que estime conveniente según el estado actual de su empresa. Valore su perfil competitivo en la escala Hostil-Favorable. Al finalizar lea la conclusión, para su caso particular, relativa al análisis del entorno próximo.</p>
            </div>
            
            <?php echo $mensaje; ?>

            <form action="autodiagnostico_porter.php" method="POST" id="form-porter">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover porter-table">
                        <thead class="table-light">
                            <tr>
                                <th>PERFIL COMPETITIVO</th>
                                <th class="label-hostil">Hostil</th>
                                <?php foreach ($valor_labels as $val_id => $label): ?>
                                    <th class="radio-cell"><?php echo "$label ($val_id)"; ?></th>
                                <?php endforeach; ?>
                                <th class="label-favorable">Favorable</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="9" class="header-group">Finalidad empresas del sector</td>
                            </tr>
                            <?php 
                            $section1_ids = [1, 2, 3, 4, 5, 6];
                            foreach ($section1_ids as $id): 
                                $valor_guardado = $respuestas_guardadas[$id] ?? -1;
                            ?>
                            <tr>
                                <td class="criterion-name"><?php echo $criterios[$id]; ?></td>
                                <td class="label-hostil"><?php echo $labels_extremos[$id][0]; ?></td>
                                <?php foreach ($valor_labels as $val_id => $label): ?>
                                <td class="radio-cell">
                                    <input class="form-check-input porter-radio" type="radio" name="criterio_<?php echo $id; ?>" value="<?php echo $val_id; ?>" <?php echo ($valor_guardado == $val_id) ? 'checked' : ''; ?>>
                                </td>
                                <?php endforeach; ?>
                                <td class="label-favorable"><?php echo $labels_extremos[$id][1]; ?></td>
                            </tr>
                            <?php endforeach; ?>

                            <tr>
                                <td colspan="9" class="header-group">Barreras de Entrada</td>
                            </tr>
                            <?php 
                            $section2_ids = [7, 8, 9, 10, 11, 12];
                            foreach ($section2_ids as $id): 
                                $valor_guardado = $respuestas_guardadas[$id] ?? -1;
                            ?>
                            <tr>
                                <td class="criterion-name"><?php echo $criterios[$id]; ?></td>
                                <td class="label-hostil"><?php echo $labels_extremos[$id][0]; ?></td>
                                <?php foreach ($valor_labels as $val_id => $label): ?>
                                <td class="radio-cell">
                                    <input class="form-check-input porter-radio" type="radio" name="criterio_<?php echo $id; ?>" value="<?php echo $val_id; ?>" <?php echo ($valor_guardado == $val_id) ? 'checked' : ''; ?> required>
                                </td>
                                <?php endforeach; ?>
                                <td class="label-favorable"><?php echo $labels_extremos[$id][1]; ?></td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <tr>
                                <td colspan="9" class="header-group">Poder de los Clientes</td>
                            </tr>
                            <?php 
                            $section3_ids = [13, 14, 15, 16];
                            foreach ($section3_ids as $id): 
                                $valor_guardado = $respuestas_guardadas[$id] ?? -1;
                            ?>
                            <tr>
                                <td class="criterion-name"><?php echo $criterios[$id]; ?></td>
                                <td class="label-hostil"><?php echo $labels_extremos[$id][0]; ?></td>
                                <?php foreach ($valor_labels as $val_id => $label): ?>
                                <td class="radio-cell">
                                    <input class="form-check-input porter-radio" type="radio" name="criterio_<?php echo $id; ?>" value="<?php echo $val_id; ?>" <?php echo ($valor_guardado == $val_id) ? 'checked' : ''; ?> required>
                                </td>
                                <?php endforeach; ?>
                                <td class="label-favorable"><?php echo $labels_extremos[$id][1]; ?></td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <tr>
                                <td colspan="9" class="header-group">Productos sustitutivos</td>
                            </tr>
                            <?php 
                            $section4_ids = [17];
                            foreach ($section4_ids as $id): 
                                $valor_guardado = $respuestas_guardadas[$id] ?? -1;
                            ?>
                            <tr>
                                <td class="criterion-name"><?php echo $criterios[$id]; ?></td>
                                <td class="label-hostil"><?php echo $labels_extremos[$id][0]; ?></td>
                                <?php foreach ($valor_labels as $val_id => $label): ?>
                                <td class="radio-cell">
                                    <input class="form-check-input porter-radio" type="radio" name="criterio_<?php echo $id; ?>" value="<?php echo $val_id; ?>" <?php echo ($valor_guardado == $val_id) ? 'checked' : ''; ?> required>
                                </td>
                                <?php endforeach; ?>
                                <td class="label-favorable"><?php echo $labels_extremos[$id][1]; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="card my-4" id="resultado-porter" style="<?php echo $todos_completos ? '' : 'display: none;'; ?>">
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-9">
                                <h4 class="mb-2">CONCLUSIÓN</h4>
                                <p class="lead mb-0" id="porter-conclusion-text">
                                    <?php echo $todos_completos ? $conclusion : 'Complete el análisis para ver la conclusión.'; ?>
                                </p>
                            </div>
                            <div class="col-md-3 text-center text-md-end mt-3 mt-md-0">
                                <h5 class="text-muted mb-1">Puntaje Total</h5>
                                <h1 class="display-4 fw-bold text-success" id="porter-total-score">
                                    <?php echo $todos_completos ? $total_score : '0'; ?>
                                </h1>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="explanation-box p-3 mb-4">
                    <p class="mb-0">Una vez analizado el entorno próximo de su empresa, es decir, análisis externo de su microentorno, identifique las oportunidades y amenazas más relevantes que desee que se reflejen en el análisis FODA.</p>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="card h-100 foda-card">
                            <div class="card-header foda-oportunidad">OPORTUNIDADES</div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <label for="o1" class="form-label fw-bold">O1:</label>
                                    <input type="text" name="o1" id="o1" class="form-control" value="<?php echo htmlspecialchars($foda_guardado['o1']); ?>" placeholder="Ingrese la primera oportunidad...">
                                </div>
                                <div>
                                    <label for="o2" class="form-label fw-bold">O2:</label>
                                    <input type="text" name="o2" id="o2" class="form-control" value="<?php echo htmlspecialchars($foda_guardado['o2']); ?>" placeholder="Ingrese la segunda oportunidad...">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card h-100 foda-card">
                            <div class="card-header foda-amenaza">AMENAZAS</div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <label for="a1" class="form-label fw-bold">A1:</label>
                                    <input type="text" name="a1" id="a1" class="form-control" value="<?php echo htmlspecialchars($foda_guardado['a1']); ?>" placeholder="Ingrese la primera amenaza...">
                                </div>
                                <div>
                                    <label for="a2" class="form-label fw-bold">A2:</label>
                                    <input type="text" name="a2" id="a2" class="form-control" value="<?php echo htmlspecialchars($foda_guardado['a2']); ?>" placeholder="Ingrese la segunda amenaza...">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <p class="text-success small fst-italic mb-2 text-center">Debe completar el test primero para poder guardar las oportunidades y amenazas.</p>
                <div class="text-center mt-3">
                    <button type="submit" class="btn btn-save px-5 py-2" style="min-width: 700px;">
                        <i class="fas fa-save me-2"></i>Guardar Análisis
                    </button>
                </div>
            </form>

            <div class="d-flex justify-content-between mt-4">
                <a href="porter_5fuerzas.php" class="btn btn-nav">&laquo; Anterior: Info. Porter</a>
                <a href="dashboard.php" class="btn btn-nav-outline">Volver al Índice</a>
                <a href="analisis_pest.php" class="btn btn-save">Siguiente: PEST &raquo;</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const radios = document.querySelectorAll('.porter-radio');
    const totalCriterios = <?php echo $total_criterios; ?>;
    const resultadoContainer = document.getElementById('resultado-porter');
    const conclusionText = document.getElementById('porter-conclusion-text');
    const totalScoreText = document.getElementById('porter-total-score');

    function calcularPorter() {
        const checkedRadios = document.querySelectorAll('.porter-radio:checked');
        
        let total_score = 0;
        checkedRadios.forEach(radio => {
            total_score += parseInt(radio.value, 10);
        });

        if (checkedRadios.length < totalCriterios) {
            resultadoContainer.style.display = 'none';
            return;
        }

        let conclusion = '';
        if (total_score < 30) {
            conclusion = "Estamos en un mercado altamente competitivo, en el que es muy difícil hacerse un hueco en el mercado.";
        } else if (total_score < 45) {
            conclusion = "El mercado presenta competitividad moderada con algunas oportunidades.";
        } else if (total_score < 60) {
            conclusion = "La situación actual del mercado es favorable a la empresa.";
        } else {
            conclusion = "El mercado es altamente favorable y presenta excelentes oportunidades de crecimiento.";
        }

        totalScoreText.innerText = total_score;
        conclusionText.innerText = conclusion;
        
        if (resultadoContainer.style.display === 'none') {
            resultadoContainer.style.display = 'block';
            resultadoContainer.classList.add('animated-result');
        }
    }

    radios.forEach(radio => {
        radio.addEventListener('change', calcularPorter);
    });
});
</script>

<?php
require_once 'includes/footer.php';
$mysqli->close();
?>