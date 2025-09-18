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

$pageStyles = ['css/resumen.css'];
require_once 'includes/db_connection.php';
require_once 'includes/header.php';

$id_empresa_actual = $_SESSION['id_empresa_actual'];
$mensaje = '';

// Obtener datos de la empresa
$stmt_empresa = $mysqli->prepare("SELECT nombre_empresa, mision, vision, valores FROM empresa WHERE id = ?");
$stmt_empresa->bind_param("i", $id_empresa_actual);
$stmt_empresa->execute();
$resultado_empresa = $stmt_empresa->get_result();
$empresa_data = $resultado_empresa->fetch_assoc();
$stmt_empresa->close();

// Obtener objetivos estratégicos
$objetivos_data = [];
$stmt_objetivos = $mysqli->prepare("SELECT contenido FROM empresa_detalle WHERE id_empresa = ? AND tipo_analisis = 'Objetivos Estrategicos'");
$stmt_objetivos->bind_param("i", $id_empresa_actual);
$stmt_objetivos->execute();
$resultado_objetivos = $stmt_objetivos->get_result();
if ($fila = $resultado_objetivos->fetch_assoc()) {
    $objetivos_data = json_decode($fila['contenido'], true);
}
$stmt_objetivos->close();

// Obtener imagen de la empresa - COMENTADO TEMPORALMENTE
/*
$imagen_empresa = '';
$stmt_imagen = $mysqli->prepare("SELECT imagen FROM empresa WHERE id = ?");
$stmt_imagen->bind_param("i", $id_empresa_actual);
$stmt_imagen->execute();
$resultado_imagen = $stmt_imagen->get_result();
if ($fila = $resultado_imagen->fetch_assoc()) {
    $imagen_empresa = $fila['imagen'];
}
$stmt_imagen->close();

// Procesar subida de imagen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['imagen_empresa'])) {
    $upload_dir = 'images/empresas/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($_FILES['imagen_empresa']['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (in_array($file_extension, $allowed_extensions)) {
        $new_filename = 'empresa_' . $id_empresa_actual . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['imagen_empresa']['tmp_name'], $upload_path)) {
            // Actualizar en la base de datos
            $stmt_update = $mysqli->prepare("UPDATE empresa SET imagen = ? WHERE id = ?");
            $stmt_update->bind_param("si", $new_filename, $id_empresa_actual);
            if ($stmt_update->execute()) {
                $mensaje = '<div class="alert alert-success">Imagen actualizada correctamente.</div>';
                $imagen_empresa = $new_filename;
            } else {
                $mensaje = '<div class="alert alert-danger">Error al guardar la imagen en la base de datos.</div>';
            }
            $stmt_update->close();
        } else {
            $mensaje = '<div class="alert alert-danger">Error al subir la imagen.</div>';
        }
    } else {
        $mensaje = '<div class="alert alert-danger">Formato de imagen no válido. Use JPG, PNG o GIF.</div>';
    }
}
*/
$imagen_empresa = ''; // Variable vacía para evitar errores

$valores_lista = !empty($empresa_data['valores']) ? explode("\n", trim($empresa_data['valores'])) : [];
?>

