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
$mensaje = '';

$preguntas = [
    1 => 'Los cambios en la composicion etnica de los consumidores de nuestro mercado esta teniendo un notable impacto.',
    2 => 'El envejecimiento de la poblacion tiene un importante impacto en la demanda.',
    3 => 'Los nuevos estilos de vida y tendencias originan cambios en la oferta de nuestro sector.',
    4 => 'El envejecimiento de la poblacion tiene un importante impacto en la oferta del sector donde operamos.',
    5 => 'Las variaciones en el nivel de riqueza de la poblacion impactan considerablemente en la demanda de los productos/servicios del sector donde operamos.',
    6 => 'La legislacion fiscal afecta muy considerablemente a la economia de las empresas del sector donde operamos.',
    7 => 'La legislacion laboral afecta muy considerablemente a la operativa del sector donde actuamos.',
    8 => 'Las subvenciones otorgadas por las Administraciones Publicas son claves en el desarrollo competitivo del mercado donde operamos.',
    9 => 'El impacto que tiene la legislacion de proteccion al consumidor, en la manera de producir bienes y/o servicios es muy importante.',
    10 => 'La normativa autonomica tiene un impacto considerable en el funcionamiento del sector donde actuamos.',
    11 => 'Las expectativas de crecimiento economico generales afectan crucialmente al mercado donde operamos.',
    12 => 'La politica de tipos de interes es fundamental en el desarrollo financiero del sector donde trabaja nuestra empresa.',
    13 => 'La globalizacion permite a nuestra industria gozar de importantes oportunidades en nuevos mercados.',
    14 => 'La situacion del empleo es fundamental para el desarrollo economico de nuestra empresa y nuestro sector.',
    15 => 'Las expectativas del ciclo economico de nuestro sector impactan en la situacion economica de sus empresas.',
    16 => 'Las Administraciones Publicas estan incentivando el esfuerzo tecnologico de las empresas de nuestro sector.',
    17 => 'Internet, el comercio electronico, el wireless y otras NTIC estan impactando en la demanda de nuestros productos/servicios y en los de la competencia.',
    18 => 'El empleo de NTIC´s es generalizado en el sector donde trabajamos.',
    19 => 'En nuestro sector, es de gran importancia ser pionero o referente en el empleo de aplicaciones tecnologicas.',
    20 => 'En el sector donde operamos, para ser competitivos, es condicion "sine qua non" innovar constantemente.',
    21 => 'La legislacion medioambiental afecta al desarrollo de nuestro sector.',
    22 => 'Los clientes de nuestro mercado exigen que se seamos socialmente responsables, en el plano medioambiental.',
    23 => 'En nuestro sector, la politicas medioambientales son una fuente de ventajas competitivas.',
    24 => 'La creciente preocupacion social por el medio ambiente impacta notablemente en la demanda de productos/servicios ofertados en nuestro mercado.',
    25 => 'El factor ecologico es una fuente de diferenciacion clara en el sector donde opera nuestra empresa.'
];

$factorLabels = [
    'social' => 'Factores Sociales y Demograficos',
    'politico' => 'Factores Politicos',
    'economico' => 'Factores Economicos',
    'tecnologico' => 'Factores Tecnologicos',
    'ambiental' => 'Factores Medioambientales'
];

$factorOrder = array_keys($factorLabels);
$factorByPregunta = [];
foreach (range(1, 25) as $numeroPregunta) {
    if ($numeroPregunta <= 5) {
        $factorByPregunta[$numeroPregunta] = 'social';
    } elseif ($numeroPregunta <= 10) {
        $factorByPregunta[$numeroPregunta] = 'politico';
    } elseif ($numeroPregunta <= 15) {
        $factorByPregunta[$numeroPregunta] = 'economico';
    } elseif ($numeroPregunta <= 20) {
        $factorByPregunta[$numeroPregunta] = 'tecnologico';
    } else {
        $factorByPregunta[$numeroPregunta] = 'ambiental';
    }
}

