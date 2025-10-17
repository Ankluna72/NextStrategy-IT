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

// Array con las 25 preguntas del autodiagnóstico
$preguntas = [
    1 => "La empresa tiene una política sistematizada de cero defectos en la producción de productos/servicios.",
    2 => "La empresa emplea los medios productivos tecnológicamente más avanzados de su sector.",
    3 => "La empresa dispone de un sistema de información y control de gestión eficiente y eficaz.",
    4 => "Los medios técnicos y tecnológicos de la empresa están preparados para competir en un futuro a corto, medio y largo plazo.",
    5 => "La empresa es un referente en su sector en I+D+i.",
    6 => "La excelencia de los procedimientos de la empresa (en ISO, etc.) son una principal fuente de ventaja competitiva.",
    7 => "La empresa dispone de página web, y esta se emplea no sólo como escaparate virtual de productos/servicios, sino también para establecer relaciones con clientes y proveedores.",
    8 => "Los productos/servicios que desarrolla nuestra empresa llevan incorporada una tecnología difícil de imitar.",
    9 => "La empresa es referente en su sector en la optimización, en términos de coste, de su cadena de producción, siendo ésta una de sus principales ventajas competitivas.",
    10 => "La informatización de la empresa es una fuente de ventaja competitiva clara respecto a sus competidores.",
    11 => "Los canales de distribución de la empresa son una importante fuente de ventajas competitivas.",
    12 => "Los productos/servicios de la empresa son altamente, y diferencialmente, valorados por el cliente respecto a nuestros competidores.",
    13 => "La empresa dispone y ejecuta un sistemático plan de marketing y ventas.",
    14 => "La empresa tiene optimizada su gestión financiera.",
    15 => "La empresa busca continuamente el mejorar la relación con sus clientes cortando los plazos de ejecución, personalizando la oferta o mejorando las condiciones de entrega. Pero siempre partiendo de un plan previo.",
    16 => "La empresa es referente en su sector en el lanzamiento de innovadores productos y servicio de éxito demostrado en el mercado.",
    17 => "Los Recursos Humanos son especialmente responsables del éxito de la empresa, considerándolos incluso como el principal activo estratégico.",
    18 => "Se tiene una plantilla altamente motivada, que conoce con claridad las metas, objetivos y estrategias de la organización.",
    19 => "La empresa siempre trabaja conforme a una estrategia y objetivos claros.",
    20 => "La gestión del circulante está optimizada.",
    21 => "Se tiene definido claramente el posicionamiento estratégico de todos los productos de la empresa.",
    22 => "Se dispone de una política de marca basada en la reputación que la empresa genera, en la gestión de relación con el cliente y en el posicionamiento estratégico previamente definido.",
    23 => "La cartera de clientes de nuestra empresa está altamente fidelizada, ya que tenemos como principal propósito el deleitarlos día a día.",
    24 => "Nuestra política y equipo de ventas y marketing es una importante ventaja competitiva de nuestra empresa respecto al sector.",
    25 => "El servicio al cliente que prestamos es uno de nuestras principales ventajas competitivas respecto a nuestros competidores."
];