<div class="container mt-4">
    <div class="resumen-container">
        <div class="resumen-header">
            <h1 class="resumen-title">Resumen del Plan Ejecutivo</h1>
            <h2 class="empresa-title"><?php echo htmlspecialchars($empresa_data['nombre_empresa']); ?></h2>
            
            <!-- Imagen de la empresa - COMENTADO TEMPORALMENTE -->
            <!--
            <div class="empresa-imagen-container">
                <?php if ($imagen_empresa): ?>
                    <img src="images/empresas/<?php echo htmlspecialchars($imagen_empresa); ?>" alt="Logo de <?php echo htmlspecialchars($empresa_data['nombre_empresa']); ?>" class="empresa-imagen">
                <?php else: ?>
                    <div class="empresa-imagen-placeholder">
                        <i class="fas fa-building"></i>
                        <span>Sin imagen</span>
                    </div>
                <?php endif; ?>
            </div>
            
            <form action="resumen_plan.php" method="POST" enctype="multipart/form-data" class="imagen-upload-form">
                <div class="upload-group">
                    <input type="file" name="imagen_empresa" id="imagen_empresa" accept="image/*" class="form-control-file">
                    <label for="imagen_empresa" class="btn btn-upload">
                        <i class="fas fa-upload"></i> Subir Imagen
                    </label>
                </div>
                <button type="submit" class="btn btn-save">Actualizar Imagen</button>
            </form>
            -->
        </div>
        
        <div class="resumen-content">
            <?php echo $mensaje; ?>
            
            <!-- Misión -->
            <div class="resumen-section">
                <h3 class="section-title">Misión</h3>
                <div class="section-content">
                    <?php if (!empty($empresa_data['mision'])): ?>
                        <p><?php echo nl2br(htmlspecialchars($empresa_data['mision'])); ?></p>
                    <?php else: ?>
                        <p class="text-muted">No se ha definido la misión. <a href="mision.php">Definir misión</a></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Visión -->
            <div class="resumen-section">
                <h3 class="section-title">Visión</h3>
                <div class="section-content">
                    <?php if (!empty($empresa_data['vision'])): ?>
                        <p><?php echo nl2br(htmlspecialchars($empresa_data['vision'])); ?></p>
                    <?php else: ?>
                        <p class="text-muted">No se ha definido la visión. <a href="vision.php">Definir visión</a></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Valores -->
            <div class="resumen-section">
                <h3 class="section-title">Valores</h3>
                <div class="section-content">
                    <?php if (!empty($valores_lista)): ?>
                        <ul class="valores-list">
                            <?php foreach ($valores_lista as $valor): ?>
                                <?php if (!empty(trim($valor))): ?>
                                    <li><?php echo htmlspecialchars(trim($valor)); ?></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted">No se han definido los valores. <a href="valores.php">Definir valores</a></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Objetivos Generales y Específicos -->
            <div class="resumen-section">
                <h3 class="section-title">Objetivos Estratégicos</h3>
                <div class="section-content">
                    <?php if (!empty($objetivos_data)): ?>
                        <?php if (!empty($objetivos_data['uens'])): ?>
                            <div class="uens-section">
                                <h4>Unidades Estratégicas de Negocio (UEN)</h4>
                                <p><?php echo nl2br(htmlspecialchars($objetivos_data['uens'])); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($objetivos_data['objetivos_especificos'])): ?>
                            <div class="objetivos-table">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Componente</th>
                                            <th>Objetivo General</th>
                                            <th>Objetivo Específico</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><strong>Misión</strong></td>
                                            <td><?php echo htmlspecialchars($empresa_data['mision']); ?></td>
                                            <td><?php echo htmlspecialchars($objetivos_data['objetivos_especificos']['mision'] ?? ''); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Visión</strong></td>
                                            <td><?php echo htmlspecialchars($empresa_data['vision']); ?></td>
                                            <td><?php echo htmlspecialchars($objetivos_data['objetivos_especificos']['vision'] ?? ''); ?></td>
                                        </tr>
                                        <?php foreach ($valores_lista as $index => $valor): ?>
                                            <?php if (!empty(trim($valor))): ?>
                                                <tr>
                                                    <td><strong>Valor <?php echo $index + 1; ?></strong></td>
                                                    <td><?php echo htmlspecialchars(trim($valor)); ?></td>
                                                    <td><?php echo htmlspecialchars($objetivos_data['objetivos_especificos']['valor_'.$index] ?? ''); ?></td>
                                                </tr>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-muted">No se han definido los objetivos estratégicos. <a href="objetivos.php">Definir objetivos</a></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Navegación -->
        <div class="resumen-navigation">
            <a href="dashboard.php" class="btn btn-nav">
                <i class="fas fa-home"></i> Ir al Inicio
            </a>
            <a href="objetivos.php" class="btn btn-save">
                <i class="fas fa-edit"></i> Editar Objetivos
            </a>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
$mysqli->close();
?>