function computePestStats(array $values, array $factorByPregunta, array $factorOrder): array
{
    $factorSums = [];
    foreach ($factorOrder as $factor) {
        $factorSums[$factor] = 0;
    }

    foreach ($factorByPregunta as $numero => $factor) {
        $valor = isset($values[$numero]) && $values[$numero] !== null ? (int) $values[$numero] : 0;
        $factorSums[$factor] += $valor;
    }

    $factorPercentages = [];
    foreach ($factorSums as $factor => $suma) {
        $porcentaje = ($suma / 20) * 100;
        $factorPercentages[$factor] = round($porcentaje);
    }

    $positiveTexts = [
        'social' => 'HAY UN NOTABLE IMPACTO DE FACTORES SOCIALES Y DEMOGRAFICOS EN EL FUNCIONAMIENTO DE LA EMPRESA',
        'politico' => 'HAY UN NOTABLE IMPACTO DE FACTORES POLITICOS EN EL FUNCIONAMIENTO DE LA EMPRESA',
        'economico' => 'HAY UN NOTABLE IMPACTO DE FACTORES ECONOMICOS EN EL FUNCIONAMIENTO DE LA EMPRESA',
        'tecnologico' => 'HAY UN NOTABLE IMPACTO DE FACTORES TECNOLOGICOS EN EL FUNCIONAMIENTO DE LA EMPRESA',
        'ambiental' => 'HAY UN NOTABLE IMPACTO DEL FACTOR MEDIO AMBIENTAL EN EL FUNCIONAMIENTO DE LA EMPRESA'
    ];

    $negativeTexts = [
        'social' => 'NO HAY UN NOTABLE IMPACTO DE FACTORES SOCIALES Y DEMOGRAFICOS EN EL FUNCIONAMIENTO DE LA EMPRESA',
        'politico' => 'NO HAY UN NOTABLE IMPACTO DE FACTORES POLITICOS EN EL FUNCIONAMIENTO DE LA EMPRESA',
        'economico' => 'NO HAY UN NOTABLE IMPACTO DE FACTORES ECONOMICOS EN EL FUNCIONAMIENTO DE LA EMPRESA',
        'tecnologico' => 'NO HAY UN NOTABLE IMPACTO DE FACTORES TECNOLOGICOS EN EL FUNCIONAMIENTO DE LA EMPRESA',
        'ambiental' => 'NO HAY UN NOTABLE IMPACTO DEL FACTOR MEDIO AMBIENTAL EN EL FUNCIONAMIENTO DE LA EMPRESA'
    ];

    $factorConclusions = [];
    foreach ($factorPercentages as $factor => $porcentaje) {
        $factorConclusions[$factor] = $porcentaje >= 70 ? $positiveTexts[$factor] : $negativeTexts[$factor];
    }

    return [
        'sums' => $factorSums,
        'percentages' => $factorPercentages,
        'conclusions' => $factorConclusions
    ];
}

$respuestasGuardadas = array_fill_keys(array_keys($preguntas), null);
$stmtRespuestas = $mysqli->prepare('SELECT pregunta_num, valor FROM pest_respuestas WHERE id_empresa = ?');
$stmtRespuestas->bind_param('i', $id_empresa_actual);
$stmtRespuestas->execute();
$resultadoRespuestas = $stmtRespuestas->get_result();
while ($fila = $resultadoRespuestas->fetch_assoc()) {
    $numero = (int) $fila['pregunta_num'];
    if (array_key_exists($numero, $respuestasGuardadas)) {
        $respuestasGuardadas[$numero] = (int) $fila['valor'];
    }
}
$stmtRespuestas->close();

