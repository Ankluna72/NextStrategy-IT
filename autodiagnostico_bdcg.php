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

// DEFINICIÓN DE VARIABLES CRÍTICAS (Corrección del error)
$id_empresa_actual = $_SESSION['id_empresa_actual'];

$pageStyles = ['css/modules.css']; 
require_once 'includes/db_connection.php';
require_once 'includes/header.php';

$mensaje_exito = '';
$mensaje_error = '';

// -------------------------------------------------------------
// Autodiagnostico BCG - V2.1 Dinámico (Wizard + Diseño Original)
// -------------------------------------------------------------

// Inicializar valores
if (!isset($_SESSION['autobcg'])) {
    $_SESSION['autobcg'] = [];
}
$data = &$_SESSION['autobcg'];  

// Productos fijos (Base de datos soporta hasta 5)
$productos = ['Producto 1','Producto 2','Producto 3','Producto 4','Producto 5'];
$numProdMax = 5; 

// Años
$anios = [2012,2013,2014,2015,2016];        
$numYears = count($anios);

// Competidores
$numCompetidores = 9;

// --- PROCESAMIENTO DEL FORMULARIO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Guardar configuración de cantidad de productos en sesión para persistencia visual
    if (isset($_POST['num_productos_activos'])) {   
        $_SESSION['bcg_num_productos'] = intval($_POST['num_productos_activos']);
    }

    // Ventas
    $data['ventas'] = array_fill(0, $numProdMax, '');
    for ($i=0; $i<$numProdMax; $i++){
        $key = "ventas_{$i}";
        $data['ventas'][$i] = isset($_POST[$key]) ? $_POST[$key] : 0;
    }
    
    // Demanda (TCM)
    $data['demanda'] = array_fill(0, $numYears, array_fill(0, $numProdMax, ''));
    for ($y=0; $y<$numYears; $y++){
        for ($p=0; $p<$numProdMax; $p++){
            $key = "demanda_{$y}_{$p}";
            $data['demanda'][$y][$p] = isset($_POST[$key]) ? $_POST[$key] : 0;
        }
    }
    
    // Demanda Global Sector
    $aniosDemanda = [2012, 2013, 2014, 2015, 2016, 2017];
    $data['demanda_global'] = array_fill(0, count($aniosDemanda), array_fill(0, $numProdMax, ''));
    for ($y=0; $y<count($aniosDemanda); $y++){
        for ($p=0; $p<$numProdMax; $p++){
            $key = "demanda_global_{$y}_{$p}";
            $data['demanda_global'][$y][$p] = isset($_POST[$key]) ? $_POST[$key] : 0;
        }
    }
    
    // Competidores
    $data['competidores'] = array_fill(0, $numProdMax, array_fill(0, $numCompetidores, ''));
    for ($p=0; $p<$numProdMax; $p++){
        for ($c=0; $c<$numCompetidores; $c++){
            $key = "comp_{$p}_{$c}";
            $data['competidores'][$p][$c] = isset($_POST[$key]) ? $_POST[$key] : 0;
        }
    }
    
    // FODA
    $data['foda'] = [
        'fortaleza_3' => $_POST['fortaleza_3'] ?? '',
        'fortaleza_4' => $_POST['fortaleza_4'] ?? '',
        'debilidad_3' => $_POST['debilidad_3'] ?? '',
        'debilidad_4' => $_POST['debilidad_4'] ?? '',
    ];
    
    // Guardar en BD según la tabla específica
    if (isset($_POST['tabla_guardar'])) {
        if ($_POST['tabla_guardar'] === 'foda') {
            $resultado = guardarFODAEnBD($data['foda'], $mysqli);
            $mensaje_exito = $resultado ? "FODA guardado correctamente." : "";
            $mensaje_error = !$resultado ? "Error al guardar FODA." : "";
        } elseif ($_POST['tabla_guardar'] === 'demanda_global') {
            $resultado = guardarDemandaGlobalEnBD($data['demanda_global'], $mysqli);
            $mensaje_exito = $resultado ? "Demanda Global guardada correctamente." : "";
            $mensaje_error = !$resultado ? "Error al guardar Demanda Global." : "";
        } else {
            // Guardado genérico (Ventas, TCM, Competidores se guardan en sesión por ahora según lógica original)
            $mensaje_exito = "Datos de " . $_POST['tabla_guardar'] . " actualizados en sesión.";
        }
    }
    
    $data['updated_at'] = date('Y-m-d H:i:s');
} else {
    // Inicialización si no hay POST
    if (!isset($data['ventas'])) $data['ventas'] = array_fill(0, $numProdMax, '');
    if (!isset($data['demanda'])) $data['demanda'] = array_fill(0, $numYears, array_fill(0, $numProdMax, ''));
    $aniosDemanda = [2012, 2013, 2014, 2015, 2016, 2017];
    if (!isset($data['demanda_global'])) $data['demanda_global'] = array_fill(0, count($aniosDemanda), array_fill(0, $numProdMax, ''));
    if (!isset($data['competidores'])) $data['competidores'] = array_fill(0, $numProdMax, array_fill(0, $numCompetidores, ''));
    if (!isset($data['foda'])) $data['foda'] = ['fortaleza_3'=>'','fortaleza_4'=>'','debilidad_3'=>'','debilidad_4'=>''];
    
    // Cargar FODA de BD
    cargarFodaDeBD($mysqli, $id_empresa_actual, $data);
    
    // Cargar Demanda Global de BD
    cargarDemandaGlobalDeBD($mysqli, $id_empresa_actual, $data);
}

// Recuperar número de productos activos (por defecto 5)
$numProductosActivos = isset($_SESSION['bcg_num_productos']) ? $_SESSION['bcg_num_productos'] : 5;


// --- FUNCIONES DE BASE DE DATOS Y CÁLCULO ---

