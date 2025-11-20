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

// Obtener estrategia identificada y conclusiones desde resumen_ejecutivo
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
            line-height: 1.6;
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
        
        .header {
            background: linear-gradient(135deg, var(--brand-blue), var(--brand-dark));
            color: white;
            padding: 25px;
            text-align: center;
            margin: -30px -30px 30px -30px;
            border-radius: 12px 12px 0 0;
            box-shadow: 0 4px 20px rgba(0,0,0,.15);
        }
        
        .header h1 {
            font-size: 24px;
            margin: 0;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0,0,0,.3);
        }
        
        .index-tab {
            background: linear-gradient(135deg, #e57373, #d32f2f);
            color: white;
            padding: 10px 20px;
            display: inline-block;
            margin-bottom: 20px;
            font-weight: 700;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(211,47,47,.3);
            font-size: 13px;
            letter-spacing: 0.5px;
        }
        
        .logo-container {
            text-align: center;
            margin: 20px 0 30px 0;
            padding: 30px;
            border: 2px dashed rgba(15,47,70,.2);
            background: linear-gradient(135deg, #f8f9fa, #ffffff);
            border-radius: 12px;
        }
        
        .logo-container img {
            max-width: 200px;
            max-height: 120px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,.1);
        }
        
        .section-title {
            background: linear-gradient(135deg, var(--brand-blue), var(--brand-dark));
            color: white;
            padding: 12px 20px;
            margin: 25px 0 15px 0;
            font-weight: 700;
            font-size: 14px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(15,47,70,.2);
            letter-spacing: 0.5px;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 15px;
            align-items: flex-start;
            gap: 10px;
        }
        
        .info-label {
            color: var(--brand-blue);
            font-weight: 700;
            min-width: 200px;
            font-size: 12px;
        }
        
        .info-value {
            flex: 1;
            padding: 10px;
            border: 1px solid rgba(15,47,70,.15);
            background: linear-gradient(135deg, #ffffff, #f8f9fa);
            border-radius: 6px;
            font-size: 12px;
        }
        
        .content-box {
            border: 1px solid rgba(15,47,70,.15);
            padding: 15px;
            min-height: 80px;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #ffffff, #f8f9fa);
            border-radius: 8px;
            font-size: 12px;
        }
        
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
            padding: 10px;
            font-size: 12px;
        }
        
        .valores-table td:first-child {
            width: 40px;
            text-align: center;
            font-weight: 700;
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            color: var(--brand-blue);
        }
        
        .foda-grid {
            display: grid;
            grid-template-columns: 180px 1fr;
            gap: 10px;
            margin: 20px 0;
        }
        
        .foda-cell {
            border: 1px solid rgba(15,47,70,.15);
            padding: 15px;
            min-height: 100px;
            border-radius: 8px;
            font-size: 11px;
            line-height: 1.5;
        }
        
        .foda-label {
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-size: 12px;
            letter-spacing: 0.5px;
        }
        
        .foda-debilidades { background: linear-gradient(135deg, #fff9c4, #fff59d); }
        .foda-amenazas { background: linear-gradient(135deg, #b3e5fc, #81d4fa); }
        .foda-fortalezas { background: linear-gradient(135deg, #ffe0b2, #ffcc80); }
        .foda-oportunidades { background: linear-gradient(135deg, #ffccbc, #ffab91); }
        
        .objetivos-grid {
            display: grid;
            grid-template-columns: 140px 1fr 1fr;
            gap: 0;
            margin: 20px 0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,.08);
        }
        
        .obj-header {
            background: linear-gradient(135deg, #fff9c4, #fff59d);
            border: 1px solid rgba(15,47,70,.15);
            padding: 10px;
            font-weight: 700;
            text-align: center;
            font-size: 11px;
            color: var(--brand-dark);
        }
        
        .obj-cell {
            border: 1px solid rgba(15,47,70,.15);
            padding: 10px;
            min-height: 70px;
            font-size: 10px;
            line-height: 1.5;
            background: white;
        }
        
        .obj-mision {
            grid-row: span 4;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #fff9c4, #fff59d);
            padding: 15px;
            text-align: center;
            font-size: 10px;
            font-weight: 600;
            color: var(--brand-dark);
        }
        
        .acciones-list {
            counter-reset: item;
            list-style: none;
            padding: 0;
        }
        
        .acciones-list li {
            position: relative;
            padding: 10px 10px 10px 40px;
            margin-bottom: 8px;
            border: 1px solid rgba(15,47,70,.15);
            counter-increment: item;
            font-size: 11px;
            line-height: 1.5;
            border-radius: 6px;
            background: linear-gradient(135deg, #ffffff, #f8f9fa);
            transition: all 0.3s ease;
        }
        
        .acciones-list li:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,.08);
            transform: translateX(5px);
        }
        
        .acciones-list li:before {
            content: counter(item);
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: linear-gradient(135deg, var(--brand-green), var(--brand-green-600));
            color: white;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(24,179,107,.3);
        }
        
        .btn-container {
            margin-top: 40px;
            text-align: center;
            padding-top: 30px;
            border-top: 2px solid rgba(15,47,70,.1);
        }
        
        .btn {
            background: linear-gradient(135deg, var(--brand-green), var(--brand-green-600));
            color: white;
            padding: 14px 32px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
            font-weight: 600;
            font-size: 14px;
            box-shadow: 0 4px 12px rgba(24,179,107,.3);
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: linear-gradient(135deg, var(--brand-green-600), var(--brand-green));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(24,179,107,.4);
            text-decoration: none;
            color: white;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #2e7d32, #1b5e20);
            box-shadow: 0 4px 12px rgba(46,125,50,.3);
        }
        
        .btn-success:hover {
            background: linear-gradient(135deg, #1b5e20, #2e7d32);
            box-shadow: 0 6px 20px rgba(46,125,50,.4);
        }
        
        .editable-section {
            position: relative;
            margin: 15px 0;
        }
        
        .edit-textarea {
            width: 100%;
            min-height: 120px;
            padding: 15px;
            border: 2px solid rgba(15,47,70,.15);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px;
            resize: vertical;
            border-radius: 8px;
            background: linear-gradient(135deg, #ffffff, #f8f9fa);
            transition: all 0.3s ease;
        }
        
        .edit-textarea:focus {
            outline: none;
            border-color: var(--brand-green);
            box-shadow: 0 0 0 3px rgba(24,179,107,.1);
        }
        
        .save-btn-container {
            text-align: right;
            margin-top: 12px;
        }
        
        .mensaje-guardado {
            display: none;
            padding: 12px 20px;
            margin: 12px 0;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            font-size: 13px;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @media print {
            body {
                padding: 0;
                background: white;
            }
            .btn-container, .save-btn-container {
                display: none;
            }
            .container {
                box-shadow: none;
                padding: 15px;
            }
            .edit-textarea {
                border: 1px solid #ddd;
                background: white;
                padding: 10px;
            }
            .section-title {
                break-after: avoid;
            }
            .foda-grid, .objetivos-grid {
                break-inside: avoid;
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
        <div id="mensaje-estrategia" class="mensaje-guardado"></div>
        <p style="margin: 10px 0;">Escriba en el siguiente recuadro la estrategia identificada en la Matriz FODA</p>
        <div class="editable-section">
            <textarea id="estrategia-textarea" class="edit-textarea" placeholder="Escriba aquí la estrategia identificada..."><?php echo htmlspecialchars($estrategia_foda); ?></textarea>
            <div class="save-btn-container">
                <button onclick="guardarEstrategia()" class="btn btn-success">Guardar Estrategia</button>
            </div>
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
        <div id="mensaje-conclusiones" class="mensaje-guardado"></div>
        <p style="margin: 10px 0;">Anote las conclusiones más relevantes de su Plan.</p>
        <div class="editable-section">
            <textarea id="conclusiones-textarea" class="edit-textarea" style="min-height: 150px;" placeholder="Escriba aquí las conclusiones del plan estratégico..."><?php echo htmlspecialchars($conclusiones); ?></textarea>
            <div class="save-btn-container">
                <button onclick="guardarConclusiones()" class="btn btn-success">Guardar Conclusiones</button>
            </div>
        </div>
        
        <div class="btn-container">
            <a href="dashboard.php" class="btn">Volver al Dashboard</a>
            <button onclick="window.print()" class="btn">Imprimir / Guardar PDF</button>
        </div>
    </div>

    <script>
        function guardarEstrategia() {
            const texto = document.getElementById('estrategia-textarea').value;
            const formData = new FormData();
            formData.append('tipo', 'estrategia');
            formData.append('contenido', texto);
            
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
                setTimeout(() => { mensaje.style.display = 'none'; }, 3000);
            })
            .catch(error => {
                alert('Error al guardar: ' + error);
            });
        }
        
        function guardarConclusiones() {
            const texto = document.getElementById('conclusiones-textarea').value;
            const formData = new FormData();
            formData.append('tipo', 'conclusiones');
            formData.append('contenido', texto);
            
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
                setTimeout(() => { mensaje.style.display = 'none'; }, 3000);
            })
            .catch(error => {
                alert('Error al guardar: ' + error);
            });
        }
    </script>
</body>
</html>
<?php
$mysqli->close();
?>