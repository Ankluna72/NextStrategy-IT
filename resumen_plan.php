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

// Obtener estrategia identificada y conclusiones
$estrategia_foda = '';
$conclusiones = '';
$stmt_resumen = $mysqli->prepare("SELECT estrategia_identificada, conclusiones FROM resumen_ejecutivo WHERE id_empresa = ?");
$stmt_resumen->bind_param("i", $id_empresa_actual);
$stmt_resumen->execute();
$res_resumen = $stmt_resumen->get_result();
if ($row_resumen = $res_resumen->fetch_assoc()) {
    $estrategia_foda = $row_resumen['estrategia_identificada'] ?? '';
    $conclusiones = $row_resumen['conclusiones'] ?? '';
}
$stmt_resumen->close();

// Obtener acciones competitivas
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plan Estratégico - <?php echo htmlspecialchars($empresa_data['nombre_empresa']); ?></title>
    <style>
        :root {
            --brand-dark: #0b1f2a;
            --brand-blue: #0f2f46;
            --brand-green: #18b36b;
            --brand-green-600: #12935a;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(180deg, #f0f6ff 0%, #e8f2ff 100%);
            padding: 20px;
            line-height: 1.5;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,.12);
        }
        
        /* HEADER */
        .header {
            background: linear-gradient(135deg, var(--brand-blue), var(--brand-dark));
            color: white;
            padding: 25px;
            text-align: center;
            margin: -30px -30px 20px -30px;
            border-radius: 12px 12px 0 0;
            box-shadow: 0 4px 20px rgba(0,0,0,.15);
        }
        
        .header h1 {
            font-size: 24px;
            margin: 0;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0,0,0,.3);
            text-transform: uppercase;
        }
        
        .index-tab {
            background: linear-gradient(135deg, #e57373, #d32f2f);
            color: white;
            padding: 8px 20px;
            display: inline-block;
            margin-bottom: 20px;
            font-weight: 700;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(211,47,47,.3);
            font-size: 13px;
        }
        
        .logo-container {
            text-align: center;
            margin: 10px 0 20px 0;
            padding: 20px;
            border: 2px dashed rgba(15,47,70,.2);
            background: linear-gradient(135deg, #f8f9fa, #ffffff);
            border-radius: 12px;
        }
        
        .logo-container img {
            max-width: 200px;
            max-height: 120px;
            border-radius: 8px;
        }
        
        /* TITULOS DE SECCIÓN */
        .section-title {
            background: linear-gradient(135deg, var(--brand-blue), var(--brand-dark));
            color: white;
            padding: 8px 15px;
            margin: 20px 0 10px 0;
            font-weight: 700;
            font-size: 13px;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(15,47,70,.2);
            letter-spacing: 0.5px;
            page-break-after: avoid; 
        }
        
        /* INFO ROWS */
        .info-section {
            display: block;
            margin-bottom: 15px;
        }

        .info-row {
            display: flex;
            margin-bottom: 10px;
            align-items: center;
            gap: 15px;
        }
        
        .info-label {
            color: var(--brand-blue);
            font-weight: 700;
            min-width: 180px;
            font-size: 12px;
            line-height: 1.2;
        }
        
        .info-value {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid rgba(15,47,70,.15);
            background: linear-gradient(135deg, #ffffff, #f8f9fa);
            border-radius: 6px;
            font-size: 12px;
            display: flex;
            align-items: center;
        }
        
        /* CAJAS DE CONTENIDO */
        .content-box {
            border: 1px solid rgba(15,47,70,.15);
            padding: 12px;
            min-height: 60px;
            margin-bottom: 15px;
            background: linear-gradient(135deg, #ffffff, #f8f9fa);
            border-radius: 8px;
            font-size: 11px;
            text-align: justify;
            line-height: 1.4;
        }
        
        /* TABLAS */
        .valores-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 10px 0;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .valores-table td {
            border: 1px solid rgba(15,47,70,.15);
            padding: 8px;
            font-size: 11px;
        }
        
        .valores-table td:first-child {
            width: 40px;
            text-align: center;
            font-weight: 700;
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            color: var(--brand-blue);
        }
        
        /* FODA */
        .foda-grid {
            display: grid;
            grid-template-columns: 140px 1fr;
            gap: 8px;
            margin: 15px 0;
        }
        
        .foda-cell {
            border: 1px solid rgba(15,47,70,.15);
            padding: 10px;
            min-height: 80px;
            border-radius: 6px;
            font-size: 10px;
            line-height: 1.4;
        }
        
        .foda-label {
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-size: 11px;
            color: #333;
        }
        
        .foda-debilidades { background: #fff9c4; }
        .foda-amenazas { background: #b3e5fc; }
        .foda-fortalezas { background: #ffe0b2; }
        .foda-oportunidades { background: #ffccbc; }
        
        /* OBJETIVOS */
        .objetivos-grid {
            display: grid;
            grid-template-columns: 120px 1fr 1fr;
            gap: 0;
            margin: 15px 0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,.08);
            border: 1px solid rgba(15,47,70,.15);
        }
        
        .obj-header {
            background: #fff9c4;
            border-bottom: 2px solid rgba(15,47,70,.15);
            border-right: 1px solid rgba(15,47,70,.15);
            padding: 8px;
            font-weight: 700;
            text-align: center;
            font-size: 10px;
            color: var(--brand-dark);
        }
        
        .obj-cell {
            border-bottom: 1px solid rgba(15,47,70,.15);
            border-right: 1px solid rgba(15,47,70,.15);
            padding: 8px;
            min-height: 60px;
            font-size: 10px;
            line-height: 1.4;
            background: white;
        }
        
        .obj-mision {
            grid-row: span 4;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff9c4;
            padding: 10px;
            text-align: center;
            font-size: 10px;
            font-weight: 600;
            color: var(--brand-dark);
            border-right: 1px solid rgba(15,47,70,.15);
        }
        
        /* ACCIONES */
        .acciones-list li {
            position: relative;
            padding: 8px 8px 8px 35px;
            margin-bottom: 6px;
            border: 1px solid rgba(15,47,70,.15);
            font-size: 11px;
            line-height: 1.4;
            border-radius: 6px;
            background: #ffffff;
            list-style: none;
            counter-increment: item;
        }
        
        .acciones-list { counter-reset: item; padding: 0; }
        
        .acciones-list li:before {
            content: counter(item);
            position: absolute;
            left: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: var(--brand-green);
            color: white;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 700;
        }
        
        /* UTILS */
        .btn-container, .save-btn-container { text-align: center; margin-top: 20px; }
        .btn { padding: 10px 25px; border-radius: 6px; border: none; color: white; background: var(--brand-green); cursor: pointer; text-decoration: none; display: inline-block; font-size: 14px; font-weight: 600; }
        .edit-textarea { width: 100%; min-height: 100px; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 12px; }
        .mensaje-guardado { display: none; padding: 10px; margin: 10px 0; border-radius: 6px; text-align: center; color: white; font-size: 12px; }

        /* --- ESTILOS DE IMPRESIÓN OPTIMIZADOS (Compactación) --- */
        @media print {
            @page {
                margin: 8mm; /* Márgenes más ajustados */
                size: A4;
            }

            body {
                background: white;
                padding: 0;
                margin: 0;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color: #000;
            }

            .container {
                box-shadow: none;
                width: 100%;
                max-width: 100%;
                margin: 0;
                border: 4px double var(--brand-dark); /* Borde doble formal */
                padding: 15px !important;
                min-height: 98vh;
            }

            /* OCULTAR INTERFAZ */
            .btn-container, .save-btn-container, .index-tab, .mensaje-guardado, .screen-only, .edit-intro-text {
                display: none !important;
            }
            .print-only { display: block !important; }

            /* HEADER COMPACTO */
            .header {
                margin: 0 0 10px 0;
                padding: 10px; /* Menos padding */
                border-radius: 4px;
                background: var(--brand-blue) !important;
                color: white !important;
            }
            .header h1 { font-size: 18px; } /* Texto más pequeño */
            .header p { display: none; } /* Ocultar subtítulo "Resumen Ejecutivo" para ahorrar espacio */

            .logo-container {
                padding: 5px;
                margin: 5px 0 10px 0;
                border: none;
                background: none;
            }
            .logo-container img { max-height: 60px; } /* Logo más pequeño */

            /* DISTRIBUCIÓN HORIZONTAL DE INFO (Ahorra mucho espacio vertical) */
            .info-section {
                display: grid !important;
                grid-template-columns: 1fr 1fr 1fr; /* 3 Columnas */
                gap: 10px;
                margin-bottom: 15px;
                border-bottom: 1px solid #ddd;
                padding-bottom: 10px;
            }
            
            .info-row {
                display: block; /* Stack label encima de value dentro de la celda */
                margin-bottom: 0;
            }
            
            .info-label {
                min-width: auto;
                font-size: 10px;
                margin-bottom: 2px;
                color: var(--brand-blue);
            }
            
            .info-value {
                font-size: 10px;
                padding: 4px 8px;
                background: none !important;
                border: 1px solid #ccc;
                min-height: auto;
            }

            /* MISIÓN Y VISIÓN LADO A LADO (2 Columnas) */
            .mision-vision-wrapper {
                display: grid !important;
                grid-template-columns: 1fr 1fr;
                gap: 15px;
            }
            
            /* Títulos más compactos */
            .section-title {
                margin: 10px 0 5px 0;
                padding: 5px 10px;
                font-size: 11px;
                background: var(--brand-dark) !important;
            }

            /* Contenido más compacto */
            .content-box {
                padding: 8px;
                min-height: auto;
                margin-bottom: 10px;
                font-size: 10px;
                border: 1px solid #ccc;
                background: white !important;
            }
            
            /* Ajustes de salto de página */
            .section-title, h2, .objetivos-grid, .foda-grid, .valores-table, li {
                break-inside: avoid;
                page-break-inside: avoid;
            }
            
            .section-title { break-after: avoid; page-break-after: avoid; }
            
            a { text-decoration: none; color: #000; }
        }
        
        .print-only { display: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="index-tab">ÍNDICE</div>
        
        <div class="header">
            <h1>PLAN ESTRATÉGICO</h1>
            <p>RESUMEN EJECUTIVO</p>
        </div>
        
        <div class="logo-container">
            <?php if (!empty($empresa_data['imagen'])): ?>
                <img src="uploads/empresa_images/<?php echo htmlspecialchars($empresa_data['imagen']); ?>" 
                     alt="Logo de <?php echo htmlspecialchars($empresa_data['nombre_empresa']); ?>">
            <?php else: ?>
                <p style="color: #999;" class="screen-only">Inserte el logo de su empresa</p>
            <?php endif; ?>
        </div>
        
        <div class="info-section">
            <div class="info-row">
                <div class="info-label">Empresa / Proyecto:</div>
                <div class="info-value"><?php echo htmlspecialchars($empresa_data['nombre_empresa']); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Fecha elaboración:</div>
                <div class="info-value"><?php echo date('d/m/Y'); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Emprendedores:</div>
                <div class="info-value">
                    <?php 
                    $stmt_user = $mysqli->prepare("SELECT nombre FROM usuario WHERE id = ?");
                    $stmt_user->bind_param("i", $id_usuario);
                    $stmt_user->execute();
                    $user_data = $stmt_user->get_result()->fetch_assoc();
                    echo htmlspecialchars($user_data['nombre'] ?? '');
                    $stmt_user->close();
                    ?>
                </div>
            </div>
        </div>
        
        <div class="mision-vision-wrapper">
            <div>
                <div class="section-title">MISIÓN</div>
                <div class="content-box">
                    <?php echo !empty($empresa_data['mision']) ? nl2br(htmlspecialchars($empresa_data['mision'])) : 'No definida'; ?>
                </div>
            </div>
            
            <div>
                <div class="section-title">VISIÓN</div>
                <div class="content-box">
                    <?php echo !empty($empresa_data['vision']) ? nl2br(htmlspecialchars($empresa_data['vision'])) : 'No definida'; ?>
                </div>
            </div>
        </div>
        
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
        
        <div class="section-title">UNIDADES ESTRATÉGICAS</div>
        <div class="content-box">
            <?php echo !empty($empresa_data['unidades_estrategicas']) ? nl2br(htmlspecialchars($empresa_data['unidades_estrategicas'])) : 'No definidas'; ?>
        </div>
        
        <div class="section-title">OBJETIVOS ESTRATÉGICOS</div>
        <div class="objetivos-grid">
            <div class="obj-header obj-mision">MISIÓN</div>
            <div class="obj-header">OBJETIVOS GENERALES</div>
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
                            echo '• ' . htmlspecialchars($esp['descripcion']) . '<br><br>';
                        }
                    } else {
                        echo '&nbsp;';
                    }
                    ?>
                </div>
            <?php endfor; ?>
        </div>
        
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
        
        <div class="section-title">IDENTIFICACIÓN DE ESTRATEGIA</div>
        
        <div id="mensaje-estrategia" class="mensaje-guardado"></div>
        <p class="edit-intro-text" style="margin: 10px 0; color: #666; font-size: 11px;">Escriba en el siguiente recuadro la estrategia identificada en la Matriz FODA:</p>
        
        <div class="editable-section">
            <div class="print-only content-box">
                <?php echo !empty($estrategia_foda) ? nl2br(htmlspecialchars($estrategia_foda)) : 'Sin estrategia definida.'; ?>
            </div>
            
            <div class="screen-only">
                <textarea id="estrategia-textarea" class="edit-textarea" placeholder="Escriba aquí la estrategia identificada..."><?php echo htmlspecialchars($estrategia_foda); ?></textarea>
                <div class="save-btn-container">
                    <button onclick="guardarEstrategia()" class="btn btn-success">Guardar Estrategia</button>
                </div>
            </div>
        </div>
        
        <div class="section-title">ACCIONES COMPETITIVAS</div>
        <ul class="acciones-list">
            <?php 
            $max_acciones = 16;
            for($i = 0; $i < $max_acciones; $i++): 
                $accion = isset($acciones_competitivas[$i]) ? $acciones_competitivas[$i] : '';
                if(!empty($accion) || $i < 3): 
            ?>
                <li><?php echo !empty($accion) ? htmlspecialchars($accion) : '&nbsp;'; ?></li>
            <?php 
                endif;
            endfor; 
            ?>
        </ul>
        
        <div class="section-title">CONCLUSIONES</div>
        
        <div id="mensaje-conclusiones" class="mensaje-guardado"></div>
        <p class="edit-intro-text" style="margin: 10px 0; color: #666; font-size: 11px;">Anote las conclusiones más relevantes de su Plan:</p>
        
        <div class="editable-section">
            <div class="print-only content-box">
                <?php echo !empty($conclusiones) ? nl2br(htmlspecialchars($conclusiones)) : 'Sin conclusiones registradas.'; ?>
            </div>
            
            <div class="screen-only">
                <textarea id="conclusiones-textarea" class="edit-textarea" style="min-height: 150px;" placeholder="Escriba aquí las conclusiones del plan estratégico..."><?php echo htmlspecialchars($conclusiones); ?></textarea>
                <div class="save-btn-container">
                    <button onclick="guardarConclusiones()" class="btn btn-success">Guardar Conclusiones</button>
                </div>
            </div>
        </div>
        
        <div class="btn-container screen-only">
            <a href="dashboard.php" class="btn">Volver al Dashboard</a>
            <button onclick="window.print()" class="btn" style="background: var(--brand-dark);">🖨️ Imprimir / Guardar PDF</button>
        </div>
    </div>

    <script>
        function guardarEstrategia() {
            const texto = document.getElementById('estrategia-textarea').value;
            const formData = new FormData();
            formData.append('tipo', 'estrategia');
            formData.append('contenido', texto);
            
            const btn = document.querySelector('#estrategia-textarea + .save-btn-container .btn');
            const originalText = btn.innerText;
            btn.innerText = 'Guardando...';
            
            fetch('guardar_resumen.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const mensaje = document.getElementById('mensaje-estrategia');
                mensaje.textContent = data.message;
                mensaje.style.display = 'block';
                mensaje.style.backgroundColor = data.success ? '#4caf50' : '#f44336';
                
                btn.innerText = originalText;
                
                if(data.success) {
                   setTimeout(() => { location.reload(); }, 1000);
                } else {
                   setTimeout(() => { mensaje.style.display = 'none'; }, 3000);
                }
            })
            .catch(error => {
                alert('Error al guardar: ' + error);
                btn.innerText = originalText;
            });
        }
        
        function guardarConclusiones() {
            const texto = document.getElementById('conclusiones-textarea').value;
            const formData = new FormData();
            formData.append('tipo', 'conclusiones');
            formData.append('contenido', texto);
            
            const btn = document.querySelector('#conclusiones-textarea + .save-btn-container .btn');
            const originalText = btn.innerText;
            btn.innerText = 'Guardando...';
            
            fetch('guardar_resumen.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const mensaje = document.getElementById('mensaje-conclusiones');
                mensaje.textContent = data.message;
                mensaje.style.display = 'block';
                mensaje.style.backgroundColor = data.success ? '#4caf50' : '#f44336';
                
                btn.innerText = originalText;

                if(data.success) {
                   setTimeout(() => { location.reload(); }, 1000);
                } else {
                   setTimeout(() => { mensaje.style.display = 'none'; }, 3000);
                }
            })
            .catch(error => {
                alert('Error al guardar: ' + error);
                btn.innerText = originalText;
            });
        }
    </script>
</body>
</html>
<?php
$mysqli->close();
?>