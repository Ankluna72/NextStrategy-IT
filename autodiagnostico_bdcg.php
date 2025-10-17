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

// -------------------------------------------------------------
// Autodiagnostico BCG - editable, cálculos en JS y en PHP al guardar
// Estructura basada en la descripción del usuario (5 productos)
// Los datos se pueden editar en la tabla y calcular en el navegador
// o guardar en sesión para persistencia temporal.
// -------------------------------------------------------------

// Inicializar valores (por defecto 0 o provenientes de POST / SESSION)
if (!isset($_SESSION['autobcg'])) {
    $_SESSION['autobcg'] = [];
}
$data = &$_SESSION['autobcg'];

// Productos (fijos)
$productos = ['Producto 1','Producto 2','Producto 3','Producto 4','Producto 5'];
$numProd = count($productos);

// Años para la tabla de demanda
$anios = [2012,2013,2014,2015,2016];
$numYears = count($anios);

// Competidores: 9 filas + 'Mayor' calculado
$numCompetidores = 9;

// Si hay POST (guardar) tomar valores; si no, usar lo almacenado o 0
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ventas
    $data['ventas'] = array_map('floatval', $_POST['ventas'] ?? array_fill(0,$numProd,0));
    // Demanda por año -> esperamos campos demanda_year_product e.g. demanda_0_0
    $data['demanda'] = array_fill(0,$numYears, array_fill(0,$numProd,0.0));
    for ($y=0;$y<$numYears;$y++){
        for ($p=0;$p<$numProd;$p++){
            $key = "demanda_{$y}_{$p}";
            $data['demanda'][$y][$p] = isset($_POST[$key])? floatval($_POST[$key]) : 0.0;
        }
    }
    // Competidores
    $data['competidores'] = array_fill(0,$numProd, array_fill(0,$numCompetidores,0.0));
    for ($p=0;$p<$numProd;$p++){
        for ($c=0;$c<$numCompetidores;$c++){
            $key = "comp_{$p}_{$c}";
            $data['competidores'][$p][$c] = isset($_POST[$key])? floatval($_POST[$key]) : 0.0;
        }
    }
    // Guardar timestamp
    $data['updated_at'] = date('Y-m-d H:i:s');
} else {
    // Inicializar si no existe
    if (!isset($data['ventas'])) $data['ventas'] = array_fill(0,$numProd,0.0);
    if (!isset($data['demanda'])) $data['demanda'] = array_fill(0,$numYears, array_fill(0,$numProd,0.0));
    if (!isset($data['competidores'])) $data['competidores'] = array_fill(0,$numProd, array_fill(0,$numCompetidores,0.0));
}

// Funciones de cálculo en PHP (para mostrar resultados al guardar)
function calc_totales_y_porcentajes($ventas){
    $total = array_sum($ventas);
    $porc = [];
    foreach ($ventas as $v) $porc[] = ($total>0)?($v/$total*100):0.0;
    return [$total,$porc];
}

function calc_prm_php($ventas, $competidores){
    // PRM por producto = ventas_producto / max(ventas_competidor) o referencia
    $max_global = (count($ventas)>0)? max($ventas) : 0.0;
    $prm = [];
    foreach ($ventas as $i=>$v){
        $maxcomp = 0.0;
        if (isset($competidores[$i]) && count($competidores[$i])>0) {
            $maxcomp = max($competidores[$i]);
        }
        $ref = ($maxcomp>0)? $maxcomp : ($max_global>0? $max_global : 1.0);
        $prm[] = ($ref>0)? ($v/$ref) : 0.0;
    }
    return $prm;
}

function calc_tcm_php($demanda, $anios){
    // Calculamos CAGR entre primer y ultimo año: (last/first)^(1/(n-1))-1
    $nYears = count($anios);
    $numProd = count($demanda[0]);
    $tcms = array_fill(0,$numProd,0.0);
    for ($p=0;$p<$numProd;$p++){
        $first = floatval($demanda[0][$p]);
        $last = floatval($demanda[$nYears-1][$p]);
        if ($first>0 && $last>0 && $nYears>1){
            $tcms[$p] = pow(($last/$first), 1/($nYears-1)) - 1.0;
        } else {
            // si falta info, intentar promedio de tasas periodicas
            $rates = [];
            for ($y=0;$y<$nYears-1;$y++){
                $prev = floatval($demanda[$y][$p]);
                $nxt = floatval($demanda[$y+1][$p]);
                if ($prev>0) $rates[] = ($nxt/$prev)-1.0;
            }
            if (count($rates)>0) $tcms[$p] = array_sum($rates)/count($rates);
            else $tcms[$p] = 0.0;
        }
    }
    return $tcms;
}

