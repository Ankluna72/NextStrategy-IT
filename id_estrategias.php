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

$pageStyles = ['css/modules.css', 'css/estrategias.css'];
require_once 'includes/db_connection.php';
require_once 'includes/header.php';

$id_empresa_actual = $_SESSION['id_empresa_actual'];
$id_usuario_actual = $_SESSION['id_usuario'];
$mensaje = '';

// Obtener FODA (máximo 4 por categoría) para mostrar bajo la matriz DAFO
$stmt_foda = $mysqli->prepare("SELECT tipo, descripcion, origen FROM foda WHERE id_empresa = ? ORDER BY tipo, id ASC");
$stmt_foda->bind_param("i", $id_empresa_actual);
$stmt_foda->execute();
$result_foda = $stmt_foda->get_result();
$foda_data = [
    'debilidad' => [],
    'amenaza' => [],
    'fortaleza' => [],
    'oportunidad' => []
];
while ($row = $result_foda->fetch_assoc()) {
    if (isset($foda_data[$row['tipo']])) {
        $foda_data[$row['tipo']][] = $row['descripcion'];
    }
}
$stmt_foda->close();

foreach ($foda_data as $tipo => $items) {
    $foda_data[$tipo] = array_slice($items, 0, 4);
}

// Cargar valores guardados de matrices (FO, FA, DO, DA) para esta empresa y usuario
$matriz_vals = [
    'FO' => [], 'FA' => [], 'DO' => [], 'DA' => []
];
if ($stmt_mcv = $mysqli->prepare("SELECT tipo, fila, columna, valor FROM matriz_cruce_valores WHERE id_empresa=? AND id_usuario=?")) {
    $stmt_mcv->bind_param("ii", $id_empresa_actual, $id_usuario_actual);
    $stmt_mcv->execute();
    $res_mcv = $stmt_mcv->get_result();
    while ($r = $res_mcv->fetch_assoc()) {
        $t = $r['tipo'];
        $f = (int)$r['fila'];
        $c = (int)$r['columna'];
        $v = (int)$r['valor'];
        if (!isset($matriz_vals[$t][$f])) { $matriz_vals[$t][$f] = []; }
        $matriz_vals[$t][$f][$c] = $v;
    }
    $stmt_mcv->close();
}

?>