$fechaResumen = null;
$stmtFecha = $mysqli->prepare('SELECT fecha_generacion FROM pest_resumen WHERE id_empresa = ?');
$stmtFecha->bind_param('i', $id_empresa_actual);
$stmtFecha->execute();
$stmtFecha->bind_result($fechaResumenDb);
if ($stmtFecha->fetch()) {
    $fechaResumen = $fechaResumenDb;
}
$stmtFecha->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $puntos = $_POST['puntos'] ?? [];
    $o3 = isset($_POST['o3']) ? trim($_POST['o3']) : '';
    $o4 = isset($_POST['o4']) ? trim($_POST['o4']) : '';
    $a3 = isset($_POST['a3']) ? trim($_POST['a3']) : '';
    $a4 = isset($_POST['a4']) ? trim($_POST['a4']) : '';
    $valores = [];
    $errores = [];

    foreach ($preguntas as $numero => $texto) {
        if (!isset($puntos[$numero])) {
            $errores[] = "Falta responder la pregunta {$numero}.";
            continue;
        }
        $valor = filter_var($puntos[$numero], FILTER_VALIDATE_INT);
        if ($valor === false || $valor < 0 || $valor > 4) {
            $errores[] = "Valor invalido en la pregunta {$numero}.";
        } else {
            $valores[$numero] = $valor;
        }
    }

    if (empty($errores) && count($valores) === count($preguntas)) {
        $stats = computePestStats($valores, $factorByPregunta, $factorOrder);
        $porcentajes = $stats['percentages'];
        $conclusiones = $stats['conclusions'];

        $mysqli->begin_transaction();
        try {
            $stmtDelete = $mysqli->prepare('DELETE FROM pest_respuestas WHERE id_empresa = ?');
            $stmtDelete->bind_param('i', $id_empresa_actual);
            $stmtDelete->execute();
            $stmtDelete->close();

            $stmtInsert = $mysqli->prepare('INSERT INTO pest_respuestas (id_empresa, pregunta_num, factor, valor) VALUES (?, ?, ?, ?)');
            $idEmpresa = $id_empresa_actual;
            $numeroPregunta = null;
            $factorPregunta = null;
            $valorPregunta = null;
            $stmtInsert->bind_param('iisi', $idEmpresa, $numeroPregunta, $factorPregunta, $valorPregunta);
            foreach ($valores as $numero => $valor) {
                $numeroPregunta = $numero;
                $factorPregunta = $factorByPregunta[$numero];
                $valorPregunta = $valor;
                $stmtInsert->execute();
            }
            $stmtInsert->close();

            $stmtResumen = $mysqli->prepare(
                'INSERT INTO pest_resumen (
                    id_empresa,
                    puntaje_social,
                    puntaje_politico,
                    puntaje_economico,
                    puntaje_tecnologico,
                    puntaje_ambiental,
                    conclusion_social,
                    conclusion_politico,
                    conclusion_economico,
                    conclusion_tecnologico,
                    conclusion_ambiental
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    puntaje_social = VALUES(puntaje_social),
                    puntaje_politico = VALUES(puntaje_politico),
                    puntaje_economico = VALUES(puntaje_economico),
                    puntaje_tecnologico = VALUES(puntaje_tecnologico),
                    puntaje_ambiental = VALUES(puntaje_ambiental),
                    conclusion_social = VALUES(conclusion_social),
                    conclusion_politico = VALUES(conclusion_politico),
                    conclusion_economico = VALUES(conclusion_economico),
                    conclusion_tecnologico = VALUES(conclusion_tecnologico),
                    conclusion_ambiental = VALUES(conclusion_ambiental),
                    fecha_generacion = CURRENT_TIMESTAMP'
            );

            $puntajeSocial = $porcentajes['social'];
            $puntajePolitico = $porcentajes['politico'];
            $puntajeEconomico = $porcentajes['economico'];
            $puntajeTecnologico = $porcentajes['tecnologico'];
            $puntajeAmbiental = $porcentajes['ambiental'];
            $conclusionSocial = $conclusiones['social'];
            $conclusionPolitico = $conclusiones['politico'];
            $conclusionEconomico = $conclusiones['economico'];
            $conclusionTecnologico = $conclusiones['tecnologico'];
            $conclusionAmbiental = $conclusiones['ambiental'];

            $stmtResumen->bind_param(
                'idddddsssss',
                $id_empresa_actual,
                $puntajeSocial,
                $puntajePolitico,
                $puntajeEconomico,
                $puntajeTecnologico,
                $puntajeAmbiental,
                $conclusionSocial,
                $conclusionPolitico,
                $conclusionEconomico,
                $conclusionTecnologico,
                $conclusionAmbiental
            );
            $stmtResumen->execute();
            $stmtResumen->close();

            // Guardar/actualizar oportunidades y amenazas en tabla foda
            $id_usuario_actual = $_SESSION['id_usuario'];
            $stmtDeleteFoda = $mysqli->prepare("DELETE FROM foda WHERE id_empresa = ? AND id_usuario = ? AND origen = 'pest' AND tipo IN ('oportunidad','amenaza')");
            $stmtDeleteFoda->bind_param('ii', $id_empresa_actual, $id_usuario_actual);
            $stmtDeleteFoda->execute();
            $stmtDeleteFoda->close();

            $stmtFodaInsert = $mysqli->prepare("INSERT INTO foda (id_empresa, id_usuario, tipo, descripcion, origen, posicion) VALUES (?, ?, ?, ?, 'pest', ?)");
            
            // Guardar oportunidades solo si tienen contenido
            if (!empty($o3)) {
                $tipoOp = 'oportunidad';
                $posOp3 = 3;
                $stmtFodaInsert->bind_param('iissi', $id_empresa_actual, $id_usuario_actual, $tipoOp, $o3, $posOp3);
                $stmtFodaInsert->execute();
            }
            if (!empty($o4)) {
                $tipoOp = 'oportunidad';
                $posOp4 = 4;
                $stmtFodaInsert->bind_param('iissi', $id_empresa_actual, $id_usuario_actual, $tipoOp, $o4, $posOp4);
                $stmtFodaInsert->execute();
            }

            // Guardar amenazas solo si tienen contenido
            if (!empty($a3)) {
                $tipoAm = 'amenaza';
                $posAm3 = 3;
                $stmtFodaInsert->bind_param('iissi', $id_empresa_actual, $id_usuario_actual, $tipoAm, $a3, $posAm3);
                $stmtFodaInsert->execute();
            }
            if (!empty($a4)) {
                $tipoAm = 'amenaza';
                $posAm4 = 4;
                $stmtFodaInsert->bind_param('iissi', $id_empresa_actual, $id_usuario_actual, $tipoAm, $a4, $posAm4);
                $stmtFodaInsert->execute();
            }

            $stmtFodaInsert->close();

            $mysqli->commit();
            
            $foda_guardados = (!empty($o3) ? 1 : 0) + (!empty($o4) ? 1 : 0) + (!empty($a3) ? 1 : 0) + (!empty($a4) ? 1 : 0);
            $mensaje = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>✓ Análisis PEST guardado correctamente.</strong> Se guardaron ' . count($valores) . ' respuestas y ' . $foda_guardados . ' elementos FODA.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
            $respuestasGuardadas = $valores;
            $fechaResumen = date('Y-m-d H:i:s');
        } catch (Throwable $e) {
            $mysqli->rollback();
            error_log("ERROR PEST: " . $e->getMessage());
            error_log("ERROR PEST Trace: " . $e->getTraceAsString());
            $mensaje = '<div class="alert alert-danger">Ocurrio un error al guardar el analisis: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    } else {
        $mensaje = '<div class="alert alert-danger">' . implode('<br>', $errores) . '</div>';
        foreach ($valores as $numero => $valor) {
            $respuestasGuardadas[$numero] = $valor;
        }
    }
}