list($totalVentas,$porcVentas) = calc_totales_y_porcentajes($data['ventas']);
$prm_php = calc_prm_php($data['ventas'],$data['competidores']);
$tcm_php = calc_tcm_php($data['demanda'],$anios);
$growth_promedio = (count($tcm_php)>0)? array_sum($tcm_php)/count($tcm_php) : 0.0;

// Clasificación BCG según reglas
$clasif_php = [];
for ($i=0;$i<$numProd;$i++){
    $g = $tcm_php[$i];
    $r = $prm_php[$i];
    if ($g > $growth_promedio && $r >= 1) $clasif_php[$i] = 'Estrella';
    elseif ($g > $growth_promedio && $r < 1) $clasif_php[$i] = 'Incógnita';
    elseif ($g <= $growth_promedio && $r >= 1) $clasif_php[$i] = 'Vaca';
    else $clasif_php[$i] = 'Perro';
}

// ----------------------
// HTML / Formulario
// ----------------------
?>

<div class="container mt-4">
    <div class="module-container">
        <div class="module-header">
            <h2 class="module-title">Autodiagnóstico BCG - Interactivo</h2>
        </div>

        <div class="module-content">
            <form id="formBCG" method="post">
                <!-- Tabla 1: Previsión de Ventas -->
                <div class="explanation-box p-3 mb-4">
                    <h4>Tabla 1: Previsión de Ventas</h4>
                    <p>Ingrese las ventas por producto. Pulse <strong>Calcular</strong> para ver los resultados en las tablas inferiores o <strong>Guardar</strong> para almacenar en sesión.</p>

                    <div class="table-responsive">
                        <table class="table table-bordered" id="tablaVentas">
                            <thead class="table-light text-center">
                                <tr>
                                    <th>Producto</th>
                                    <th>Ventas</th>
                                    <th>% s/ TOTAL</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php for ($i=0;$i<$numProd;$i++): ?>
                                <tr>
                                    <td><?php echo $productos[$i]; ?></td>
                                    <td><input type="number" step="0.01" min="0" name="ventas[]" class="form-control venta-input" value="<?php echo htmlspecialchars($data['ventas'][$i]); ?>"></td>
                                    <td class="text-end pct-venta">0.00 %</td>
                                </tr>
                                <?php endfor; ?>
                                <tr class="table-secondary">
                                    <td><strong>TOTAL</strong></td>
                                    <td class="text-end"><strong id="totalVentas"><?php echo number_format($totalVentas,2); ?></strong></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tabla 2: TCM por periodos -->
                <div class="explanation-box p-3 mb-4">
                    <h4>Tabla 2: Tasa de Crecimiento del Mercado (TCM) — periodos</h4>
                    <p>Complete la demanda por producto para cada año (en miles de soles).</p>

                    <div class="table-responsive">
                        <table class="table table-bordered" id="tablaDemanda">
                            <thead class="table-light text-center">
                                <tr>
                                    <th>Año</th>
                                    <?php foreach ($productos as $p): ?><th><?php echo $p; ?></th><?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php for ($y=0;$y<$numYears;$y++): ?>
                                <tr>
                                    <td class="align-middle"><?php echo $anios[$y]; ?></td>
                                    <?php for ($p=0;$p<$numProd;$p++): ?>
                                    <td><input type="number" step="0.01" min="0" name="demanda_<?php echo $y; ?>_<?php echo $p; ?>" class="form-control demanda-input" value="<?php echo htmlspecialchars($data['demanda'][$y][$p]); ?>"></td>
                                    <?php endfor; ?>
                                </tr>
                                <?php endfor; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-light text-center">
                                    <td>TCM (CAGR %)</td>
                                    <?php for ($p=0;$p<$numProd;$p++): ?><td class="tcmtxt"><?php echo number_format($tcm_php[$p]*100,2); ?> %</td><?php endfor; ?>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Tabla 3: Resultados BCG -->
                 <div class="explanation-box p-3 mb-4">
                    <h4>Tabla 3: Resultados - Resumen BCG</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered text-center" id="tablaResultados">
                            <thead class="table-light">
                                <tr>
                                    <th>BCG</th>
                                    <?php foreach ($productos as $p): ?><th><?php echo $p; ?></th><?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>TCM (%)</strong></td>
                                    <?php for ($p=0;$p<$numProd;$p++): ?><td class="res-tcm"><?php echo number_format($tcm_php[$p]*100,2); ?> %</td><?php endfor; ?>
                                </tr>
                                <tr>
                                    <td><strong>PRM</strong></td>
                                    <?php for ($p=0;$p<$numProd;$p++): ?><td class="res-prm"><?php echo number_format($prm_php[$p],3); ?></td><?php endfor; ?>
                                </tr>
                                <tr>
                                    <td><strong>% s/VTAS</strong></td>
                                    <?php for ($p=0;$p<$numProd;$p++): ?><td class="res-porc"><?php echo number_format($porcVentas[$p],2); ?> %</td><?php endfor; ?>
                                </tr>
                                <tr>
                                    <td><strong>Clasificación</strong></td>
                                    <?php for ($p=0;$p<$numProd;$p++): ?><td class="res-clasif"><strong><?php echo $clasif_php[$p]; ?></strong></td><?php endfor; ?>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tabla 4: Niveles de venta competidores -->
                <div class="explanation-box p-3 mb-4">
                    <h4>Niveles de venta de los competidores (por producto)</h4>
                    <p>Ingrese hasta <?php echo $numCompetidores; ?> competidores por producto. La fila <strong>Mayor</strong> se calcula automáticamente.</p>

                    <div class="table-responsive">
                        <table class="table table-bordered" id="tablaCompetidores">
                            <thead class="table-light text-center">
                                <tr>
                                    <th>Competidor</th>
                                    <?php for ($p=0;$p<$numProd;$p++): ?><th><?php echo $productos[$p]; ?></th><?php endfor; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php for ($c=0;$c<$numCompetidores;$c++): ?>
                                <tr>
                                    <td>CP<?php echo $c+1; ?></td>
                                    <?php for ($p=0;$p<$numProd;$p++): ?>
                                    <td><input type="number" step="0.01" min="0" name="comp_<?php echo $p; ?>_<?php echo $c; ?>" class="form-control comp-input" value="<?php echo htmlspecialchars($data['competidores'][$p][$c]); ?>"></td>
                                    <?php endfor; ?>
                                </tr>
                                <?php endfor; ?>
                                <tr class="table-secondary">
                                    <td><strong>Mayor</strong></td>
                                    <?php for ($p=0;$p<$numProd;$p++): ?><td class="mayor-comp" data-prod="<?php echo $p; ?>">0.00</td><?php endfor; ?>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                
                

                <div class="d-flex justify-content-between mb-5">
                    <div>
                        <button type="button" class="btn btn-primary" id="btnCalcular">Calcular</button>
                        <button type="submit" class="btn btn-success">Guardar</button>
                    </div>
                    <div>
                        <a href="dashboard.php" class="btn btn-nav-outline">Volver al índice</a>
                    </div>
                </div>

            </form>

            <div class="card mb-4">
                <div class="card-body small text-muted">
                    <strong>Nota:</strong> Los cálculos de TCM (CAGR) se realizan entre 2012 y 2017 si hay datos completos. Las tasas periodicas por intervalo se usan como respaldo si faltan extremos.
                    Al guardar, los datos se almacenan en la sesión de PHP (persistencia temporal). Para almacenamiento permanente debería implementarse guardado en la base de datos.
                </div>
            </div>

        </div>
    </div>