<div class="container mt-4">
    <div class="module-container">
        <div class="module-header">
            <h2 class="module-title">10. Identificación de Estrategias</h2>
        </div>
        <div class="module-content">
            <div class="explanation-box">
                <p style="text-align: justify; line-height: 1.8; color: #0f2f46;">
                    Tras el análisis realizado habiéndose identificado las oportunidades, amenazas, 
                    fortalezas y debilidades, es momento de identificar la estrategia que debe seguir en 
                    su empresa para el logro de sus objetivos empresariales.
                </p>
                <p style="text-align: justify; line-height: 1.8; color: #0f2f46;">
                    Se trata de realizar una Matriz Cruzada tal y como se refleja en el siguiente dibujo 
                    para identificar la estrategia más conveniente a llevar a cabo.
                </p>
            </div>

            <!-- Diagrama de Matriz DAFO -->
            <div class="dafo-diagram-container">
                <div class="dafo-center-label">
                    <span>Matriz DAFO</span>
                </div>
                
                <div class="dafo-grid">
                    <!-- Columna izquierda: Fortalezas y Debilidades -->
                    <div class="dafo-column-left">
                        <div class="dafo-box fortalezas-box">
                            <span>FORTALEZAS</span>
                        </div>
                        <div class="dafo-box debilidades-box">
                            <span>DEBILIDADES</span>
                        </div>
                    </div>

                    <!-- Columna derecha: Oportunidades, Amenazas y Estrategias -->
                    <div class="dafo-column-right">
                        <!-- Fila 1: Oportunidades -->
                        <div class="dafo-row">
                            <div class="dafo-box oportunidades-box">
                                <span>OPORTUNIDADES</span>
                            </div>
                            <div class="dafo-box amenazas-box">
                                <span>AMENAZAS</span>
                            </div>
                        </div>

                        <!-- Fila 2: Estrategias Ofensivas y Defensivas -->
                        <div class="dafo-row">
                            <div class="dafo-box estrategias-ofensivas-box">
                                <span>ESTRATEGIAS OFENSIVAS</span>
                            </div>
                            <div class="dafo-box estrategias-defensivas-box">
                                <span>ESTRATEGIAS DEFENSIVAS</span>
                            </div>
                        </div>

                        <!-- Fila 3: Estrategias de Reorientación y Supervivencia -->
                        <div class="dafo-row">
                            <div class="dafo-box estrategias-reorientacion-box">
                                <span>ESTRATEGIAS DE REORIENTACIÓN</span>
                            </div>
                            <div class="dafo-box estrategias-supervivencia-box">
                                <span>ESTRATEGIAS DE SUPERVIVENCIA</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resumen FODA estilizado debajo del diagrama -->
            <div class="module-container mt-4">
                <div class="module-content">
                    <p class="foda-intro text-center" style="text-align:center;">Según ha ido cumplimentando en las fases anteriores, los factores internos y externos de su empresa son los siguientes:</p>
                    <div class="foda-summary">
                        <div class="foda-row debilidades">
                            <div class="foda-label"><span>DEBILIDADES</span></div>
                            <div class="foda-items">
                                <?php 
                                $debilidades = $foda_data['debilidad'];
                                for ($i = 0; $i < 4; $i++): 
                                    $item = isset($debilidades[$i]) ? $debilidades[$i] : '';
                                ?>
                                    <div class="foda-item<?php echo empty($item) ? ' empty' : ''; ?>"><?php echo !empty($item) ? htmlspecialchars($item) : '&nbsp;'; ?></div>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="foda-row amenazas">
                            <div class="foda-label"><span>AMENAZAS</span></div>
                            <div class="foda-items">
                                <?php 
                                $amenazas = $foda_data['amenaza'];
                                for ($i = 0; $i < 4; $i++): 
                                    $item = isset($amenazas[$i]) ? $amenazas[$i] : '';
                                ?>
                                    <div class="foda-item<?php echo empty($item) ? ' empty' : ''; ?>"><?php echo !empty($item) ? htmlspecialchars($item) : '&nbsp;'; ?></div>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="foda-row fortalezas">
                            <div class="foda-label"><span>FORTALEZAS</span></div>
                            <div class="foda-items">
                                <?php 
                                $fortalezas = $foda_data['fortaleza'];
                                for ($i = 0; $i < 4; $i++): 
                                    $item = isset($fortalezas[$i]) ? $fortalezas[$i] : '';
                                ?>
                                    <div class="foda-item<?php echo empty($item) ? ' empty' : ''; ?>"><?php echo !empty($item) ? htmlspecialchars($item) : '&nbsp;'; ?></div>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="foda-row oportunidades">
                            <div class="foda-label"><span>OPORTUNIDADES</span></div>
                            <div class="foda-items">
                                <?php 
                                $oportunidades = $foda_data['oportunidad'];
                                for ($i = 0; $i < 4; $i++): 
                                    $item = isset($oportunidades[$i]) ? $oportunidades[$i] : '';
                                ?>
                                    <div class="foda-item<?php echo empty($item) ? ' empty' : ''; ?>"><?php echo !empty($item) ? htmlspecialchars($item) : '&nbsp;'; ?></div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Matriz de Cruce: Fortalezas x Oportunidades -->
            <div class="module-container mt-4">
                <div class="module-content">
                    <div class="matriz-cruce-header">
                        <p><strong>Las fortalezas se usan para tomar ventaja en cada una las oportunidades.</strong></p>
                        <p><em>0=En total desacuerdo, 1= No está de acuerdo, 2= Está de acuerdo, 3= Bastante de acuerdo y 4=En total acuerdo</em></p>
                    </div>
                    
                    <div class="matriz-cruce-container">
                        <table class="matriz-cruce-table">
                            <thead>
                                <tr>
                                    <th rowspan="2" class="corner-cell"><i class="fas fa-pen me-2"></i></th>
                                    <th rowspan="2" class="header-fortalezas-col">
                                        <button type="button" class="matriz-save-btn" title="Guardar matriz FO" onclick="guardarMatriz('FO')">
                                            <i class="fas fa-save"></i>
                                        </button>
                                    </th>
                                    <th colspan="4" class="header-oportunidades">OPORTUNIDADES</th>
                                </tr>
                                <tr>
                                    <?php for($i = 1; $i <= 4; $i++): ?>
                                        <th class="col-header <?php echo $i === 1 ? 'o1-header' : ''; ?>">O<?php echo $i; ?></th>
                                    <?php endfor; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td rowspan="4" class="header-fortalezas">FORTALEZAS</td>
                                    <td class="row-label">F1</td>
                                    <?php for($o = 1; $o <= 4; $o++): ?>
                                        <td class="data-cell">
                                            <input type="number" 
                                                   class="matriz-input fo-input" 
                                                   data-row="1" 
                                                   data-col="<?php echo $o; ?>"
                                                   min="0" 
                                                   max="4" 
                                                   value="<?php echo isset($matriz_vals['FO'][1][$o]) ? (int)$matriz_vals['FO'][1][$o] : 0; ?>"
                                                   maxlength="1"
                                                   oninput="limitarUnDigito(this)">
                                        </td>
                                    <?php endfor; ?>
                                </tr>
                                <?php for($f = 2; $f <= 4; $f++): ?>
                                <tr>
                                    <td class="row-label">F<?php echo $f; ?></td>
                                    <?php for($o = 1; $o <= 4; $o++): ?>
                                        <td class="data-cell">
                                            <input type="number" 
                                                   class="matriz-input fo-input" 
                                                   data-row="<?php echo $f; ?>" 
                                                   data-col="<?php echo $o; ?>"
                                                   min="0" 
                                                   max="4" 
                                                   value="<?php echo isset($matriz_vals['FO'][$f][$o]) ? (int)$matriz_vals['FO'][$f][$o] : 0; ?>"
                                                   maxlength="1"
                                                   oninput="limitarUnDigito(this)">
                                        </td>
                                    <?php endfor; ?>
                                </tr>
                                <?php endfor; ?>
                                <tr class="total-row">
                                    <td class="total-label">TOTAL</td>
                                    <td class="total-general-cell">
                                        <span class="total-general-value" id="total-general">0</span>
                                    </td>
                                    <?php for($o = 1; $o <= 4; $o++): ?>
                                        <td class="total-cell">
                                            <span class="total-value" id="total-o<?php echo $o; ?>">0</span>
                                        </td>
                                    <?php endfor; ?>
                                </tr>
                            </tbody>
                        </table>
                        <div class="save-status" id="status-fo" aria-live="polite"></div>
                    </div>
                </div>
            </div>

            <!-- Matriz de Cruce: Fortalezas x Amenazas -->
            <div class="module-container mt-4">
                <div class="module-content">
                    <div class="matriz-cruce-header">
                        <p><strong>Las fortalezas evaden el efecto negativo de las amenazas.</strong></p>
                        <p><em>0=En total desacuerdo, 1= No está de acuerdo, 2= Está de acuerdo, 3= Bastante de acuerdo y 4=En total acuerdo</em></p>
                    </div>
                    <div class="matriz-cruce-container">
                        <table class="matriz-cruce-table">
                            <thead>
                                <tr>
                                    <th rowspan="2" class="corner-cell"><i class="fas fa-pen me-2"></i></th>
                                    <th rowspan="2" class="header-fortalezas-col">
                                        <button type="button" class="matriz-save-btn" title="Guardar matriz FA" onclick="guardarMatriz('FA')">
                                            <i class="fas fa-save"></i>
                                        </button>
                                    </th>
                                    <th colspan="4" class="header-amenazas">AMENAZAS</th>
                                </tr>
                                <tr>
                                    <?php for($i = 1; $i <= 4; $i++): ?>
                                        <th class="col-header">A<?php echo $i; ?></th>
                                    <?php endfor; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td rowspan="4" class="header-fortalezas">FORTALEZAS</td>
                                    <td class="row-label">F1</td>
                                    <?php for($a = 1; $a <= 4; $a++): ?>
                                        <td class="data-cell">
                                            <input type="number" class="matriz-input fa-input" data-row="1" data-col="<?php echo $a; ?>" min="0" max="4" value="<?php echo isset($matriz_vals['FA'][1][$a]) ? (int)$matriz_vals['FA'][1][$a] : 0; ?>" maxlength="1" oninput="limitarUnDigito(this);calcularTotalesFA()">
                                        </td>
                                    <?php endfor; ?>
                                </tr>
                                <?php for($f = 2; $f <= 4; $f++): ?>
                                <tr>
                                    <td class="row-label">F<?php echo $f; ?></td>
                                    <?php for($a = 1; $a <= 4; $a++): ?>
                                        <td class="data-cell">
                                            <input type="number" class="matriz-input fa-input" data-row="<?php echo $f; ?>" data-col="<?php echo $a; ?>" min="0" max="4" value="<?php echo isset($matriz_vals['FA'][$f][$a]) ? (int)$matriz_vals['FA'][$f][$a] : 0; ?>" maxlength="1" oninput="limitarUnDigito(this);calcularTotalesFA()">
                                        </td>
                                    <?php endfor; ?>
                                </tr>
                                <?php endfor; ?>
                                <tr class="total-row">
                                    <td class="total-label">TOTAL</td>
                                    <td class="total-general-cell"><span class="total-general-value" id="total-general-fa">0</span></td>
                                    <?php for($a = 1; $a <= 4; $a++): ?>
                                        <td class="total-cell"><span class="total-value" id="total-fa-a<?php echo $a; ?>">0</span></td>
                                    <?php endfor; ?>
                                </tr>
                            </tbody>
                        </table>
                        <div class="save-status" id="status-fa" aria-live="polite"></div>
                    </div>
                </div>
            </div>

            <!-- Matriz de Cruce: Debilidades x Oportunidades -->
            <div class="module-container mt-4">
                <div class="module-content">
                    <div class="matriz-cruce-header">
                        <p><strong>Superamos las debilidades tomando ventaja de las oportunidades.</strong></p>
                        <p><em>0=En total desacuerdo, 1= No está de acuerdo, 2= Está de acuerdo, 3= Bastante de acuerdo y 4=En total acuerdo</em></p>
                    </div>
                    <div class="matriz-cruce-container">
                        <table class="matriz-cruce-table">
                            <thead>
                                <tr>
                                    <th rowspan="2" class="corner-cell"><i class="fas fa-pen me-2"></i></th>
                                    <th rowspan="2" class="header-fortalezas-col">
                                        <button type="button" class="matriz-save-btn" title="Guardar matriz DO" onclick="guardarMatriz('DO')">
                                            <i class="fas fa-save"></i>
                                        </button>
                                    </th>
                                    <th colspan="4" class="header-oportunidades">OPORTUNIDADES</th>
                                </tr>
                                <tr>
                                    <?php for($i = 1; $i <= 4; $i++): ?>
                                        <th class="col-header">O<?php echo $i; ?></th>
                                    <?php endfor; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td rowspan="4" class="header-debilidades">DEBILIDADES</td>
                                    <td class="row-label">D1</td>
                                    <?php for($o = 1; $o <= 4; $o++): ?>
                                        <td class="data-cell">
                                            <input type="number" class="matriz-input do-input" data-row="1" data-col="<?php echo $o; ?>" min="0" max="4" value="<?php echo isset($matriz_vals['DO'][1][$o]) ? (int)$matriz_vals['DO'][1][$o] : 0; ?>" maxlength="1" oninput="limitarUnDigito(this);calcularTotalesDO()">
                                        </td>
                                    <?php endfor; ?>
                                </tr>
                                <?php for($d = 2; $d <= 4; $d++): ?>
                                <tr>
                                    <td class="row-label">D<?php echo $d; ?></td>
                                    <?php for($o = 1; $o <= 4; $o++): ?>
                                        <td class="data-cell">
                                            <input type="number" class="matriz-input do-input" data-row="<?php echo $d; ?>" data-col="<?php echo $o; ?>" min="0" max="4" value="<?php echo isset($matriz_vals['DO'][$d][$o]) ? (int)$matriz_vals['DO'][$d][$o] : 0; ?>" maxlength="1" oninput="limitarUnDigito(this);calcularTotalesDO()">
                                        </td>
                                    <?php endfor; ?>
                                </tr>
                                <?php endfor; ?>
                                <tr class="total-row">
                                    <td class="total-label">TOTAL</td>
                                    <td class="total-general-cell"><span class="total-general-value" id="total-general-do">0</span></td>
                                    <?php for($o = 1; $o <= 4; $o++): ?>
                                        <td class="total-cell"><span class="total-value" id="total-do-o<?php echo $o; ?>">0</span></td>
                                    <?php endfor; ?>
                                </tr>
                            </tbody>
                        </table>
                        <div class="save-status" id="status-do" aria-live="polite"></div>
                    </div>
                </div>
            </div>

            <!-- Matriz de Cruce: Debilidades x Amenazas -->
            <div class="module-container mt-4">
                <div class="module-content">
                    <div class="matriz-cruce-header">
                        <p><strong>Las debilidades intensifican notablemente el efecto negativo de las amenazas.</strong></p>
                        <p><em>0=En total desacuerdo, 1= No está de acuerdo, 2= Está de acuerdo, 3= Bastante de acuerdo y 4=En total acuerdo</em></p>
                    </div>
                    <div class="matriz-cruce-container">
                        <table class="matriz-cruce-table">
                            <thead>
                                <tr>
                                    <th rowspan="2" class="corner-cell"><i class="fas fa-pen me-2"></i></th>
                                    <th rowspan="2" class="header-fortalezas-col">
                                        <button type="button" class="matriz-save-btn" title="Guardar matriz DA" onclick="guardarMatriz('DA')">
                                            <i class="fas fa-save"></i>
                                        </button>
                                    </th>
                                    <th colspan="4" class="header-amenazas">AMENAZAS</th>
                                </tr>
                                <tr>
                                    <?php for($i = 1; $i <= 4; $i++): ?>
                                        <th class="col-header">A<?php echo $i; ?></th>
                                    <?php endfor; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td rowspan="4" class="header-debilidades">DEBILIDADES</td>
                                    <td class="row-label">D1</td>
                                    <?php for($a = 1; $a <= 4; $a++): ?>
                                        <td class="data-cell">
                                            <input type="number" class="matriz-input da-input" data-row="1" data-col="<?php echo $a; ?>" min="0" max="4" value="<?php echo isset($matriz_vals['DA'][1][$a]) ? (int)$matriz_vals['DA'][1][$a] : 0; ?>" maxlength="1" oninput="limitarUnDigito(this);calcularTotalesDA()">
                                        </td>
                                    <?php endfor; ?>
                                </tr>
                                <?php for($d = 2; $d <= 4; $d++): ?>
                                <tr>
                                    <td class="row-label">D<?php echo $d; ?></td>
                                    <?php for($a = 1; $a <= 4; $a++): ?>
                                        <td class="data-cell">
                                            <input type="number" class="matriz-input da-input" data-row="<?php echo $d; ?>" data-col="<?php echo $a; ?>" min="0" max="4" value="<?php echo isset($matriz_vals['DA'][$d][$a]) ? (int)$matriz_vals['DA'][$d][$a] : 0; ?>" maxlength="1" oninput="limitarUnDigito(this);calcularTotalesDA()">
                                        </td>
                                    <?php endfor; ?>
                                </tr>
                                <?php endfor; ?>
                                <tr class="total-row">
                                    <td class="total-label">TOTAL</td>
                                    <td class="total-general-cell"><span class="total-general-value" id="total-general-da">0</span></td>
                                    <?php for($a = 1; $a <= 4; $a++): ?>
                                        <td class="total-cell"><span class="total-value" id="total-da-a<?php echo $a; ?>">0</span></td>
                                    <?php endfor; ?>
                                </tr>
                            </tbody>
                        </table>
                        <div class="save-status" id="status-da" aria-live="polite"></div>
                    </div>
                </div>
            </div>

            <div class="sintesis-container">
                <h4 class="sintesis-title">SÍNTESIS DE RESULTADOS</h4>
                <table class="sintesis-table">
                    <thead>
                        <tr>
                            <th>Relaciones</th>
                            <th>Tipología de estrategia</th>
                            <th>Puntuación</th>
                            <th>Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="sintesis-rel">FO</td>
                            <td class="sintesis-tipo">Estrategia Ofensiva</td>
                            <td class="sintesis-puntos"><span id="res-fo">0</span></td>
                            <td class="sintesis-desc">Deberá adoptar estrategias de crecimiento</td>
                        </tr>
                        <tr>
                            <td class="sintesis-rel">AF</td>
                            <td class="sintesis-tipo">Estrategia Defensiva</td>
                            <td class="sintesis-puntos"><span id="res-fa">0</span></td>
                            <td class="sintesis-desc">La empresa está preparada para enfrentarse a las amenazas</td>
                        </tr>
                        <tr>
                            <td class="sintesis-rel">AD</td>
                            <td class="sintesis-tipo">Estrategia de Supervivencia</td>
                            <td class="sintesis-puntos"><span id="res-da">0</span></td>
                            <td class="sintesis-desc">Se enfrenta a amenazas externas sin las fortalezas necesarias para luchar con la competencia</td>
                        </tr>
                        <tr>
                            <td class="sintesis-rel">OD</td>
                            <td class="sintesis-tipo">Estrategia de Reorientación</td>
                            <td class="sintesis-puntos"><span id="res-do">0</span></td>
                            <td class="sintesis-desc">La empresa no puede aprovechar las oportunidades porque carece de preparación adecuada</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <a href="analisis_pest.php" class="btn btn-nav">&laquo; Anterior: Análisis Externo: PEST</a>
                <a href="dashboard.php" class="btn btn-nav-outline">Volver al Índice</a>
                <a href="resumen_plan.php" class="btn btn-save">Siguiente: Resumen del Plan &raquo;</a>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
