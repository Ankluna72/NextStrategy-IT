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

$pageStyles = ['css/modules.css', 'css/came.css'];
require_once 'includes/db_connection.php';
require_once 'includes/header.php';

$id_empresa_actual = $_SESSION['id_empresa_actual'];
$mensaje = '';

// Procesar guardado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acciones = $_POST['acciones'] ?? [];
    
    // Codificamos los arrays a JSON para guardarlos en las columnas correspondientes
    // Usamos JSON_UNESCAPED_UNICODE para que las tildes se guarden legibles
    $json_c = json_encode($acciones['corregir'] ?? [], JSON_UNESCAPED_UNICODE);
    $json_a = json_encode($acciones['afrontar'] ?? [], JSON_UNESCAPED_UNICODE);
    $json_m = json_encode($acciones['mantener'] ?? [], JSON_UNESCAPED_UNICODE);
    $json_e = json_encode($acciones['explotar'] ?? [], JSON_UNESCAPED_UNICODE);
    
    $fecha = date('Y-m-d H:i:s');

    try {
        // 1. Verificar si ya existe registro para esta empresa
        $check = $mysqli->prepare("SELECT id FROM matriz_came WHERE id_empresa = ?");
        $check->bind_param("i", $id_empresa_actual);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows > 0) {
            // 2. Si existe, ACTUALIZAMOS
            $stmt = $mysqli->prepare("UPDATE matriz_came SET acciones_c=?, acciones_a=?, acciones_m=?, acciones_e=?, fecha_actualizacion=? WHERE id_empresa=?");
            $stmt->bind_param("sssssi", $json_c, $json_a, $json_m, $json_e, $fecha, $id_empresa_actual);
        } else {
            // 3. Si no existe, INSERTAMOS
            $stmt = $mysqli->prepare("INSERT INTO matriz_came (id_empresa, acciones_c, acciones_a, acciones_m, acciones_e, fecha_creacion) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssss", $id_empresa_actual, $json_c, $json_a, $json_m, $json_e, $fecha);
        }
        
        if ($stmt->execute()) {
            $mensaje = '<div class="alert alert-success alert-success-auto">Matriz CAME guardada correctamente.</div>';
        } else {
            $mensaje = '<div class="alert alert-danger">Error al guardar en BD: ' . $stmt->error . '</div>';
        }
        $stmt->close();
        $check->close();
        
    } catch (Exception $e) {
        $mensaje = '<div class="alert alert-danger">Excepción: ' . $e->getMessage() . '</div>';
    }
}

// Cargar datos existentes para mostrarlos en el formulario
// Inicializamos estructura vacía con 4 espacios para cada sección
$datos_came = [
    'corregir' => array_fill(0, 4, ''),
    'afrontar' => array_fill(0, 4, ''),
    'mantener' => array_fill(0, 4, ''),
    'explotar' => array_fill(0, 4, '')
];

// Leer de la BD
$stmt_load = $mysqli->prepare("SELECT acciones_c, acciones_a, acciones_m, acciones_e FROM matriz_came WHERE id_empresa = ?");
$stmt_load->bind_param("i", $id_empresa_actual);
$stmt_load->execute();
$res = $stmt_load->get_result();

if ($row = $res->fetch_assoc()) {
    // Decodificar JSON a array PHP y mezclar con los vacíos para rellenar slots
    $c = json_decode($row['acciones_c'], true);
    $a = json_decode($row['acciones_a'], true);
    $m = json_decode($row['acciones_m'], true);
    $e = json_decode($row['acciones_e'], true);
    
    if(is_array($c)) $datos_came['corregir'] = array_replace($datos_came['corregir'], $c);
    if(is_array($a)) $datos_came['afrontar'] = array_replace($datos_came['afrontar'], $a);
    if(is_array($m)) $datos_came['mantener'] = array_replace($datos_came['mantener'], $m);
    if(is_array($e)) $datos_came['explotar'] = array_replace($datos_came['explotar'], $e);
}
$stmt_load->close();

