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

$pageStyles = ['css/resumen.css', 'css/estrategias.css'];
require_once 'includes/db_connection.php';
require_once 'includes/header.php';

$id_empresa_actual = $_SESSION['id_empresa_actual'];

// 1. Obtener datos de la empresa (Misión, Visión, etc.) incluyendo imagen
$stmt_empresa = $mysqli->prepare("SELECT nombre_empresa, mision, vision, valores, unidades_estrategicas, imagen FROM empresa WHERE id = ?");
$stmt_empresa->bind_param("i", $id_empresa_actual);
$stmt_empresa->execute();
$empresa_data = $stmt_empresa->get_result()->fetch_assoc();
$stmt_empresa->close();

$valores_lista = !empty($empresa_data['valores']) ? explode("\n", trim($empresa_data['valores'])) : [];

// 2. Obtener y organizar los objetivos estratégicos
$stmt_objetivos = $mysqli->prepare("SELECT id, descripcion, tipo, id_padre FROM objetivos_estrategicos WHERE id_empresa = ? ORDER BY id_padre ASC, id ASC");
$stmt_objetivos->bind_param("i", $id_empresa_actual);
$stmt_objetivos->execute();
$resultado = $stmt_objetivos->get_result();

$objetivos_generales = [];
$objetivos_especificos_map = [];

while ($row = $resultado->fetch_assoc()) {
    if ($row['tipo'] == 'general') {
        $row['especificos'] = [];
        $objetivos_generales[$row['id']] = $row;
    } else {
        $objetivos_especificos_map[] = $row;
    }
}

foreach ($objetivos_especificos_map as $especifico) {
    if (isset($objetivos_generales[$especifico['id_padre']])) {
        $objetivos_generales[$especifico['id_padre']]['especificos'][] = $especifico;
    }
}
$stmt_objetivos->close();

?>