$mysqli->close();
?>

<script>
function calcularTotalesFO() {
    let totalGeneral = 0;
    
    // Calcular totales por columna (Oportunidades)
    for(let o = 1; o <= 4; o++) {
        let total = 0;
        document.querySelectorAll(`.fo-input[data-col="${o}"]`).forEach(input => {
            let val = parseInt(input.value) || 0;
            // Validar rango 0-4
            if(val < 0) {
                input.value = 0;
                val = 0;
            }
            if(val > 4) {
                input.value = 4;
                val = 4;
            }
            total += val;
        });
        document.getElementById(`total-o${o}`).textContent = total;
        totalGeneral += total;
    }
    
    // Actualizar total general
    document.getElementById('total-general').textContent = totalGeneral;
    actualizarSintesis();
}

function limitarUnDigito(el) {
    // Mantener solo primer dígito válido 0-4
    let v = el.value.replace(/[^0-4]/g, '');
    if (v.length > 1) v = v.charAt(0);
    el.value = v === '' ? '0' : v; // si borran queda 0
    // Ajustar si supera 4 por seguridad
    if (parseInt(el.value) > 4) el.value = '4';
    calcularTotalesFO();
}

function calcularTotalesFA() {
    let totalGeneral = 0;
    for (let a = 1; a <= 4; a++) {
        let total = 0;
        document.querySelectorAll(`.fa-input[data-col="${a}"]`).forEach(input => {
            const val = parseInt(input.value) || 0;
            total += val;
        });
        document.getElementById(`total-fa-a${a}`).textContent = total;
        totalGeneral += total;
    }
    document.getElementById('total-general-fa').textContent = totalGeneral;
    actualizarSintesis();
}