// ---- LÓGICA PARA GUARDAR DATOS ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mysqli->begin_transaction();
    try {
        // Guardar respuestas del autodiagnóstico
        $stmt_delete = $mysqli->prepare("DELETE FROM cadena_valor_respuestas WHERE id_empresa = ?");
        $stmt_delete->bind_param("i", $id_empresa_actual);
        $stmt_delete->execute();
        $stmt_delete->close();

        $stmt_insert = $mysqli->prepare("INSERT INTO cadena_valor_respuestas (id_empresa, pregunta_num, respuesta_valor) VALUES (?, ?, ?)");
        foreach ($preguntas as $num => $texto) {
            if (isset($_POST['q' . $num])) {
                $valor = intval($_POST['q' . $num]);
                $stmt_insert->bind_param("iii", $id_empresa_actual, $num, $valor);
                $stmt_insert->execute();
            }
        }
        $stmt_insert->close();

        // Guardar fortalezas y debilidades en la tabla FODA
        $stmt_foda = $mysqli->prepare("INSERT INTO foda (id_empresa, tipo, descripcion) VALUES (?, ?, ?)");
        
        // Fortalezas
        if(isset($_POST['fortalezas']) && is_array($_POST['fortalezas'])) {
            $tipo = 'fortaleza';
            foreach($_POST['fortalezas'] as $f) {
                $descripcion = trim($f);
                if(!empty($descripcion)) {
                    $stmt_foda->bind_param("iss", $id_empresa_actual, $tipo, $descripcion);
                    $stmt_foda->execute();
                }
            }
        }
        // Debilidades
        if(isset($_POST['debilidades']) && is_array($_POST['debilidades'])) {
            $tipo = 'debilidad';
            foreach($_POST['debilidades'] as $d) {
                $descripcion = trim($d);
                if(!empty($descripcion)) {
                    $stmt_foda->bind_param("iss", $id_empresa_actual, $tipo, $descripcion);
                    $stmt_foda->execute();
                }
            }
        }
        $stmt_foda->close();


        $mysqli->commit();
        $mensaje = '<div class="alert alert-success">Diagnóstico y reflexiones guardadas correctamente.</div>';
    } catch (mysqli_sql_exception $exception) {
        $mysqli->rollback();
        $mensaje = '<div class="alert alert-danger">Error al guardar los datos.</div>';
    }
}

// --- LÓGICA PARA CARGAR DATOS ---
$respuestas_guardadas = [];
$stmt_select = $mysqli->prepare("SELECT pregunta_num, respuesta_valor FROM cadena_valor_respuestas WHERE id_empresa = ?");
$stmt_select->bind_param("i", $id_empresa_actual);
$stmt_select->execute();
$resultado = $stmt_select->get_result();
while ($fila = $resultado->fetch_assoc()) {
    $respuestas_guardadas[$fila['pregunta_num']] = $fila['respuesta_valor'];
}
$stmt_select->close();

// Calcular resultado para mostrarlo si ya está guardado
$potencial_mejora = null;
if (!empty($respuestas_guardadas) && count($respuestas_guardadas) == count($preguntas)) {
    $suma_puntos = array_sum($respuestas_guardadas);
    $max_puntos = count($preguntas) * 4;
    $potencial_mejora = (1 - ($suma_puntos / $max_puntos)) * 100;
}

?>

