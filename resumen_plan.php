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

$id_empresa_actual = $_SESSION['id_empresa_actual'];
$id_usuario = $_SESSION['id_usuario'];

$id_empresa_actual = $_SESSION['id_empresa_actual'];
$id_usuario = $_SESSION['id_usuario'];

// Obtener datos de la empresa
$stmt_empresa = $mysqli->prepare("SELECT nombre_empresa, mision, vision, valores, unidades_estrategicas, imagen FROM empresa WHERE id = ?");
$stmt_empresa->bind_param("i", $id_empresa_actual);
$stmt_empresa->execute();
$empresa_data = $stmt_empresa->get_result()->fetch_assoc();
$stmt_empresa->close();

$valores_lista = !empty($empresa_data['valores']) ? explode("\n", trim($empresa_data['valores'])) : [];

// Obtener Objetivos
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

// Obtener datos FODA
$stmt_foda = $mysqli->prepare("SELECT tipo, descripcion FROM foda WHERE id_empresa = ? ORDER BY tipo, id ASC");
$stmt_foda->bind_param("i", $id_empresa_actual);
$stmt_foda->execute();
$result_foda = $stmt_foda->get_result();
$foda_data = [
    'debilidad' => [], 'amenaza' => [], 'fortaleza' => [], 'oportunidad' => []
];
while ($row = $result_foda->fetch_assoc()) {
    $foda_data[$row['tipo']][] = $row['descripcion'];
}
$stmt_foda->close();

// Obtener estrategia identificada (de matriz_came o tabla estrategias)
$estrategia_foda = '';
$stmt_estrategia = $mysqli->prepare("SELECT estrategia_identificada FROM matriz_came WHERE id_empresa = ?");
$stmt_estrategia->bind_param("i", $id_empresa_actual);
$stmt_estrategia->execute();
$res_estrategia = $stmt_estrategia->get_result();
if ($row_est = $res_estrategia->fetch_assoc()) {
    $estrategia_foda = $row_est['estrategia_identificada'] ?? '';
}
$stmt_estrategia->close();

// Obtener acciones competitivas (de matriz_came)
$acciones_competitivas = [];
$stmt_acciones = $mysqli->prepare("SELECT acciones_c, acciones_a, acciones_m, acciones_e FROM matriz_came WHERE id_empresa = ?");
$stmt_acciones->bind_param("i", $id_empresa_actual);
$stmt_acciones->execute();
$res_acciones = $stmt_acciones->get_result();
if ($row_acc = $res_acciones->fetch_assoc()) {
    $acciones_c = json_decode($row_acc['acciones_c'], true) ?? [];
    $acciones_a = json_decode($row_acc['acciones_a'], true) ?? [];
    $acciones_m = json_decode($row_acc['acciones_m'], true) ?? [];
    $acciones_e = json_decode($row_acc['acciones_e'], true) ?? [];
    $acciones_competitivas = array_merge($acciones_c, $acciones_a, $acciones_m, $acciones_e);
    $acciones_competitivas = array_filter($acciones_competitivas);
}
$stmt_acciones->close();