function calcularTotalesDO() {
    let totalGeneral = 0;
    for (let o = 1; o <= 4; o++) {
        let total = 0;
        document.querySelectorAll(`.do-input[data-col="${o}"]`).forEach(input => {
            const val = parseInt(input.value) || 0;
            total += val;
        });
        document.getElementById(`total-do-o${o}`).textContent = total;
        totalGeneral += total;
    }
    document.getElementById('total-general-do').textContent = totalGeneral;
    actualizarSintesis();
}

function calcularTotalesDA() {
    let totalGeneral = 0;
    for (let a = 1; a <= 4; a++) {
        let total = 0;
        document.querySelectorAll(`.da-input[data-col="${a}"]`).forEach(input => {
            const val = parseInt(input.value) || 0;
            total += val;
        });
        document.getElementById(`total-da-a${a}`).textContent = total;
        totalGeneral += total;
    }
    document.getElementById('total-general-da').textContent = totalGeneral;
    actualizarSintesis();
}

function actualizarSintesis() {
    const get = id => parseInt((document.getElementById(id)?.textContent || '0')) || 0;
    const fo = get('total-general');
    const fa = get('total-general-fa');
    const da = get('total-general-da');
    const doo = get('total-general-do');
    const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
    set('res-fo', fo);
    set('res-fa', fa);
    set('res-da', da);
    set('res-do', doo);
}