</div>

<script>
// Funciones JS para cálculos interactivos
function fmt(n,dec=2){ return Number.isFinite(n)? n.toFixed(dec) : '0.00'; }

function calcularEnJS(){
    const numProd = <?php echo $numProd; ?>;
    const numYears = <?php echo $numYears; ?>;
    // Ventas
    const ventasEls = document.querySelectorAll('.venta-input');
    let ventas = Array.from(ventasEls).map(e=>parseFloat(e.value||0));
    const total = ventas.reduce((a,b)=>a+b,0);
    document.getElementById('totalVentas').innerText = fmt(total,2);
    // % sobre total
    document.querySelectorAll('.pct-venta').forEach((td,i)=>{ td.innerText = (total>0? (ventas[i]/total*100).toFixed(2) : '0.00') + ' %'; });

    // Demanda matrix
    let demanda = [];
    for (let y=0;y<numYears;y++){
        demanda[y]=[];
        for (let p=0;p<numProd;p++){
            const el = document.querySelector("input[name='demanda_"+y+"_"+p+"']");
            demanda[y][p] = parseFloat(el.value||0);
        }
    }

    // TCM (CAGR) por producto
    let tcms = [];
    for (let p=0;p<numProd;p++){
        const first = demanda[0][p];
        const last = demanda[numYears-1][p];
        if (first>0 && last>0){
            const cagr = Math.pow((last/first), 1/(numYears-1)) - 1;
            tcms[p]=cagr;
        } else {
            // promedio tasas periodicas
            let rates=[];
            for (let y=0;y<numYears-1;y++){
                const prev = demanda[y][p];
                const nxt = demanda[y+1][p];
                if (prev>0) rates.push((nxt/prev)-1);
            }
            tcms[p] = rates.length>0? rates.reduce((a,b)=>a+b,0)/rates.length : 0;
        }
    }

    // Competidores
    const numComp = <?php echo $numCompetidores; ?>;
    let competidores = [];
    for (let p=0;p<numProd;p++){
        competidores[p]=[];
        for (let c=0;c<numComp;c++){
            const el = document.querySelector("input[name='comp_"+p+"_"+c+"']");
            const v = el? parseFloat(el.value||0) : 0;
            competidores[p].push(v);
        }
    }
    // Mayor por producto
    for (let p=0;p<numProd;p++){
        const maxc = Math.max(...competidores[p],0);
        document.querySelector('.mayor-comp[data-prod="'+p+'"]').innerText = fmt(maxc,2);
    }

    // PRM
    const maxGlobal = Math.max(...ventas,0);
    let prms = [];
    for (let p=0;p<numProd;p++){
        const maxc = Math.max(...competidores[p],0);
        const ref = maxc>0? maxc : (maxGlobal>0? maxGlobal : 1);
        prms[p] = ref>0? ventas[p]/ref : 0;
    }

    // growth promedio
    const growth_prom = tcms.reduce((a,b)=>a+b,0)/tcms.length;

    // Clasificacion
    let clasif = [];
    for (let p=0;p<numProd;p++){
        const g = tcms[p];
        const r = prms[p];
        let cls = 'Perro';
        if (g > growth_prom && r >= 1) cls = 'Estrella';
        else if (g > growth_prom && r < 1) cls = 'Incógnita';
        else if (g <= growth_prom && r >= 1) cls = 'Vaca';
        clasif[p]=cls;
    }

    // Mostrar en tabla resultados
    document.querySelectorAll('.res-tcm').forEach((td,i)=> td.innerText = fmt(tcms[i]*100,2)+' %');
    document.querySelectorAll('.res-prm').forEach((td,i)=> td.innerText = fmt(prms[i],3));
    document.querySelectorAll('.res-porc').forEach((td,i)=> td.innerText = fmt((total>0? ventas[i]/total*100 : 0),2)+' %');
    document.querySelectorAll('.res-clasif').forEach((td,i)=> td.innerHTML = '<strong>'+clasif[i]+'</strong>');

}

// Eventos
document.getElementById('btnCalcular').addEventListener('click', function(e){ calcularEnJS(); });
// Calcular al cargar para mostrar valores guardados
window.addEventListener('load', function(){ calcularEnJS(); });

</script>

<?php
require_once 'includes/footer.php';
$mysqli->close();
?>