$statsActuales = computePestStats($respuestasGuardadas, $factorByPregunta, $factorOrder);
$porcentajes = $statsActuales['percentages'];
$conclusiones = $statsActuales['conclusions'];

$chartData = [];
foreach ($factorOrder as $factor) {
    $chartData[] = isset($porcentajes[$factor]) ? (int) $porcentajes[$factor] : 0;
}

$fodaOportunidades = [];
$fodaAmenazas = [];

$id_usuario_actual = $_SESSION['id_usuario'];
$stmtFoda = $mysqli->prepare("SELECT descripcion FROM foda WHERE id_empresa = ? AND id_usuario = ? AND tipo = 'oportunidad' AND origen = 'pest' ORDER BY posicion ASC");
$stmtFoda->bind_param('ii', $id_empresa_actual, $id_usuario_actual);
$stmtFoda->execute();
$resultFoda = $stmtFoda->get_result();
while ($row = $resultFoda->fetch_assoc()) {
    $fodaOportunidades[] = $row['descripcion'];
}
$resultFoda->free();
$stmtFoda->close();

$stmtFodaAmenaza = $mysqli->prepare("SELECT descripcion FROM foda WHERE id_empresa = ? AND id_usuario = ? AND tipo = 'amenaza' AND origen = 'pest' ORDER BY posicion ASC");
$stmtFodaAmenaza->bind_param('ii', $id_empresa_actual, $id_usuario_actual);
$stmtFodaAmenaza->execute();
$resultFodaAmenaza = $stmtFodaAmenaza->get_result();
while ($row = $resultFodaAmenaza->fetch_assoc()) {
    $fodaAmenazas[] = $row['descripcion'];
}
$resultFodaAmenaza->free();
$stmtFodaAmenaza->close();