// Obtener conclusiones
$conclusiones = '';
$stmt_concl = $mysqli->prepare("SELECT conclusiones FROM resumen_ejecutivo WHERE id_empresa = ?");
$stmt_concl->bind_param("i", $id_empresa_actual);
$stmt_concl->execute();
$res_concl = $stmt_concl->get_result();
if ($row_concl = $res_concl->fetch_assoc()) {
    $conclusiones = $row_concl['conclusiones'] ?? '';
}
$stmt_concl->close();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plan Estratégico - <?php echo htmlspecialchars($empresa_data['nombre_empresa']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
            line-height: 1.3;
            font-size: 11px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .header {
            background: linear-gradient(135deg, #40a8c4 0%, #1e7a9e 100%);
            color: white;
            padding: 15px;
            text-align: center;
            margin: -20px -20px 20px -20px;
        }
        
        .header h1 {
            font-size: 20px;
            margin: 0;
        }
        
        .index-tab {
            background-color: #e57373;
            color: white;
            padding: 8px 15px;
            display: inline-block;
            margin-bottom: 15px;
            font-weight: bold;
            border: 2px solid #333;
            font-size: 12px;
        }
        
        .logo-container {
            text-align: center;
            margin: 15px 0 20px 0;
            padding: 20px;
            border: 2px solid #1976d2;
            background-color: #f8f9fa;
        }
        
        .logo-container img {
            max-width: 150px;
            max-height: 100px;
        }
        
        .section-title {
            background-color: #1976d2;
            color: white;
            padding: 8px 15px;
            margin: 15px 0 10px 0;
            font-weight: bold;
            font-size: 12px;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 10px;
            align-items: flex-start;
            font-size: 10px;
        }
        
        .info-label {
            color: #1976d2;
            font-weight: bold;
            margin-right: 8px;
            min-width: 180px;
            font-size: 10px;
        }
        
        .info-value {
            flex: 1;
            padding: 6px;
            border: 2px solid #1976d2;
            background-color: white;
            font-size: 10px;
        }
        
        .content-box {
            border: 2px solid #1976d2;
            padding: 10px;
            min-height: 60px;
            margin-bottom: 15px;
            font-size: 10px;
        }
        
        .valores-table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
        }
        
        .valores-table td {
            border: 2px solid #1976d2;
            padding: 6px;
            font-size: 10px;
        }
        
        .valores-table td:first-child {
            width: 25px;
            text-align: center;
            font-weight: bold;
            background-color: #e3f2fd;
        }
        
        .foda-grid {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 0;
            margin: 15px 0;
        }
        
        .foda-cell {
            border: 2px solid #1976d2;
            padding: 10px;
            min-height: 70px;
            font-size: 9px;
            line-height: 1.3;
        }
        
        .foda-label {
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        
        .foda-debilidades { background-color: #fff9c4; }
        .foda-amenazas { background-color: #b3e5fc; }
        .foda-fortalezas { background-color: #ffe0b2; }
        .foda-oportunidades { background-color: #ffccbc; }
        
        .objetivos-grid {
            display: grid;
            grid-template-columns: 120px 1fr 1fr;
            gap: 0;
            margin: 15px 0;
            border: 2px solid #000;
        }
        
        .obj-header {
            background-color: #fff9c4;
            border: 2px solid #000;
            padding: 6px;
            font-weight: bold;
            text-align: center;
            font-size: 10px;
        }
        
        .obj-cell {
            border: 2px solid #000;
            padding: 6px;
            min-height: 50px;
            font-size: 9px;
            line-height: 1.3;
        }
        
        .obj-mision {
            grid-row: span 4;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #fff9c4;
            padding: 10px;
            text-align: center;
            font-size: 9px;
        }
        
        .obj-row-gen {
            background-color: #b3e5fc;
        }
        
        .obj-row-esp {
            background-color: white;
        }
        
        .acciones-list {
            counter-reset: item;
            list-style: none;
            padding: 0;
        }
        
        .acciones-list li {
            position: relative;
            padding: 6px 6px 6px 30px;
            margin-bottom: 3px;
            border: 2px solid #1976d2;
            counter-increment: item;
            font-size: 9px;
            line-height: 1.3;
        }
        
        .acciones-list li:before {
            content: counter(item);
            position: absolute;
            left: 6px;
            top: 50%;
            transform: translateY(-50%);
            background-color: #1976d2;
            color: white;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: bold;
        }
        
        .btn-container {
            margin-top: 30px;
            text-align: center;
        }
        
        .btn {
            background-color: #1976d2;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        
        .btn:hover {
            background-color: #1565c0;
        }
        
        @media print {
            body {
                padding: 0;
                background: white;
            }
            .btn-container {
                display: none;
            }
            .container {
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="index-tab">ÍNDICE</div>
        
        <div class="header">
            <h1>Resumen ejecutivo PLAN ESTRATÉGICO</h1>
        </div>
        
        <div class="logo-container">
            <?php if (!empty($empresa_data['imagen'])): ?>
                <img src="uploads/empresa_images/<?php echo htmlspecialchars($empresa_data['imagen']); ?>" 
                     alt="Logo de <?php echo htmlspecialchars($empresa_data['nombre_empresa']); ?>">
            <?php else: ?>
                <p style="color: #999;">Inserte el logo de su empresa</p>
            <?php endif; ?>
        </div>
        
        <h2 style="text-align: center; margin: 15px 0 20px 0; font-size: 14px;">RESUMEN EJECUTIVO DEL PLAN ESTRATÉGICO</h2>
        
        <div class="info-row">
            <div class="info-label">Nombre de la empresa / proyecto:</div>
            <div class="info-value"><?php echo htmlspecialchars($empresa_data['nombre_empresa']); ?></div>
        </div>
        
        <div class="info-row">
            <div class="info-label">Fecha de elaboración:</div>
            <div class="info-value"><?php echo date('d/m/Y'); ?></div>
        </div>
        
        <div class="info-row">
            <div class="info-label">Emprendedores / promotores:</div>
            <div class="info-value">
                <?php 
                // Obtener nombre del usuario
                $stmt_user = $mysqli->prepare("SELECT nombre FROM usuario WHERE id = ?");
                $stmt_user->bind_param("i", $id_usuario);
                $stmt_user->execute();
                $user_data = $stmt_user->get_result()->fetch_assoc();
                echo htmlspecialchars($user_data['nombre'] ?? '');
                $stmt_user->close();
                ?>
            </div>
        </div>
        
        <!-- MISIÓN -->
        <div class="section-title">MISIÓN</div>
        <div class="content-box">
            <?php echo !empty($empresa_data['mision']) ? nl2br(htmlspecialchars($empresa_data['mision'])) : 'No definida'; ?>
        </div>
        
        <!-- VISIÓN -->
        <div class="section-title">VISIÓN</div>
        <div class="content-box">
            <?php echo !empty($empresa_data['vision']) ? nl2br(htmlspecialchars($empresa_data['vision'])) : 'No definida'; ?>
        </div>
        
        <!-- VALORES -->
        <div class="section-title">VALORES</div>
        <table class="valores-table">
            <?php 
            $max_valores = 4;
            for($i = 0; $i < $max_valores; $i++): 
                $valor = isset($valores_lista[$i]) ? trim($valores_lista[$i]) : '';
            ?>
            <tr>
                <td><?php echo ($i + 1); ?></td>
                <td><?php echo !empty($valor) ? htmlspecialchars($valor) : '&nbsp;'; ?></td>
            </tr>
            <?php endfor; ?>
        </table>
        
        <!-- UNIDADES ESTRATÉGICAS -->
        <div class="section-title">UNIDADES ESTRATÉGICAS</div>
        <div class="content-box">
            <?php echo !empty($empresa_data['unidades_estrategicas']) ? nl2br(htmlspecialchars($empresa_data['unidades_estrategicas'])) : 'No definidas'; ?>
        </div>
        
        <!-- OBJETIVOS ESTRATÉGICOS -->
        <div class="section-title">OBJETIVOS ESTRATÉGICOS</div>
        <div class="objetivos-grid">
            <div class="obj-header obj-mision">MISIÓN</div>
            <div class="obj-header">OBJETIVOS GENERALES O ESTRATÉGICOS</div>
            <div class="obj-header">OBJETIVOS ESPECÍFICOS</div>
            
            <?php 
            $max_objetivos = 3;
            $objetivos_array = array_values($objetivos_generales);
            for($i = 0; $i < $max_objetivos; $i++): 
                $objetivo = isset($objetivos_array[$i]) ? $objetivos_array[$i] : null;
                $bg_colors = ['#b3e5fc', '#ffe0b2', '#ffccbc'];
            ?>
                <div class="obj-cell obj-row-gen" style="background-color: <?php echo $bg_colors[$i]; ?>;">
                    <?php echo $objetivo ? htmlspecialchars($objetivo['descripcion']) : '&nbsp;'; ?>
                </div>
                <div class="obj-cell obj-row-esp">
                    <?php 
                    if ($objetivo && !empty($objetivo['especificos'])) {
                        foreach(array_slice($objetivo['especificos'], 0, 2) as $esp) {
                            echo htmlspecialchars($esp['descripcion']) . '<br><br>';
                        }
                    } else {
                        echo '&nbsp;';
                    }
                    ?>
                </div>
            <?php endfor; ?>
        </div>
        
        <!-- ANÁLISIS FODA -->
        <div class="section-title">ANÁLISIS FODA</div>
        <div class="foda-grid">
            <div class="foda-cell foda-label foda-debilidades">DEBILIDADES</div>
            <div class="foda-cell foda-debilidades">
                <?php 
                if (!empty($foda_data['debilidad'])) {
                    foreach($foda_data['debilidad'] as $item) {
                        echo '• ' . htmlspecialchars($item) . '<br>';
                    }
                } else {
                    echo '&nbsp;';
                }
                ?>
            </div>
            
            <div class="foda-cell foda-label foda-amenazas">AMENAZAS</div>
            <div class="foda-cell foda-amenazas">
                <?php 
                if (!empty($foda_data['amenaza'])) {
                    foreach($foda_data['amenaza'] as $item) {
                        echo '• ' . htmlspecialchars($item) . '<br>';
                    }
                } else {
                    echo '&nbsp;';
                }
                ?>
            </div>
            
            <div class="foda-cell foda-label foda-fortalezas">FORTALEZAS</div>
            <div class="foda-cell foda-fortalezas">
                <?php 
                if (!empty($foda_data['fortaleza'])) {
                    foreach($foda_data['fortaleza'] as $item) {
                        echo '• ' . htmlspecialchars($item) . '<br>';
                    }
                } else {
                    echo '&nbsp;';
                }
                ?>
            </div>
            
            <div class="foda-cell foda-label foda-oportunidades">OPORTUNIDADES</div>
            <div class="foda-cell foda-oportunidades">
                <?php 
                if (!empty($foda_data['oportunidad'])) {
                    foreach($foda_data['oportunidad'] as $item) {
                        echo '• ' . htmlspecialchars($item) . '<br>';
                    }
                } else {
                    echo '&nbsp;';
                }
                ?>
            </div>
        </div>
        
        <!-- IDENTIFICACIÓN DE ESTRATEGIA -->
        <div class="section-title">IDENTIFICACIÓN DE ESTRATEGIA</div>
        <p style="margin: 10px 0;">Escriba en el siguiente recuadro la estrategia identificada en la Matriz FODA</p>
        <div class="content-box">
            <?php echo !empty($estrategia_foda) ? nl2br(htmlspecialchars($estrategia_foda)) : 'No definida'; ?>
        </div>
        
        <!-- ACCIONES COMPETITIVAS -->
        <div class="section-title">ACCIONES COMPETITIVAS</div>
        <ul class="acciones-list">
            <?php 
            $max_acciones = 16;
            for($i = 0; $i < $max_acciones; $i++): 
                $accion = isset($acciones_competitivas[$i]) ? $acciones_competitivas[$i] : '';
            ?>
                <li><?php echo !empty($accion) ? htmlspecialchars($accion) : '&nbsp;'; ?></li>
            <?php endfor; ?>
        </ul>
        
        <!-- CONCLUSIONES -->
        <div class="section-title">CONCLUSIONES</div>
        <p style="margin: 10px 0;">Anote las conclusiones más relevantes de su Plan.</p>
        <div class="content-box" style="min-height: 150px;">
            <?php echo !empty($conclusiones) ? nl2br(htmlspecialchars($conclusiones)) : 'No definidas'; ?>
        </div>
        
        <div class="btn-container">
            <a href="dashboard.php" class="btn">Volver al Dashboard</a>
            <button onclick="window.print()" class="btn">Imprimir / Guardar PDF</button>
            <button onclick="descargarPDF()" class="btn" style="background-color: #d32f2f;">Descargar PDF</button>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function descargarPDF() {
            const elemento = document.querySelector('.container');
            const nombreEmpresa = '<?php echo preg_replace('/[^a-zA-Z0-9]/', '_', $empresa_data['nombre_empresa']); ?>';
            const fecha = '<?php echo date('Y-m-d'); ?>';
            
            // Ocultar botones antes de generar PDF
            const btnContainer = document.querySelector('.btn-container');
            btnContainer.style.display = 'none';
            
            // Ajustar estilos para PDF
            const originalPadding = elemento.style.padding;
            elemento.style.padding = '15px';
            elemento.style.fontSize = '10px';
            
            const opciones = {
                margin: [5, 5, 5, 5],
                filename: `Plan_Estrategico_${nombreEmpresa}_${fecha}.pdf`,
                image: { type: 'jpeg', quality: 0.95 },
                html2canvas: { 
                    scale: 1.5,
                    useCORS: true,
                    letterRendering: true,
                    logging: false
                },
                jsPDF: { 
                    unit: 'mm', 
                    format: 'a4', 
                    orientation: 'portrait',
                    compress: true
                },
                pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
            };
            
            html2pdf().set(opciones).from(elemento).save().then(() => {
                // Restaurar estilos y mostrar botones
                elemento.style.padding = originalPadding;
                elemento.style.fontSize = '';
                btnContainer.style.display = 'block';
            });
        }
    </script>
</body>
</html>
<?php
$mysqli->close();
?>