<div class="container mt-4">
    <div class="module-container">
        <div class="module-header">
            <h2 class="module-title">Autodiagnóstico de la Cadena de Valor Interna</h2>
        </div>
        <div class="module-content">
            <p>A continuación, valore su empresa de 0 a 4 en función de cada una de las afirmaciones para conocer porcentualmente el potencial de mejora de la cadena de valor.</p>
            <p><strong>Valoración:</strong> 0= En total desacuerdo; 1= No está de acuerdo; 2= Está de acuerdo; 3= Está bastante de acuerdo; 4= En total acuerdo.</p>
            
            <?php echo $mensaje; ?>

            <form action="autodiagnostico_cadena_valor.php" method="POST">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>AUTODIAGNÓSTICO DE LA CADENA DE VALOR INTERNA</th>
                                <th class="text-center" style="width: 5%;">0</th>
                                <th class="text-center" style="width: 5%;">1</th>
                                <th class="text-center" style="width: 5%;">2</th>
                                <th class="text-center" style="width: 5%;">3</th>
                                <th class="text-center" style="width: 5%;">4</th>
                            </tr>
                        </thead>
                        <tbody id="diagnostic-table-body">
                            <?php foreach ($preguntas as $num => $texto): ?>
                            <tr>
                                <td><?php echo "<strong>$num.</strong> " . htmlspecialchars($texto); ?></td>
                                <?php for ($i = 0; $i <= 4; $i++): ?>
                                <td class="text-center">
                                    <input class="form-check-input" type="radio" 
                                           name="q<?php echo $num; ?>" 
                                           value="<?php echo $i; ?>"
                                           <?php echo (isset($respuestas_guardadas[$num]) && $respuestas_guardadas[$num] == $i) ? 'checked' : ''; ?>
                                           required>
                                </td>
                                <?php endfor; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="card my-4 text-center" id="resultado-diagnostico" 
                     style="<?php echo ($potencial_mejora === null) ? 'display: none;' : ''; ?>">
                    <div class="card-header">
                        <h4>Resultado del Diagnóstico (Previa)</h4>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">Potencial de Mejora de la Cadena de Valor Interna</h5>
                        <p class="display-4 fw-bold text-primary" id="potencial-porcentaje">
                            <?php echo ($potencial_mejora !== null) ? number_format($potencial_mejora, 2) . '%' : '#¡REF!'; ?>
                        </p>
                        <p class="text-muted">Un valor más alto indica una mayor área de oportunidad para mejorar.</p>
                    </div>
                </div>


                <div class="card mt-5">
                    <div class="card-header">
                        Reflexión y Conclusiones
                    </div>
                    <div class="card-body">
                        <p>Reflexione sobre el resultado obtenido. Anote aquellas observaciones que puedan ser de su interés e identifique sus fortalezas y debilidades respecto a su cadena de valor. Éstas se añadirán a su análisis FODA.</p>
                        
                        <h5 class="mt-4">Fortalezas Identificadas</h5>
                        <div class="mb-3">
                            <input type="text" name="fortalezas[]" class="form-control mb-2" placeholder="Ej: Nuestro servicio al cliente es una ventaja competitiva principal.">
                            <input type="text" name="fortalezas[]" class="form-control" placeholder="Añada otra fortaleza si lo desea...">
                        </div>

                        <h5 class="mt-4">Debilidades Identificadas</h5>
                        <div class="mb-3">
                            <input type="text" name="debilidades[]" class="form-control mb-2" placeholder="Ej: No empleamos los medios tecnológicos más avanzados del sector.">
                            <input type="text" name="debilidades[]" class="form-control" placeholder="Añada otra debilidad si lo desea...">
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2 mt-4">
                     <button type="submit" class="btn btn-save btn-lg">Guardar Diagnóstico y Reflexiones</button>
                </div>
            </form>

            <div class="d-flex justify-content-between mt-4">
                <a href="cadena_valor.php" class="btn btn-nav">&laquo; Anterior: Cadena de Valor</a>
                <a href="dashboard.php" class="btn btn-nav-outline">Volver al Índice</a>
                <a href="matriz_bcg.php" class="btn btn-save">Siguiente: Matriz BCG &raquo;</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const totalPreguntas = <?php echo count($preguntas); ?>;
    const radios = document.querySelectorAll('#diagnostic-table-body input[type="radio"]');
    const resultadoContainer = document.getElementById('resultado-diagnostico');
    const porcentajeSpan = document.getElementById('potencial-porcentaje');

    function calcularPotencial() {
        const respuestasSeleccionadas = document.querySelectorAll('#diagnostic-table-body input[type="radio"]:checked');
        
        if (respuestasSeleccionadas.length < totalPreguntas) {
            resultadoContainer.style.display = 'none';
            porcentajeSpan.textContent = 'Termine de marcar para ver el resultado'; 
            return;
        }

        let sumaPuntos = 0;
        respuestasSeleccionadas.forEach(radio => {
            sumaPuntos += parseInt(radio.value, 10);
        });

        const maxPuntos = totalPreguntas * 4;
        const potencialMejora = (1 - (sumaPuntos / maxPuntos)) * 100;
        
        porcentajeSpan.textContent = potencialMejora.toFixed(2) + '%';
        resultadoContainer.style.display = 'block';
    }

    radios.forEach(radio => {
        radio.addEventListener('change', calcularPotencial);
    });
});
</script>

<?php
require_once 'includes/footer.php';
$mysqli->close();
?>