$fechaResumenLegible = null;
if ($fechaResumen) {
    $timestamp = strtotime($fechaResumen);
    if ($timestamp) {
        $fechaResumenLegible = date('d/m/Y H:i', $timestamp);
    }
}
?>

<div class="module-container mt-4 mb-5">
    <div class="module-header">
        <h2 class="module-title">9. ANALISIS EXTERNO: PEST</h2>
        <p class="mt-2 mb-0">Evalua el impacto de los factores del macroentorno sobre tu empresa para anticipar oportunidades y amenazas.</p>
    </div>
    <div class="module-content">
        <?php echo $mensaje; ?>

        <div class="explanation-box mb-4">
            <h3 class="mb-4" style="color: var(--brand-blue); font-weight: 700;">PEST</h3>
            <div style="background: white; padding: 1.5rem; border-radius: .75rem; box-shadow: 0 2px 8px rgba(0,0,0,.05);">
                <canvas id="pestChart" width="800" height="400"></canvas>
            </div>
        </div>

        <form method="POST" class="pest-form" autocomplete="off">
            <div class="table-responsive">
                <table class="table pest-table align-middle">
                    <thead>
                        <tr>
                            <th class="w-50">Autodiagnostico del macroentorno</th>
                            <th class="text-center">0<br><span>En total desacuerdo</span></th>
                            <th class="text-center">1<br><span>No esta de acuerdo</span></th>
                            <th class="text-center">2<br><span>Esta de acuerdo</span></th>
                            <th class="text-center">3<br><span>Bastante de acuerdo</span></th>
                            <th class="text-center">4<br><span>Totalmente de acuerdo</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($preguntas as $numero => $texto): ?>
                            <tr>
                                <td class="pest-question">
                                    <span class="pest-question-index"><?php echo $numero; ?>.</span>
                                    <?php echo htmlspecialchars($texto); ?>
                                </td>
                                <?php for ($valor = 0; $valor <= 4; $valor++): ?>
                                    <td class="text-center">
                                        <input type="radio"
                                               class="form-check-input pest-option"
                                               name="puntos[<?php echo $numero; ?>]"
                                               value="<?php echo $valor; ?>"
                                               <?php echo ($respuestasGuardadas[$numero] !== null && (int)$respuestasGuardadas[$numero] === $valor) ? 'checked' : ''; ?>
                                        >
                                    </td>
                                <?php endfor; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="row g-3 mt-4">
                <?php foreach ($factorOrder as $factor): ?>
                    <div class="col-12">
                        <div class="pest-conclusion-row" id="pest-conclusion-<?php echo $factor; ?>">
                            <?php echo htmlspecialchars($conclusiones[$factor]); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="row mt-4">
                <div class="col-md-6 mb-3">
                    <div class="card h-100 foda-card">
                        <div class="card-header foda-oportunidad">OPORTUNIDADES</div>
                        <div class="card-body">
                            <div class="mb-2">
                                <label for="o3" class="form-label fw-bold">O3:</label>
                                <input type="text" name="o3" id="o3" class="form-control" value="<?php echo isset($fodaOportunidades[0]) ? htmlspecialchars($fodaOportunidades[0]) : ''; ?>" placeholder="Ingrese la tercera oportunidad...">
                            </div>
                            <div>
                                <label for="o4" class="form-label fw-bold">O4:</label>
                                <input type="text" name="o4" id="o4" class="form-control" value="<?php echo isset($fodaOportunidades[1]) ? htmlspecialchars($fodaOportunidades[1]) : ''; ?>" placeholder="Ingrese la cuarta oportunidad...">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card h-100 foda-card">
                        <div class="card-header foda-amenaza">AMENAZAS</div>
                        <div class="card-body">
                            <div class="mb-2">
                                <label for="a3" class="form-label fw-bold">A3:</label>
                                <input type="text" name="a3" id="a3" class="form-control" value="<?php echo isset($fodaAmenazas[0]) ? htmlspecialchars($fodaAmenazas[0]) : ''; ?>" placeholder="Ingrese la tercera amenaza...">
                            </div>
                            <div>
                                <label for="a4" class="form-label fw-bold">A4:</label>
                                <input type="text" name="a4" id="a4" class="form-control" value="<?php echo isset($fodaAmenazas[1]) ? htmlspecialchars($fodaAmenazas[1]) : ''; ?>" placeholder="Ingrese la cuarta amenaza...">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <p class="text-success small fst-italic mb-2 text-center">Debe completar el test primero para poder guardar las oportunidades y amenazas.</p>
            <div class="text-center mt-3">
                <button type="submit" class="btn btn-save px-5 py-2" style="min-width: 700px;">
                    <i class="fas fa-save me-2"></i>Guardar
                </button>
            </div>
        </form>

        <div class="d-flex justify-content-between mt-5">
            <a href="autodiagnostico_porter.php" class="btn btn-nav">&laquo; Volver: Autodiagnóstico: 5 Fuerzas de Porter</a>
            <a href="dashboard.php" class="btn btn-nav-outline">Volver al indice</a>
            <a href="id_estrategias.php" class="btn btn-save">Siguiente: 10. Identificacion de Estrategia &raquo;</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