function cargarFodaDeBD($mysqli, $id_empresa, &$data) {
    $stmt = $mysqli->prepare("SELECT tipo, descripcion, posicion FROM foda WHERE id_empresa = ? AND origen = 'bcg'");
    $stmt->bind_param("i", $id_empresa);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        if ($row['tipo'] == 'fortaleza' && $row['posicion'] >= 3) {
            $data['foda']['fortaleza_' . $row['posicion']] = $row['descripcion'];
        } elseif ($row['tipo'] == 'debilidad' && $row['posicion'] >= 3) {
            $data['foda']['debilidad_' . $row['posicion']] = $row['descripcion'];
        }
    }
    $stmt->close();
}

function cargarDemandaGlobalDeBD($mysqli, $id_empresa, &$data) {
    $stmt = $mysqli->prepare("SELECT producto, anio_2012, anio_2013, anio_2014, anio_2015, anio_2016, anio_2017 FROM demanda_global_sector WHERE id_empresa = ?");
    $stmt->bind_param("i", $id_empresa);
    $stmt->execute();
    $res = $stmt->get_result();
    
    // Mapeo de nombre producto a índice 0-4
    $mapProd = ['Producto 1'=>0, 'Producto 2'=>1, 'Producto 3'=>2, 'Producto 4'=>3, 'Producto 5'=>4];
    
    while ($row = $res->fetch_assoc()) {
        if(isset($mapProd[$row['producto']])) {
            $idx = $mapProd[$row['producto']];
            $data['demanda_global'][0][$idx] = $row['anio_2012'];
            $data['demanda_global'][1][$idx] = $row['anio_2013'];
            $data['demanda_global'][2][$idx] = $row['anio_2014'];
            $data['demanda_global'][3][$idx] = $row['anio_2015'];
            $data['demanda_global'][4][$idx] = $row['anio_2016'];
            $data['demanda_global'][5][$idx] = $row['anio_2017'];
        }
    }
    $stmt->close();
}

function guardarFODAEnBD($fodaData, $mysqli) {
    $id_empresa = $_SESSION['id_empresa_actual'] ?? null;
    $id_usuario = $_SESSION['id_usuario'] ?? null;
    if (!$id_empresa) return false;
    
    try {
        $mysqli->begin_transaction();
        
        $stmt_del = $mysqli->prepare("DELETE FROM foda WHERE id_empresa = ? AND origen = 'bcg'");
        $stmt_del->bind_param("i", $id_empresa);
        $stmt_del->execute();
        $stmt_del->close();
        
        $stmt_ins = $mysqli->prepare("INSERT INTO foda (id_empresa, id_usuario, tipo, descripcion, origen, posicion) VALUES (?, ?, ?, ?, 'bcg', ?)");
        
        $items = [
            ['tipo'=>'fortaleza', 'pos'=>3, 'val'=>$fodaData['fortaleza_3']],
            ['tipo'=>'fortaleza', 'pos'=>4, 'val'=>$fodaData['fortaleza_4']],
            ['tipo'=>'debilidad', 'pos'=>3, 'val'=>$fodaData['debilidad_3']],
            ['tipo'=>'debilidad', 'pos'=>4, 'val'=>$fodaData['debilidad_4']]
        ];
        
        foreach ($items as $item) {
            if (!empty($item['val'])) {
                $stmt_ins->bind_param("iissi", $id_empresa, $id_usuario, $item['tipo'], $item['val'], $item['pos']);
                $stmt_ins->execute();
            }
        }
        $stmt_ins->close();
        $mysqli->commit();
        return true;
    } catch (Exception $e) {
        $mysqli->rollback();
        return false;
    }
}

function guardarDemandaGlobalEnBD($demandaData, $mysqli) {
    $id_empresa = $_SESSION['id_empresa_actual'] ?? null;
    if (!$id_empresa) return false;
    
    try {
        $mysqli->begin_transaction();
        $stmt = $mysqli->prepare("UPDATE demanda_global_sector SET anio_2012=?, anio_2013=?, anio_2014=?, anio_2015=?, anio_2016=?, anio_2017=? WHERE id_empresa=? AND producto=?");
        
        $productos = ['Producto 1','Producto 2','Producto 3','Producto 4','Producto 5'];
        
        for ($p = 0; $p < 5; $p++) {
            $vals = [];
            for ($y = 0; $y < 6; $y++) {
                $val = $demandaData[$y][$p];
                $vals[] = ($val === '' || $val === null) ? null : floatval($val);
            }
            $prodName = $productos[$p];
            $stmt->bind_param("ddddddis", $vals[0], $vals[1], $vals[2], $vals[3], $vals[4], $vals[5], $id_empresa, $prodName);
            $stmt->execute();
        }
        $stmt->close();
        $mysqli->commit();
        return true;
    } catch (Exception $e) {
        $mysqli->rollback();
        return false;
    }
}

function getProductoClass($index) { return 'producto-' . ($index + 1); }
function getProductoColor($index) { $c=['#18b36b','#0f2f46','#e74c3c','#f39c12','#9b59b6']; return $c[$index]??'#666'; }

// Cálculos previos PHP para renderizado inicial (Totales, etc)
$ventas_val = array_map(function($v){ return floatval($v); }, $data['ventas']);
$totalVentas = array_sum($ventas_val);
$porcVentas = [];
foreach($ventas_val as $v) $porcVentas[] = ($totalVentas>0)?($v/$totalVentas*100):0;

?>