// Configuración para generar el HTML dinámicamente
$secciones = [
    'corregir' => [
        'letra' => 'C',
        'titulo' => 'Corregir las debilidades',
        'clase' => 'section-corregir',
        'inicio_num' => 1
    ],
    'afrontar' => [
        'letra' => 'A',
        'titulo' => 'Afrontar las amenazas',
        'clase' => 'section-afrontar',
        'inicio_num' => 5
    ],
    'mantener' => [
        'letra' => 'M',
        'titulo' => 'Mantener las fortalezas',
        'clase' => 'section-mantener',
        'inicio_num' => 9
    ],
    'explotar' => [
        'letra' => 'E',
        'titulo' => 'Explotar las oportunidades',
        'clase' => 'section-explotar',
        'inicio_num' => 13
    ]
];
?>

<div class="container mt-4">
    <div class="module-container">
        <div class="module-header">
            <h2 class="module-title">11. MATRIZ CAME</h2>
        </div>
        
        <div class="module-content">
            <?php echo $mensaje; ?>
            
            <div class="explanation-box p-4 mb-5">
                <p>A continuación y para finalizar de elaborar un Plan Estratégico, además de tener identificada la estrategia es necesario determinar acciones que permitan <strong>corregir las debilidades, afrontar las amenazas, mantener las fortalezas y explotar las oportunidades.</strong></p>
                <p class="mb-0"><em>Reflexione y anote acciones a llevar a cabo teniendo en cuenta que estas acciones deben favorecer la ejecución exitosa de la estrategia general identificada.</em></p>
            </div>

            <form action="matriz_came.php" method="POST" id="formCame">
                <div class="came-container">
                    
                    <?php foreach ($secciones as $key => $info): ?>
                    <div class="came-section <?php echo $info['clase']; ?>">
                        <!-- Letra Lateral -->
                        <div class="came-letter-box">
                            <?php echo $info['letra']; ?>
                        </div>
                        
                        <!-- Contenido -->
                        <div class="came-content-box">
                            <div class="came-header">
                                <?php echo $info['titulo']; ?>
                            </div>
                            <ul class="came-inputs-list">
                                <?php 
                                $contador = $info['inicio_num'];
                                // Iteramos 4 veces por sección
                                for ($i = 0; $i < 4; $i++): 
                                    $valor = isset($datos_came[$key][$i]) ? $datos_came[$key][$i] : '';
                                ?>
                                <li class="came-input-row">
                                    <div class="came-index"><?php echo $contador; ?></div>
                                    <div class="came-input-field">
                                        <input type="text" 
                                               name="acciones[<?php echo $key; ?>][<?php echo $i; ?>]" 
                                               value="<?php echo htmlspecialchars($valor); ?>"
                                               placeholder="Escriba una acción...">
                                    </div>
                                </li>
                                <?php 
                                $contador++;
                                endfor; 
                                ?>
                            </ul>
                        </div>
                    </div>
                    <?php endforeach; ?>

                </div>

                <div class="text-center mt-5 mb-3">
                    <button type="submit" class="btn btn-save px-5 py-2" style="min-width: 300px;">
                        <i class="fas fa-save me-2"></i>Guardar Matriz CAME
                    </button>
                </div>
            </form>
            
            <div class="d-flex justify-content-between mt-5">
                <a href="id_estrategias.php" class="btn btn-nav">&laquo; Anterior: Ident. Estrategia</a>
                <a href="dashboard.php" class="btn btn-nav-outline">Volver al Índice</a>
                <a href="resumen_plan.php" class="btn btn-save">Siguiente: Resumen Ejecutivo &raquo;</a>
            </div>
        </div>
    </div>
</div>

<?php if (strpos($mensaje, 'alert-success') !== false): ?>
<script>
    setTimeout(function() {
        const alert = document.querySelector('.alert');
        if(alert) {
            alert.style.transition = "opacity 1s";
            alert.style.opacity = 0;
            setTimeout(() => alert.remove(), 1000);
        }
    }, 3000);
</script>
<?php endif; ?>

<?php
require_once 'includes/footer.php';
$mysqli->close();
?>