<script>
    const pestFactorOrder = <?php echo json_encode($factorOrder); ?>;
    const pestFactorLabels = <?php echo json_encode($factorLabels); ?>;
    const pestFactorMap = <?php echo json_encode($factorByPregunta); ?>;
    const pestInitialData = <?php echo json_encode($chartData); ?>;

    const pestConclusionsCopy = {
        social: {
            positive: 'HAY UN NOTABLE IMPACTO DE FACTORES SOCIALES Y DEMOGRAFICOS EN EL FUNCIONAMIENTO DE LA EMPRESA',
            negative: 'NO HAY UN NOTABLE IMPACTO DE FACTORES SOCIALES Y DEMOGRAFICOS EN EL FUNCIONAMIENTO DE LA EMPRESA'
        },
        politico: {
            positive: 'HAY UN NOTABLE IMPACTO DE FACTORES POLITICOS EN EL FUNCIONAMIENTO DE LA EMPRESA',
            negative: 'NO HAY UN NOTABLE IMPACTO DE FACTORES POLITICOS EN EL FUNCIONAMIENTO DE LA EMPRESA'
        },
        economico: {
            positive: 'HAY UN NOTABLE IMPACTO DE FACTORES ECONOMICOS EN EL FUNCIONAMIENTO DE LA EMPRESA',
            negative: 'NO HAY UN NOTABLE IMPACTO DE FACTORES ECONOMICOS EN EL FUNCIONAMIENTO DE LA EMPRESA'
        },
        tecnologico: {
            positive: 'HAY UN NOTABLE IMPACTO DE FACTORES TECNOLOGICOS EN EL FUNCIONAMIENTO DE LA EMPRESA',
            negative: 'NO HAY UN NOTABLE IMPACTO DE FACTORES TECNOLOGICOS EN EL FUNCIONAMIENTO DE LA EMPRESA'
        },
        ambiental: {
            positive: 'HAY UN NOTABLE IMPACTO DEL FACTOR MEDIO AMBIENTAL EN EL FUNCIONAMIENTO DE LA EMPRESA',
            negative: 'NO HAY UN NOTABLE IMPACTO DEL FACTOR MEDIO AMBIENTAL EN EL FUNCIONAMIENTO DE LA EMPRESA'
        }
    };

    let pestChartInstance = null;

    function buildPestChart(dataset) {
        const ctx = document.getElementById('pestChart');
        if (!ctx) { return; }

        if (pestChartInstance) {
            pestChartInstance.destroy();
        }

        pestChartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: pestFactorOrder.map(key => pestFactorLabels[key]),
                datasets: [{
                    label: 'Impacto (%)',
                    data: dataset,
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.6)',
                        'rgba(255, 99, 132, 0.6)',
                        'rgba(255, 159, 64, 0.6)',
                        'rgba(75, 192, 192, 0.6)',
                        'rgba(153, 102, 255, 0.6)'
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 99, 132, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Nivel de impacto (%)'
                        }
                    },
                    x: {
                        ticks: {
                            autoSkip: false,
                            maxRotation: 0,
                            minRotation: 0
                        }
                    }
                },
                plugins: {
                    legend: { display: false },
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        color: '#0f2f46',
                        font: {
                            weight: 'bold'
                        },
                        formatter: value => value + '%'
                    }
                }
            },
            plugins: [ChartDataLabels]
        });
    }

    function updatePestView() {
        const totals = {
            social: 0,
            politico: 0,
            economico: 0,
            tecnologico: 0,
            ambiental: 0
        };

        for (const [pregunta, factor] of Object.entries(pestFactorMap)) {
            const control = document.querySelector(`input[name="puntos[${pregunta}]"]:checked`);
            if (control) {
                totals[factor] += parseInt(control.value, 10);
            }
        }

        const percentages = {};
        const dataset = [];
        pestFactorOrder.forEach(factor => {
            const porcentaje = Math.round((totals[factor] / 20) * 100);
            percentages[factor] = isNaN(porcentaje) ? 0 : porcentaje;
            dataset.push(percentages[factor]);
        });

        buildPestChart(dataset);
        updatePestConclusions(percentages);
    }

    function updatePestConclusions(percentages) {
        pestFactorOrder.forEach(factor => {
            const porcentaje = percentages[factor] ?? 0;
            const conclusionNode = document.getElementById(`pest-conclusion-${factor}`);
            if (conclusionNode) {
                conclusionNode.textContent = porcentaje >= 70
                    ? pestConclusionsCopy[factor].positive
                    : pestConclusionsCopy[factor].negative;
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        buildPestChart(pestInitialData);

        document.querySelectorAll('.pest-option').forEach(radio => {
            radio.addEventListener('change', updatePestView);
        });
    });
</script>

<?php
require_once 'includes/footer.php';
$mysqli->close();
?>