document.addEventListener('DOMContentLoaded', () => {
    // Recalcular totales desde valores precargados
    try { calcularTotalesFO(); } catch(e) {}
    try { calcularTotalesFA(); } catch(e) {}
    try { calcularTotalesDO(); } catch(e) {}
    try { calcularTotalesDA(); } catch(e) {}
    actualizarSintesis();
});

function recolectarCeldas(tipo) {
    const classMap = { FO: '.fo-input', FA: '.fa-input', DO: '.do-input', DA: '.da-input' };
    const selector = classMap[tipo];
    const values = [];
    document.querySelectorAll(selector).forEach(inp => {
        values.push({ row: parseInt(inp.getAttribute('data-row')), col: parseInt(inp.getAttribute('data-col')), val: parseInt(inp.value) || 0 });
    });
    return values;
}

function mostrarEstado(tipo, ok, mensaje) {
    const idMap = { FO: 'status-fo', FA: 'status-fa', DO: 'status-do', DA: 'status-da' };
    const cont = document.getElementById(idMap[tipo]);
    if (!cont) return;
    cont.classList.remove('success', 'error', 'show');
    cont.innerHTML = '';
    const span = document.createElement('span');
    span.className = 'badge';
    span.textContent = mensaje;
    cont.classList.add(ok ? 'success' : 'error');
    cont.appendChild(span);
    // mostrar
    requestAnimationFrame(() => cont.classList.add('show'));
    // ocultar a los 3s
    setTimeout(() => {
        cont.classList.remove('show');
        setTimeout(() => { cont.innerHTML = ''; }, 300);
    }, 3000);
}

async function guardarMatriz(tipo) {
    try {
        const values = recolectarCeldas(tipo);
        const res = await fetch('save_matriz_cruce.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ tipo, values })
        });
        const data = await res.json();
        if (!res.ok || !data.ok) throw new Error(data.error || 'Error al guardar');
        mostrarEstado(tipo, true, 'Guardado exitoso');
    } catch (e) {
        mostrarEstado(tipo, false, 'Fracaso al guardar');
    }
}
</script>