<div class="container mt-4">
    <div class="resumen-container">
        <div class="resumen-header">
            <h1 class="resumen-title">Resumen del Plan Ejecutivo</h1>
            <h2 class="empresa-title"><?php echo htmlspecialchars($empresa_data['nombre_empresa']); ?></h2>
        </div>
        
        <!-- Imagen de la empresa -->
        <?php if (!empty($empresa_data['imagen'])): ?>
        <div class="empresa-imagen-container">
            <img src="uploads/empresa_images/<?php echo htmlspecialchars($empresa_data['imagen']); ?>" 
                 alt="Logo de <?php echo htmlspecialchars($empresa_data['nombre_empresa']); ?>" 
                 class="empresa-imagen">
        </div>
        <?php endif; ?>
        
        <div class="resumen-content">
            
            <!-- Misión -->
            <div class="resumen-section">
                <h3 class="section-title">Misión</h3>
                <div class="section-content">
                    <p><?php echo !empty($empresa_data['mision']) ? nl2br(htmlspecialchars($empresa_data['mision'])) : '<span class="text-muted">No definida.</span>'; ?></p>
                </div>
            </div>
            
            <!-- Visión -->
            <div class="resumen-section">
                <h3 class="section-title">Visión</h3>
                <div class="section-content">
                    <p><?php echo !empty($empresa_data['vision']) ? nl2br(htmlspecialchars($empresa_data['vision'])) : '<span class="text-muted">No definida.</span>'; ?></p>
                </div>
            </div>
            
            <!-- Valores -->
            <div class="resumen-section">
                <h3 class="section-title">Valores</h3>
                <div class="section-content">
                    <?php if (!empty($valores_lista) && !empty(trim($valores_lista[0]))): ?>
                        <ul class="valores-list">
                            <?php foreach ($valores_lista as $valor): if(empty(trim($valor))) continue; ?>
                                <li><?php echo htmlspecialchars(trim($valor)); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted">No definidos.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Unidades Estratégicas -->
            <div class="resumen-section">
                <h3 class="section-title">Unidades Estratégicas</h3>
                <div class="section-content">
                    <p><?php echo !empty($empresa_data['unidades_estrategicas']) ? nl2br(htmlspecialchars($empresa_data['unidades_estrategicas'])) : '<span class="text-muted">No definidas.</span>'; ?></p>
                </div>
            </div>
            
            <!-- Objetivos Estratégicos -->
            <div class="resumen-section">
                <h3 class="section-title">Objetivos Estratégicos</h3>
                <div class="objetivos-layout-container">
                    <div class="objetivos-header-grid">
                        <div class="header-cell mision-header">MISIÓN</div>
                        <div class="header-cell">OBJETIVOS GENERALES O ESTRATÉGICOS</div>
                        <div class="header-cell">OBJETIVOS ESPECÍFICOS</div>
                    </div>
                    <div class="objetivos-body-grid">
                        <div class="mision-cell">
                            <?php echo !empty($empresa_data['mision']) ? htmlspecialchars($empresa_data['mision']) : ''; ?>
                        </div>
                        <div class="objetivos-rows-container">
                            <?php if (empty($objetivos_generales)): ?>
                                <div class="objetivo-row-item empty-state">
                                    <p class="text-muted">No se han definido objetivos. <a href="objetivos.php">Definir ahora</a></p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($objetivos_generales as $general): ?>
                                    <div class="objetivo-row-item">
                                        <div class="general-cell">
                                            <?php echo htmlspecialchars($general['descripcion']); ?>
                                        </div>
                                        <div class="especificos-cell">
                                            <?php if (empty($general['especificos'])): ?>
                                                <p class="text-muted-small">Sin objetivos específicos.</p>
                                            <?php else: ?>
                                                <ul>
                                                    <?php foreach ($general['especificos'] as $especifico): ?>
                                                        <li><?php echo htmlspecialchars($especifico['descripcion']); ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- FODA -->
        <div class="resumen-section mt-5">
            <h3 class="section-title" style="margin-left:32px;">Análisis FODA</h3>
            <?php
            // Obtener FODA de todas las fuentes (cadena_valor, bcg, etc.)
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
                $foda_data[$row['tipo']][] = $row['descripcion'];
            }
            $stmt_foda->close();
            
            // Debug: mostrar datos obtenidos
            // echo "<!-- Debug FODA: " . print_r($foda_data, true) . " -->";
            
            // Limitar a máximo 4 elementos por categoría
            foreach ($foda_data as $tipo => $items) {
                $foda_data[$tipo] = array_slice($items, 0, 4);
            }
            ?>
            <div class="module-container mt-4">
                <div class="module-content">
                    <div class="foda-summary">
                        <div class="foda-row debilidades">
                            <div class="foda-label"><span>DEBILIDADES</span></div>
                            <div class="foda-items">
                                <?php $debilidades = $foda_data['debilidad']; for ($i=0;$i<4;$i++): $item = $debilidades[$i] ?? ''; ?>
                                    <div class="foda-item<?php echo empty($item)?' empty':''; ?>"><?php echo !empty($item)? htmlspecialchars($item): '&nbsp;'; ?></div>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="foda-row amenazas">
                            <div class="foda-label"><span>AMENAZAS</span></div>
                            <div class="foda-items">
                                <?php $amenazas = $foda_data['amenaza']; for ($i=0;$i<4;$i++): $item = $amenazas[$i] ?? ''; ?>
                                    <div class="foda-item<?php echo empty($item)?' empty':''; ?>"><?php echo !empty($item)? htmlspecialchars($item): '&nbsp;'; ?></div>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="foda-row fortalezas">
                            <div class="foda-label"><span>FORTALEZAS</span></div>
                            <div class="foda-items">
                                <?php $fortalezas = $foda_data['fortaleza']; for ($i=0;$i<4;$i++): $item = $fortalezas[$i] ?? ''; ?>
                                    <div class="foda-item<?php echo empty($item)?' empty':''; ?>"><?php echo !empty($item)? htmlspecialchars($item): '&nbsp;'; ?></div>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="foda-row oportunidades">
                            <div class="foda-label"><span>OPORTUNIDADES</span></div>
                            <div class="foda-items">
                                <?php $oportunidades = $foda_data['oportunidad']; for ($i=0;$i<4;$i++): $item = $oportunidades[$i] ?? ''; ?>
                                    <div class="foda-item<?php echo empty($item)?' empty':''; ?>"><?php echo !empty($item)? htmlspecialchars($item): '&nbsp;'; ?></div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Navegación -->
        <div class="resumen-navigation">
            <a href="dashboard.php" class="btn btn-nav"><i class="fas fa-home"></i> Ir al Inicio</a>
            <a href="autodiagnostico_cadena_valor.php" class="btn btn-save"><i class="fas fa-edit"></i> Editar FODA (Cadena de Valor)</a>
            <a href="autodiagnostico_bdcg.php" class="btn btn-save"><i class="fas fa-edit"></i> Editar FODA (BCG)</a>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
$mysqli->close();
?>
