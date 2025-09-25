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

// Manejar la lógica de POST para añadir o eliminar objetivos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Añadir un nuevo objetivo GENERAL
    if (isset($_POST['add_general'])) {
        $descripcion = trim($_POST['descripcion_general']);
        if (!empty($descripcion)) {
            $stmt = $mysqli->prepare("INSERT INTO objetivos_estrategicos (id_empresa, descripcion, tipo) VALUES (?, ?, 'general')");
            $stmt->bind_param("is", $id_empresa_actual, $descripcion);
            if ($stmt->execute()) {
                $mensaje = '<div class="alert alert-success">Objetivo general añadido correctamente.</div>';
            } else {
                $mensaje = '<div class="alert alert-danger">Error al añadir el objetivo general.</div>';
            }
            $stmt->close();
        }
    }

    // Añadir un nuevo objetivo ESPECÍFICO
    if (isset($_POST['add_especifico'])) {
        $descripcion = trim($_POST['descripcion_especifico']);
        $id_padre = $_POST['id_padre'];
        if (!empty($descripcion) && !empty($id_padre)) {
            $stmt = $mysqli->prepare("INSERT INTO objetivos_estrategicos (id_empresa, descripcion, tipo, id_padre) VALUES (?, ?, 'especifico', ?)");
            $stmt->bind_param("isi", $id_empresa_actual, $descripcion, $id_padre);
            if ($stmt->execute()) {
                $mensaje = '<div class="alert alert-success">Objetivo específico añadido correctamente.</div>';
            } else {
                $mensaje = '<div class="alert alert-danger">Error al añadir el objetivo específico.</div>';
            }
            $stmt->close();
        }
    }

    // Eliminar un objetivo
    if (isset($_POST['delete_objetivo'])) {
        $id_objetivo = $_POST['id_objetivo'];
        // Opcional: Para evitar que se eliminen objetivos generales con hijos, primero verificar.
        // En este caso, la BD se encarga por ON DELETE CASCADE, eliminando hijos automáticamente.
        $stmt = $mysqli->prepare("DELETE FROM objetivos_estrategicos WHERE id = ? AND id_empresa = ?");
        $stmt->bind_param("ii", $id_objetivo, $id_empresa_actual);
        if ($stmt->execute()) {
            $mensaje = '<div class="alert alert-info">Objetivo eliminado correctamente.</div>';
        } else {
            $mensaje = '<div class="alert alert-danger">Error al eliminar el objetivo.</div>';
        }
        $stmt->close();
    }
}

// Obtener todos los objetivos de la empresa y organizarlos
$objetivos = [];
$stmt = $mysqli->prepare("SELECT id, descripcion, tipo, id_padre FROM objetivos_estrategicos WHERE id_empresa = ? ORDER BY id_padre ASC, id ASC");
$stmt->bind_param("i", $id_empresa_actual);
$stmt->execute();
$resultado = $stmt->get_result();

$objetivos_generales = [];
$objetivos_especificos = [];

while ($row = $resultado->fetch_assoc()) {
    if ($row['tipo'] == 'general') {
        $objetivos_generales[$row['id']] = $row;
        $objetivos_generales[$row['id']]['especificos'] = [];
    } else {
        $objetivos_especificos[] = $row;
    }
}

foreach ($objetivos_especificos as $especifico) {
    if (isset($objetivos_generales[$especifico['id_padre']])) {
        $objetivos_generales[$especifico['id_padre']]['especificos'][] = $especifico;
    }
}
$stmt->close();

?>
<style>
    .objetivo-general-card {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: .25rem;
        margin-bottom: 1.5rem;
        padding: 1.25rem;
    }
    .objetivo-general-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }
    .objetivo-general-title {
        font-size: 1.2rem;
        font-weight: 500;
        margin-bottom: 0;
    }
    .objetivo-especifico-list {
        list-style: none;
        padding-left: 0;
    }
    .objetivo-especifico-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background-color: #fff;
        padding: .75rem 1.25rem;
        border: 1px solid #e9ecef;
        margin-top: .5rem;
        border-radius: .25rem;
    }
    .form-add-especifico {
        display: flex;
        gap: 10px;
        margin-top: 1rem;
    }
</style>

<div class="container mt-4">
    <div class="module-container">
        <div class="module-header">
            <h2 class="module-title">4. OBJETIVOS ESTRATÉGICOS</h2>
        </div>
        <div class="module-content">
            <?php echo $mensaje; ?>

            <!-- Formulario para añadir Objetivo General -->
            <div class="card mb-4">
                <div class="card-header">
                    Añadir Nuevo Objetivo General
                </div>
                <div class="card-body">
                    <form action="objetivos.php" method="POST">
                        <div class="mb-3">
                            <textarea class="form-control" name="descripcion_general" rows="3" placeholder="Describe aquí el objetivo general o estratégico..."></textarea>
                        </div>
                        <button type="submit" name="add_general" class="btn btn-primary">Guardar Objetivo General</button>
                    </form>
                </div>
            </div>

            <!-- Lista de Objetivos -->
            <h3 class="mt-5 mb-3">Mis Objetivos</h3>
            <div id="lista-objetivos">
                <?php if (empty($objetivos_generales)): ?>
                    <p>Aún no has añadido ningún objetivo general.</p>
                <?php else: ?>
                    <?php foreach ($objetivos_generales as $general): ?>
                        <div class="objetivo-general-card">
                            <div class="objetivo-general-header">
                                <h5 class="objetivo-general-title"><?php echo htmlspecialchars($general['descripcion']); ?></h5>
                                <form action="objetivos.php" method="POST" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este objetivo general y todos sus objetivos específicos asociados?');">
                                    <input type="hidden" name="id_objetivo" value="<?php echo $general['id']; ?>">
                                    <button type="submit" name="delete_objetivo" class="btn btn-danger btn-sm">Eliminar</button>
                                </form>
                            </div>
                            
                            <h6>Objetivos Específicos:</h6>
                            <?php if (empty($general['especificos'])): ?>
                                <p><small>Aún no hay objetivos específicos para este objetivo general.</small></p>
                            <?php else: ?>
                                <ul class="objetivo-especifico-list">
                                    <?php foreach ($general['especificos'] as $especifico): ?>
                                        <li class="objetivo-especifico-item">
                                            <span><?php echo htmlspecialchars($especifico['descripcion']); ?></span>
                                            <form action="objetivos.php" method="POST" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este objetivo específico?');">
                                                <input type="hidden" name="id_objetivo" value="<?php echo $especifico['id']; ?>">
                                                <button type="submit" name="delete_objetivo" class="btn btn-outline-danger btn-sm">x</button>
                                            </form>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>

                            <!-- Formulario para añadir Objetivo Específico -->
                            <form action="objetivos.php" method="POST" class="form-add-especifico mt-3">
                                <input type="hidden" name="id_padre" value="<?php echo $general['id']; ?>">
                                <input type="text" class="form-control form-control-sm" name="descripcion_especifico" placeholder="Añadir un objetivo específico..." required>
                                <button type="submit" name="add_especifico" class="btn btn-secondary btn-sm">Añadir</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
             <div class="d-flex justify-content-between mt-4">
                <a href="valores.php" class="btn btn-nav">&laquo; Anterior: Valores</a>
                <a href="dashboard.php" class="btn btn-nav-outline">Volver al Índice</a>
                <a href="resumen_plan.php" class="btn btn-save">Siguiente: Resumen &raquo;</a>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
$mysqli->close();
?>