<!-- Estilos adicionales para el Wizard -->
<style>
    /* Estilos del Wizard */
    .step-indicator {
        display: flex;
        justify-content: space-between;
        margin-bottom: 2rem;
        position: relative;
        padding: 0 20px;
    }
    .step-indicator::before {
        content: '';
        position: absolute;
        top: 15px;
        left: 20px;
        right: 20px;
        height: 3px;
        background: #e0e0e0;
        z-index: 0;
    }
    .step {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        background: #fff;
        border: 3px solid #e0e0e0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: #999;
        position: relative;
        z-index: 1;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .step.active {
        border-color: var(--brand-green);
        background: var(--brand-green);
        color: white;
        box-shadow: 0 0 0 4px rgba(24,179,107,0.2);
    }
    .step.completed {
        border-color: var(--brand-green);
        background: var(--brand-green);
        color: white;
    }
    .step-label {
        position: absolute;
        top: 45px;
        left: 50%;
        transform: translateX(-50%);
        font-size: 0.75rem;
        font-weight: 600;
        color: #999;
        white-space: nowrap;
    }
    .step.active .step-label, .step.completed .step-label {
        color: var(--brand-blue);
    }
    
    /* Ocultar pasos no activos */
    .step-content {
        display: none;
        animation: fadeIn 0.5s;
    }
    .step-content.active {
        display: block;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Ocultar columnas de productos inactivos */
    .prod-col-hidden {
        display: none !important;
    }
    
    .wizard-actions {
        display: flex;
        justify-content: space-between;
        margin-top: 2rem;
        padding-top: 1rem;
        border-top: 1px solid #eee;
    }

    /* Estilos originales de las tablas */
    #tablaVentas tr:hover { transform: translateY(-1px); box-shadow: 0 2px 8px rgba(0,0,0,.1); }
    #tablaVentas .venta-input:focus { border-color: var(--brand-green) !important; box-shadow: 0 0 0 0.2rem rgba(24,179,107,.25) !important; background: #ffffff !important; }
    
    /* Matriz BCG Visual Original */
    .product-bubble-bcg {
        position: absolute;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: white;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        transition: all 0.3s ease;
        z-index: 10;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        border: 3px solid rgba(255,255,255,0.3);
    }
    .product-bubble-bcg:hover { transform: scale(1.1); box-shadow: 0 4px 12px rgba(0,0,0,0.3); border-color: rgba(255,255,255,0.6); }
</style>

<div class="container mt-4">
    <div class="module-container">
        <div class="module-header">
            <h2 class="module-title">Autodiagnóstico BCG</h2>
        </div>

        <div class="module-content">
            <?php if (!empty($mensaje_exito)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($mensaje_exito); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Indicadores de Paso -->
            <div class="step-indicator">
                <div class="step active" onclick="goToStep(1)">1<span class="step-label">Configuración</span></div>
                <div class="step" onclick="goToStep(2)">2<span class="step-label">Ventas</span></div>
                <div class="step" onclick="goToStep(3)">3<span class="step-label">Mercado</span></div>
                <div class="step" onclick="goToStep(4)">4<span class="step-label">Competencia</span></div>
                <div class="step" onclick="goToStep(5)">5<span class="step-label">Resultados</span></div>
            </div>

            <form id="formBCG" method="post">
                <input type="hidden" name="num_productos_activos" id="input_num_productos" value="<?php echo $numProductosActivos; ?>">

                <!-- PASO 1: CONFIGURACIÓN -->
                <div class="step-content active" id="step-1">
                    <div class="explanation-box p-4">
                        <h4 style="color: var(--brand-blue);">Configuración Inicial</h4>
                        <p>Bienvenido al asistente de Matriz BCG. Para simplificar el proceso, seleccione cuántos productos o unidades de negocio desea analizar (entre 1 y 5).</p>
                        
                        <div class="my-4 text-center">
                            <label class="form-label fw-bold mb-3">Número de Productos a Analizar:</label>
                            <div class="btn-group" role="group">
                                <?php for($i=1; $i<=5; $i++): ?>
                                <input type="radio" class="btn-check" name="btnradio" id="btnradio<?php echo $i; ?>" autocomplete="off" 
                                       onclick="setNumProductos(<?php echo $i; ?>)" <?php echo ($i == $numProductosActivos) ? 'checked' : ''; ?>>
                                <label class="btn btn-outline-primary" for="btnradio<?php echo $i; ?>"><?php echo $i; ?></label>
                                <?php endfor; ?>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> El sistema adaptará todas las tablas para mostrar únicamente la cantidad de productos seleccionada.
                        </div>
                    </div>
                </div>

                <!-- PASO 2: VENTAS -->
                <div class="step-content" id="step-2">
                    <div class="explanation-box p-3 mb-4">
                        <h4 style="color: var(--brand-green); text-align: center; font-weight: 700; margin-bottom: 1.5rem;">PREVISIÓN DE VENTAS</h4>
                        <p>Ingrese las ventas por producto. Los cálculos se actualizan automáticamente al escribir.</p>

                        <div class="table-responsive">
                            <table class="table table-bordered" id="tablaVentas" style="background: linear-gradient(135deg, #f5faff 0%, #eef6fb 100%); border-radius: 0.75rem; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,.05);">
                                <thead style="background: linear-gradient(135deg, var(--brand-blue), var(--brand-dark)); color: white;">
                                    <tr>
                                        <th style="text-align: left; font-weight: 600; padding: 1rem;">PRODUCTOS</th>
                                        <th style="text-align: center; font-weight: 600; padding: 1rem;">VENTAS</th>
                                        <th style="text-align: center; font-weight: 600; padding: 1rem;">% S/ TOTAL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php for ($i=0;$i<$numProdMax;$i++): ?>
                                    <tr class="row-prod-<?php echo $i; ?>" style="transition: all 0.3s ease;">
                                        <td class="<?php echo getProductoClass($i); ?>" style="padding: 1rem; font-weight: 700; font-size: 1.1rem; color: inherit;"><?php echo $productos[$i]; ?></td>
                                        <td style="text-align: center; padding: 1rem; background: white;">
                                            <input type="text" name="ventas_<?php echo $i; ?>" class="form-control venta-input" value="<?php echo htmlspecialchars($data['ventas'][$i]); ?>" style="color: var(--brand-blue); text-align: center; font-weight: 600; border: 1px solid rgba(15,47,70,.2); border-radius: 0.5rem; padding: 0.75rem; transition: all 0.3s ease;" placeholder="0.00" oninput="this.value=this.value.replace(/[^0-9.]/g,'');">
                                        </td>
                                        <td style="text-align: center; padding: 1rem; font-weight: 600; background: white;" class="pct-venta">0.00%</td>
                                    </tr>
                                    <?php endfor; ?>
                                    <tr style="background: linear-gradient(135deg, var(--brand-green), var(--brand-green-600)); color: white; font-weight: 700;">
                                        <td style="padding: 1rem;"><strong>TOTAL</strong></td>
                                        <td style="text-align: center; padding: 1rem;"><strong id="totalVentas">0.00</strong></td>
                                        <td style="text-align: center; padding: 1rem;"><strong id="totalPorcentaje">100.00%</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <button type="button" class="btn btn-save" onclick="guardarTabla('ventas')">Guardar Ventas</button>
                        </div>
                    </div>
                </div>

                <!-- PASO 3: MERCADO -->
                <div class="step-content" id="step-3">
                    <!-- TCM -->
                    <div class="explanation-box p-3 mb-4">
                        <h4 style="color: var(--brand-blue); text-align: center; font-weight: 700; margin-bottom: 1.5rem;">TASAS DE CRECIMIENTO (TCM)</h4>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="tablaDemanda" style="background: linear-gradient(135deg, #f5faff 0%, #eef6fb 100%); border-radius: 0.75rem; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,.05);">
                                <thead>
                                    <tr>
                                        <th style="background: linear-gradient(135deg, var(--brand-blue), var(--brand-dark)); color: white; font-weight: 600; padding: 1rem; text-align: left;">PERIODOS</th>
                                        <?php for ($i=0;$i<$numProdMax;$i++): ?>
                                        <th class="col-prod-<?php echo $i; ?> <?php echo getProductoClass($i); ?>" style="font-weight: 700; padding: 1rem; text-align: center; color: inherit;"><?php echo $productos[$i]; ?></th>
                                        <?php endfor; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php for ($y=0;$y<$numYears;$y++): $periodo = ($anios[$y])."-".($anios[$y]+1); ?>
                                    <tr>
                                        <td style="padding: 1rem; font-weight: 600; background: rgba(15,47,70,.05);"><?php echo $periodo; ?></td>
                                        <?php for ($p=0;$p<$numProdMax;$p++): ?>
                                        <td class="col-prod-<?php echo $p; ?>" style="padding: 0.5rem; background: white;">
                                            <input type="text" name="demanda_<?php echo $y; ?>_<?php echo $p; ?>" class="form-control demanda-input" value="<?php echo htmlspecialchars($data['demanda'][$y][$p]); ?>" style="border: 1px solid rgba(15,47,70,.2); border-radius: 0.5rem; padding: 0.5rem; text-align: center; color: var(--brand-blue);" placeholder="0.00" oninput="this.value=this.value.replace(/[^0-9.]/g,'');">
                                        </td>
                                        <?php endfor; ?>
                                    </tr>
                                    <?php endfor; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <button type="button" class="btn btn-save" onclick="guardarTabla('tcm')">Guardar TCM</button>
                        </div>
                    </div>

                    <!-- Demanda Global -->
                    <div class="explanation-box p-3 mb-4">
                        <h4 style="color: var(--brand-blue); text-align: center; font-weight: 700; margin-bottom: 1.5rem;">DEMANDA GLOBAL SECTOR</h4>
                        <div class="table-responsive">
                            <table class="table table-bordered" style="background: linear-gradient(135deg, #f5faff 0%, #eef6fb 100%); border-radius: 0.75rem; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,.05);">
                                <thead>
                                    <tr>
                                        <th style="background: linear-gradient(135deg, var(--brand-blue), var(--brand-dark)); color: white; font-weight: 600; padding: 1rem; text-align: left;">AÑO</th>
                                        <?php for ($i=0;$i<$numProdMax;$i++): ?>
                                        <th class="col-prod-<?php echo $i; ?> <?php echo getProductoClass($i); ?>" style="font-weight: 700; padding: 1rem; text-align: center; color: inherit;"><?php echo $productos[$i]; ?></th>
                                        <?php endfor; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php for ($y=0;$y<count($aniosDemanda);$y++): ?>
                                    <tr>
                                        <td style="padding: 1rem; font-weight: 600; background: rgba(15,47,70,.05);"><?php echo $aniosDemanda[$y]; ?></td>
                                        <?php for ($p=0;$p<$numProdMax;$p++): ?>
                                        <td class="col-prod-<?php echo $p; ?>" style="padding: 0.5rem; background: white;">
                                            <input type="text" name="demanda_global_<?php echo $y; ?>_<?php echo $p; ?>" class="form-control demanda-global-input" value="<?php echo htmlspecialchars($data['demanda_global'][$y][$p] ?? ''); ?>" style="border: 1px solid rgba(15,47,70,.2); border-radius: 0.5rem; padding: 0.5rem; text-align: center; color: var(--brand-blue);" placeholder="0.00">
                                        </td>
                                        <?php endfor; ?>
                                    </tr>
                                    <?php endfor; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <button type="button" class="btn btn-save" onclick="guardarTabla('demanda_global')">Guardar Demanda Global</button>
                        </div>
                    </div>
                </div>

                <!-- PASO 4: COMPETENCIA -->
                <div class="step-content" id="step-4">
                    <div class="explanation-box p-3 mb-4">
                        <h4 style="color: var(--brand-green); text-align: center; font-weight: 700; margin-bottom: 1.5rem;">COMPETIDORES</h4>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="tablaCompetidores" style="background: linear-gradient(135deg, #f5faff 0%, #eef6fb 100%); border-radius: 0.75rem; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,.05);">
                                <thead>
                                    <tr>
                                        <th style="background: linear-gradient(135deg, var(--brand-blue), var(--brand-dark)); color: white; font-weight: 600; padding: 1rem; text-align: center;">COMPETIDOR</th>
                                        <?php for ($i=0;$i<$numProdMax;$i++): ?>
                                        <th class="col-prod-<?php echo $i; ?> <?php echo getProductoClass($i); ?>" style="font-weight: 700; padding: 1rem; text-align: center; color: inherit;"><?php echo $productos[$i]; ?></th>
                                        <?php endfor; ?>
                                    </tr>
                                    <tr>
                                        <th style="background: linear-gradient(135deg, var(--brand-blue), var(--brand-dark)); color: white; font-weight: 600; padding: 1rem; text-align: center;">EMPRESA (Ventas)</th>
                                        <?php for ($i=0;$i<$numProdMax;$i++): ?>
                                        <th class="col-prod-<?php echo $i; ?> <?php echo getProductoClass($i); ?> text-center empresa-venta" style="font-weight: 700; padding: 1rem; text-align: center; color: inherit;" data-prod="<?php echo $i; ?>">0</th>
                                        <?php endfor; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php for ($c=0;$c<$numCompetidores;$c++): ?>
                                    <tr>
                                        <td style="padding: 1rem; font-weight: 600; background: rgba(15,47,70,.05);">CP<?php echo $c+1; ?></td>
                                        <?php for ($p=0;$p<$numProdMax;$p++): ?>
                                        <td class="col-prod-<?php echo $p; ?>" style="padding: 0.5rem; background: white;">
                                            <input type="text" name="comp_<?php echo $p; ?>_<?php echo $c; ?>" class="form-control comp-input" value="<?php echo htmlspecialchars($data['competidores'][$p][$c]); ?>" placeholder="0.00" style="border: 1px solid rgba(15,47,70,.2); border-radius: 0.5rem; padding: 0.5rem; text-align: center; color: var(--brand-blue);" oninput="this.value=this.value.replace(/[^0-9.]/g,'');">
                                        </td>
                                        <?php endfor; ?>
                                    </tr>
                                    <?php endfor; ?>
                                    <tr style="background: linear-gradient(135deg, var(--brand-green), var(--brand-green-600)); color: white; font-weight: 700;">
                                        <td style="padding: 1rem;"><strong>MAYOR</strong></td>
                                        <?php for ($p=0;$p<$numProdMax;$p++): ?>
                                        <td class="col-prod-<?php echo $p; ?> text-center mayor-comp" style="padding: 1rem;" data-prod="<?php echo $p; ?>">0.00</td>
                                        <?php endfor; ?>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <button type="button" class="btn btn-save" onclick="guardarTabla('competidores')">Guardar Competidores</button>
                        </div>
                    </div>
                </div>

                <!-- PASO 5: RESULTADOS Y FODA -->
                <div class="step-content" id="step-5">
                    <!-- Resumen Indicadores -->
                    <div class="explanation-box p-3 mb-4">
                        <h4 style="color: var(--brand-blue); text-align: center; font-weight: 700; margin-bottom: 1.5rem;">RESUMEN DE INDICADORES</h4>
                        <div class="table-responsive">
                            <table class="table table-bordered text-center" id="tablaResultados">
                                <thead>
                                    <tr>
                                        <th style="background: linear-gradient(135deg, var(--brand-blue), var(--brand-dark)); color: white; font-weight: 600; padding: 1rem;">BCG</th>
                                        <?php for ($i=0;$i<$numProdMax;$i++): ?>
                                        <th class="col-prod-<?php echo $i; ?> <?php echo getProductoClass($i); ?>" style="font-weight: 700; padding: 1rem; text-align: center; color: inherit;"><?php echo $productos[$i]; ?></th>
                                        <?php endfor; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><strong>TCM (%)</strong></td>
                                        <?php for ($p=0;$p<$numProdMax;$p++): ?><td class="col-prod-<?php echo $p; ?> res-tcm">0.00 %</td><?php endfor; ?>
                                    </tr>
                                    <tr>
                                        <td><strong>PRM</strong></td>
                                        <?php for ($p=0;$p<$numProdMax;$p++): ?><td class="col-prod-<?php echo $p; ?> res-prm">0.000</td><?php endfor; ?>
                                    </tr>
                                    <tr>
                                        <td><strong>% Ventas</strong></td>
                                        <?php for ($p=0;$p<$numProdMax;$p++): ?><td class="col-prod-<?php echo $p; ?> res-porc">0.00 %</td><?php endfor; ?>
                                    </tr>
                                    <tr>
                                        <td><strong>Clasificación</strong></td>
                                        <?php for ($p=0;$p<$numProdMax;$p++): ?><td class="col-prod-<?php echo $p; ?> res-clasif fw-bold">-</td><?php endfor; ?>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Matriz BCG Visual Original -->
                    <div class="explanation-box p-4 mb-5" style="background: linear-gradient(135deg, #ffffff 0%, #f0f8ff 100%); box-shadow: 0 8px 30px rgba(0,0,0,.15); border-radius: 1.2rem; border: 3px solid #18b36b; margin-top: 3rem; margin-bottom: 3rem;">
                        <div style="text-align: center; background: linear-gradient(135deg, #0f2f46, #18b36b); padding: 1.5rem; margin: -1rem -1rem 2rem -1rem; border-radius: 1rem 1rem 0 0;">
                            <h3 style="color: white; font-weight: 800; margin: 0; font-size: 1.8rem; text-transform: uppercase; letter-spacing: 1px;">
                                📊 MATRIZ BCG
                            </h3>
                        </div>
                        
                        <div style="display: flex; justify-content: center; margin: 2rem auto; padding: 2rem; background: white; border-radius: 1rem; box-shadow: inset 0 2px 10px rgba(0,0,0,.05);">
                            <div id="bcg-matrix" style="position: relative; width: 600px; height: 400px; border: 4px solid #0f2f46; background: linear-gradient(to right, #f8f9fa 50%, #e9ecef 50%), linear-gradient(to bottom, #f8f9fa 50%, #e9ecef 50%); background-size: 100% 100%; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,.1);">
                                <!-- Líneas divisorias -->
                                <div style="position: absolute; left: 50%; top: 0; width: 3px; height: 100%; background-color: #0f2f46; z-index: 1;"></div>
                                <div style="position: absolute; left: 0; top: 50%; width: 100%; height: 3px; background-color: #0f2f46; z-index: 1;"></div>
                                
                                <!-- Etiquetas de cuadrantes -->
                                <div style="position: absolute; top: 10px; left: 10px; background: rgba(139,69,19,0.1); padding: 5px 10px; border-radius: 6px; font-weight: bold; font-size: 11px; color: #8B4513;">INTERROGANTES</div>
                                <div style="position: absolute; top: 10px; right: 10px; background: rgba(65,105,225,0.1); padding: 5px 10px; border-radius: 6px; font-weight: bold; font-size: 11px; color: #4169E1;">ESTRELLAS</div>
                                <div style="position: absolute; bottom: 10px; right: 10px; background: rgba(34,139,34,0.1); padding: 5px 10px; border-radius: 6px; font-weight: bold; font-size: 11px; color: #228B22;">VACAS LECHERAS</div>
                                <div style="position: absolute; bottom: 10px; left: 10px; background: rgba(105,105,105,0.1); padding: 5px 10px; border-radius: 6px; font-weight: bold; font-size: 11px; color: #696969;">PERROS</div>
                                
                                <!-- Etiquetas de los ejes -->
                                <div style="position: absolute; bottom: -35px; left: 50%; transform: translateX(-50%); font-weight: bold; font-size: 13px; color: #0f2f46; background: white; padding: 3px 10px; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,.1);">PRM = 1.0</div>
                                <div style="position: absolute; left: -100px; top: 50%; transform: translateY(-50%) rotate(-90deg); font-weight: bold; font-size: 13px; color: #0f2f46; white-space: nowrap;">TCM Promedio</div>
                                
                                <!-- Iconos en los cuadrantes -->
                                <div style="position: absolute; top: 35px; left: 35px; font-size: 40px; opacity: 0.3;">❓</div>
                                <div style="position: absolute; top: 35px; right: 35px; font-size: 40px; opacity: 0.3;">⭐</div>
                                <div style="position: absolute; bottom: 35px; right: 35px; font-size: 40px; opacity: 0.3;">🐄</div>
                                <div style="position: absolute; bottom: 35px; left: 35px; font-size: 40px; opacity: 0.3;">🐕</div>
                                
                                <!-- Burbujas de productos (Se posicionan con JS) -->
                                <?php for ($i=0; $i<$numProdMax; $i++): ?>
                                <div id="bubble-<?php echo $i; ?>" class="product-bubble-bcg" 
                                     style="position: absolute; width: 60px; height: 60px; 
                                            background-color: <?php echo getProductoColor($i); ?>; 
                                            border-radius: 50%; display: none; align-items: center; 
                                            justify-content: center; font-weight: 800; font-size: 12px; color: white; 
                                            text-shadow: 1px 1px 2px rgba(0,0,0,0.5); 
                                            box-shadow: 0 4px 15px rgba(0,0,0,0.3); border: 3px solid rgba(255,255,255,0.5);">
                                    0%
                                </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>

                    <!-- FODA Final -->
                    <div class="explanation-box p-3 mb-4">
                        <p>Complete las fortalezas y debilidades más significativas identificadas en su análisis BCG.</p>
                        <div class="table-responsive">
                            <table class="table table-bordered" style="background: linear-gradient(135deg, #f5faff 0%, #eef6fb 100%); border-radius: 0.75rem; overflow: hidden;">
                                <thead>
                                    <tr>
                                        <th colspan="2" style="background: #D2B48C; color: #000; font-weight: 700; padding: 1rem; text-align: center;">FORTALEZAS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td style="background: #D2B48C; font-weight: 600; padding: 1rem; width: 15%;">F3:</td>
                                        <td style="background: white; padding: 1rem;">
                                            <input type="text" name="fortaleza_3" class="form-control" value="<?php echo htmlspecialchars($data['foda']['fortaleza_3']); ?>" placeholder="Ingrese fortaleza 3" style="border: 1px solid rgba(15,47,70,.2); border-radius: 0.5rem; padding: 0.75rem;">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="background: #D2B48C; font-weight: 600; padding: 1rem;">F4:</td>
                                        <td style="background: white; padding: 1rem;">
                                            <input type="text" name="fortaleza_4" class="form-control" value="<?php echo htmlspecialchars($data['foda']['fortaleza_4']); ?>" placeholder="Ingrese fortaleza 4" style="border: 1px solid rgba(15,47,70,.2); border-radius: 0.5rem; padding: 0.75rem;">
                                        </td>
                                    </tr>
                                </tbody>
                                <thead>
                                    <tr>
                                        <th colspan="2" style="background: #90EE90; color: #000; font-weight: 700; padding: 1rem; text-align: center;">DEBILIDADES</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td style="background: #90EE90; font-weight: 600; padding: 1rem;">D3:</td>
                                        <td style="background: white; padding: 1rem;">
                                            <input type="text" name="debilidad_3" class="form-control" value="<?php echo htmlspecialchars($data['foda']['debilidad_3']); ?>" placeholder="Ingrese debilidad 3" style="border: 1px solid rgba(15,47,70,.2); border-radius: 0.5rem; padding: 0.75rem;">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="background: #90EE90; font-weight: 600; padding: 1rem;">D4:</td>
                                        <td style="background: white; padding: 1rem;">
                                            <input type="text" name="debilidad_4" class="form-control" value="<?php echo htmlspecialchars($data['foda']['debilidad_4']); ?>" placeholder="Ingrese debilidad 4" style="border: 1px solid rgba(15,47,70,.2); border-radius: 0.5rem; padding: 0.75rem;">
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <button type="button" class="btn btn-save" onclick="guardarTabla('foda')">Guardar FODA</button>
                        </div>
                    </div>
                </div>

                <!-- Botones de Navegación del Wizard -->
                <div class="wizard-actions">
                    <button type="button" class="btn btn-outline-secondary" id="btnPrev" onclick="prevStep()" disabled>
                        <i class="fas fa-arrow-left"></i> Anterior
                    </button>
                    <div class="d-flex gap-2">
                        <a href="dashboard.php" class="btn btn-nav-outline">Salir</a>
                        <button type="button" class="btn btn-brand" id="btnNext" onclick="nextStep()">
                            Siguiente <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>

<style>
    /* Estilos del Wizard */
    .step-indicator {
        display: flex;
        justify-content: space-between;
        margin-bottom: 2rem;
        position: relative;
        padding: 0 20px;
    }
    .step-indicator::before {
        content: '';
        position: absolute;
        top: 15px;
        left: 20px;
        right: 20px;
        height: 3px;
        background: #e0e0e0;
        z-index: 0;
    }
    .step {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        background: #fff;
        border: 3px solid #e0e0e0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: #999;
        position: relative;
        z-index: 1;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .step.active {
        border-color: var(--brand-green);
        background: var(--brand-green);
        color: white;
        box-shadow: 0 0 0 4px rgba(24,179,107,0.2);
    }
    .step.completed {
        border-color: var(--brand-green);
        background: var(--brand-green);
        color: white;
    }
    .step-label {
        position: absolute;
        top: 45px;
        left: 50%;
        transform: translateX(-50%);
        font-size: 0.75rem;
        font-weight: 600;
        color: #999;
        white-space: nowrap;
    }
    .step.active .step-label, .step.completed .step-label {
        color: var(--brand-blue);
    }
    
    /* Ocultar pasos no activos */
    .step-content {
        display: none;
        animation: fadeIn 0.5s;
    }
    .step-content.active {
        display: block;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Ocultar columnas de productos inactivos */
    .prod-col-hidden {
        display: none !important;
    }
    
    .wizard-actions {
        display: flex;
        justify-content: space-between;
        margin-top: 2rem;
        padding-top: 1rem;
        border-top: 1px solid #eee;
    }

    /* Efectos Matriz Visual */
    .product-bubble-bcg:hover {
        transform: scale(1.1);
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        border-color: rgba(255,255,255,0.8);
    }
</style>

<script>
// Variables globales
let currentStep = 1;
const totalSteps = 5;
let numProductos = <?php echo $numProductosActivos; ?>;

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    updateUIProducts();
    calcularTodo();
    showStep(1);
});

// Navegación del Wizard
function showStep(step) {
    if (currentStep === 1 && step > 1 && numProductos === 0) {
        alert("Por favor seleccione el número de productos.");
        return;
    }

    document.querySelectorAll('.step-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.step').forEach(el => el.classList.remove('active'));
    document.getElementById('step-' + step).classList.add('active');
    
    for(let i=1; i<=totalSteps; i++) {
        const ind = document.querySelector(`.step:nth-child(${i})`);
        if(i < step) {
            ind.classList.add('completed');
            ind.classList.remove('active');
        } else if (i === step) {
            ind.classList.add('active');
            ind.classList.remove('completed');
        } else {
            ind.classList.remove('active', 'completed');
        }
    }

    currentStep = step;
    
    document.getElementById('btnPrev').disabled = (step === 1);
    document.getElementById('btnNext').innerHTML = (step === totalSteps) ? 'Finalizar <i class="fas fa-check"></i>' : 'Siguiente <i class="fas fa-arrow-right"></i>';
    
    if(step === 5) {
        calcularTodo();
        document.getElementById('btnNext').onclick = function() { window.location.href = 'porter_5fuerzas.php'; };
    } else {
        document.getElementById('btnNext').onclick = function() { nextStep(); };
    }
}

function nextStep() {
    if(currentStep < totalSteps) showStep(currentStep + 1);
}

function prevStep() {
    if(currentStep > 1) showStep(currentStep - 1);
}

function goToStep(step) {
    showStep(step);
}

// Configuración de Productos
function setNumProductos(n) {
    numProductos = n;
    document.getElementById('input_num_productos').value = n;
    updateUIProducts();
    calcularTodo();
}

function updateUIProducts() {
    for (let i = 0; i < 5; i++) {
        const show = i < numProductos;
        const display = show ? '' : 'none';
        
        const rowsVenta = document.querySelectorAll(`.row-prod-${i}`);
        rowsVenta.forEach(r => r.style.display = display);
        
        const cols = document.querySelectorAll(`.col-prod-${i}`);
        cols.forEach(c => {
            if(show) c.classList.remove('prod-col-hidden');
            else c.classList.add('prod-col-hidden');
        });
        
        const bubble = document.getElementById(`bubble-${i}`);
        if(bubble) bubble.style.display = display;
    }
}

// Funciones de formato
function fmt(n, dec=2) { return Number.isFinite(n) ? n.toFixed(dec) : '0.00'; }

// Lógica de Cálculo
function calcularTodo() {
    const numYears = <?php echo $numYears; ?>;
    const numComp = <?php echo $numCompetidores; ?>;
    
    // 1. Ventas
    let totalVentas = 0;
    let ventas = [];
    for(let i=0; i<5; i++) {
        const val = parseFloat(document.querySelector(`input[name="ventas_${i}"]`)?.value || 0);
        ventas[i] = val;
        if(i < numProductos) totalVentas += val;
    }
    document.getElementById('totalVentas').innerText = fmt(totalVentas, 2);
    
    document.querySelectorAll('.pct-venta').forEach((el, i) => {
        if(i < 5) {
            const pct = totalVentas > 0 ? (ventas[i] / totalVentas * 100) : 0;
            el.innerText = fmt(pct, 2) + '%';
            const resPorc = document.querySelector(`.res-porc.col-prod-${i}`);
            if(resPorc) resPorc.innerText = fmt(pct, 2) + '%';
        }
    });
    
    document.querySelectorAll('.empresa-venta').forEach((el) => {
        const idx = el.dataset.prod;
        el.innerText = ventas[idx] || 0;
    });

    // 2. Competidores y PRM
    let maxCompetidores = [];
    let prms = [];
    for(let p=0; p<5; p++) {
        let max = 0;
        for(let c=0; c<numComp; c++) {
            const val = parseFloat(document.querySelector(`input[name="comp_${p}_${c}"]`)?.value || 0);
            if(val > max) max = val;
        }
        maxCompetidores[p] = max;
        document.querySelector(`.mayor-comp[data-prod="${p}"]`).innerText = fmt(max, 2);
        
        // PRM: Si Mayor=0 -> 0. Si Venta/Mayor > 2 -> 2. Sino ratio.
        let prm = 0;
        if (max === 0) {
            prm = 0;
        } else {
            const ratio = ventas[p] / max;
            prm = ratio > 2 ? 2 : ratio;
        }
        prms[p] = prm;
        
        const resPrm = document.querySelector(`.res-prm.col-prod-${p}`);
        if(resPrm) resPrm.innerText = fmt(prm, 3);
    }
    
    // 3. TCM
    let tcms = [];
    for(let p=0; p<5; p++) {
        let demandas = [];
        for(let y=0; y<numYears; y++) {
            demandas.push(parseFloat(document.querySelector(`input[name="demanda_${y}_${p}"]`)?.value || 0));
        }
        
        let suma = demandas.reduce((a,b)=>a+b,0);
        let promedio = demandas.length > 0 ? suma/demandas.length : 0;
        // Si promedio > 20, tope 20
        let tcm = promedio > 20 ? 20 : promedio;
        tcms[p] = tcm;
        
        const resTcm = document.querySelector(`.res-tcm.col-prod-${p}`);
        if(resTcm) resTcm.innerText = fmt(tcm, 2) + '%';
    }
    
    // 4. Clasificación y Gráfico
    const avgTCM = tcms.slice(0, numProductos).reduce((a,b)=>a+b,0) / (numProductos || 1);
    const matrixWidth = 600;
    const matrixHeight = 400;
    const maxVentas = Math.max(...ventas.slice(0, numProductos), 1); // Evitar división por cero

    for(let p=0; p<5; p++) {
        if(p >= numProductos) continue;
        
        const tcm = tcms[p];
        const prm = prms[p];
        let label = '';
        
        if(tcm > avgTCM && prm >= 1) label = 'Estrella ⭐';
        else if(tcm > avgTCM && prm < 1) label = 'Incógnita ❓';
        else if(tcm <= avgTCM && prm >= 1) label = 'Vaca 🐄';
        else label = 'Perro 🐕';
        
        const resClasif = document.querySelector(`.res-clasif.col-prod-${p}`);
        if(resClasif) resClasif.innerText = label;
        
        // Posicionar Burbuja
        const bubble = document.getElementById(`bubble-${p}`);
        if(bubble) {
            // Tamaño de burbuja (50px a 120px)
            const minSize = 50;
            const maxSize = 120;
            const bubbleSize = minSize + ((ventas[p] / maxVentas) * (maxSize - minSize));
            
            const margenX = 40;
            const anchoUtil = matrixWidth - (2 * margenX) - bubbleSize;
                
            const xmin = 0;
            const xmax = 2;
            const centerPRM = 1;

            // Factor: negativo = izquierda / positivo = derecha
            const factorX = (prm - centerPRM) / (xmax - xmin);

            // Convertir a pixeles desde el centro
            posX = (matrixWidth / 2) + (factorX * ((matrixWidth / 2) - 60)) - bubbleSize/2;


            // ------ Y (TCM) - centro en promedio ------
            const minY = Math.min(...tcms);
            const maxY = Math.max(...tcms);
            const centerTCM = avgTCM;

            // Factor: positivo = arriba / negativo = abajo
            const factorY = (tcm - centerTCM) / (maxY - minY);

            posY = (matrixHeight / 2) - (factorY * ((matrixHeight / 2) - 60)) - bubbleSize/2;


            // ------ Límites ------
            posX = Math.max(10, Math.min(posX, matrixWidth - bubbleSize - 10));
            posY = Math.max(10, Math.min(posY, matrixHeight - bubbleSize - 10));

            
            bubble.style.left = posX + 'px';
            bubble.style.top = posY + 'px';
            bubble.style.width = bubbleSize + 'px';
            bubble.style.height = bubbleSize + 'px';
            
            // Porcentaje dentro de la burbuja
            const pct = totalVentas > 0 ? (ventas[p] / totalVentas * 100) : 0;
            bubble.innerText = fmt(pct, 1) + '%';
            
            // Ajuste fuente
            const fontSize = Math.max(10, Math.min(16, bubbleSize / 5));
            bubble.style.fontSize = fontSize + 'px';
        }
    }
}

document.querySelectorAll('input').forEach(inp => {
    inp.addEventListener('input', calcularTodo);
});

function guardarTabla(tabla) {
    const form = document.getElementById('formBCG');
    const formData = new FormData(form);
    formData.append('tabla_guardar', tabla);
    
    fetch('autodiagnostico_bdcg.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.text())
    .then(() => {
        const btn = event.target;
        const orgText = btn.innerText;
        btn.innerText = '¡Guardado!';
        btn.classList.replace('btn-outline-success', 'btn-success');
        btn.classList.replace('btn-save', 'btn-success'); // Para el btn principal
        setTimeout(() => {
            btn.innerText = orgText;
            btn.classList.replace('btn-success', 'btn-outline-success');
            btn.classList.replace('btn-success', 'btn-save');
        }, 2000);
    });
}
</script>

<?php
require_once 'includes/footer.php';
$mysqli->close();
?>