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

// Manejar mensajes de la sesión (patrón PRG)
if (isset($_SESSION['mensaje_objetivos'])) {
    $mensaje = $_SESSION['mensaje_objetivos'];
    unset($_SESSION['mensaje_objetivos']);
}

// Manejar la lógica de POST para añadir, eliminar o actualizar objetivos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Añadir un nuevo objetivo GENERAL
    if (isset($_POST['add_general'])) {
        $descripcion = trim($_POST['descripcion_general']);
        if (!empty($descripcion)) {
            $stmt = $mysqli->prepare("INSERT INTO objetivos_estrategicos (id_empresa, descripcion, tipo) VALUES (?, ?, 'general')");
            $stmt->bind_param("is", $id_empresa_actual, $descripcion);
            if ($stmt->execute()) {
                $_SESSION['mensaje_objetivos'] = '<div class="alert alert-success alert-success-auto">Objetivo general añadido correctamente.</div>';
            } else {
                $_SESSION['mensaje_objetivos'] = '<div class="alert alert-danger">Error al añadir el objetivo general.</div>';
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
                $_SESSION['mensaje_objetivos'] = '<div class="alert alert-success alert-success-auto">Objetivo específico añadido correctamente.</div>';
            } else {
                $_SESSION['mensaje_objetivos'] = '<div class="alert alert-danger">Error al añadir el objetivo específico.</div>';
            }
            $stmt->close();
        }
    }

    // Eliminar un objetivo
    if (isset($_POST['delete_objetivo'])) {
        $id_objetivo = $_POST['id_objetivo'];
        $stmt = $mysqli->prepare("DELETE FROM objetivos_estrategicos WHERE id = ? AND id_empresa = ?");
        $stmt->bind_param("ii", $id_objetivo, $id_empresa_actual);
        if ($stmt->execute()) {
            $_SESSION['mensaje_objetivos'] = '<div class="alert alert-info alert-success-auto">Objetivo eliminado correctamente.</div>';
        } else {
            $_SESSION['mensaje_objetivos'] = '<div class="alert alert-danger">Error al eliminar el objetivo.</div>';
        }
        $stmt->close();
    }

    // Actualizar un objetivo
    if (isset($_POST['update_objetivo'])) {
        $id_objetivo = $_POST['id_objetivo'];
        $descripcion = trim($_POST['descripcion']);
        if (!empty($descripcion)) {
            $stmt = $mysqli->prepare("UPDATE objetivos_estrategicos SET descripcion = ? WHERE id = ? AND id_empresa = ?");
            $stmt->bind_param("sii", $descripcion, $id_objetivo, $id_empresa_actual);
            if ($stmt->execute()) {
                $_SESSION['mensaje_objetivos'] = '<div class="alert alert-success alert-success-auto">Objetivo actualizado correctamente.</div>';
            } else {
                $_SESSION['mensaje_objetivos'] = '<div class="alert alert-danger">Error al actualizar el objetivo.</div>';
            }
            $stmt->close();
        }
    }
    
    // Redireccionar para evitar reenvío de POST
    header('Location: objetivos.php');
    exit();
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
    .objetivo-actions > * {
        margin: 0 2px;
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
                                <div class="objetivo-descripcion" id="descripcion-general-<?php echo $general['id']; ?>">
                                    <h5 class="objetivo-general-title"><?php echo htmlspecialchars($general['descripcion']); ?></h5>
                                </div>
                                <div class="objetivo-actions">
                                    <button class="btn btn-sm btn-outline-primary btn-edit" data-id="<?php echo $general['id']; ?>" data-tipo="general"><i class="fas fa-edit"></i></button>
                                    <form action="objetivos.php" method="POST" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este objetivo general y todos sus objetivos específicos asociados?');" style="display: inline;">
                                        <input type="hidden" name="id_objetivo" value="<?php echo $general['id']; ?>">
                                        <button type="submit" name="delete_objetivo" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </div>
                            
                            <h6>Objetivos Específicos:</h6>
                            <?php if (empty($general['especificos'])): ?>
                                <p><small>Aún no hay objetivos específicos para este objetivo general.</small></p>
                            <?php else: ?>
                                <ul class="objetivo-especifico-list">
                                    <?php foreach ($general['especificos'] as $especifico):
                                        echo '<li class="objetivo-especifico-item">';
                                        echo '<span class="objetivo-descripcion" id="descripcion-especifico-'. $especifico['id'] .'">'. htmlspecialchars($especifico['descripcion']) .'</span>';
                                        echo '<div class="objetivo-actions">';
                                        echo '<button class="btn btn-sm btn-outline-primary btn-edit" data-id="'. $especifico['id'] .'" data-tipo="especifico"><i class="fas fa-edit"></i></button>';
                                        echo '<form action="objetivos.php" method="POST" onsubmit="return confirm(\'¿Estás seguro de que deseas eliminar este objetivo específico?\');" style="display: inline;">';
                                        echo '<input type="hidden" name="id_objetivo" value="'. $especifico['id'] .'">';
                                        echo '<button type="submit" name="delete_objetivo" class="btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>';
                                        echo '</form>';
                                        echo '</div>';
                                        echo '</li>';
                                    endforeach;
                                echo '</ul>';
                            endif; ?>

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
                <a href="analisis_info.php" class="btn btn-save">Siguiente: Análisis Interno y Externo &raquo;</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editButtons = document.querySelectorAll('.btn-edit');

    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const tipo = this.dataset.tipo;
            const descripcionElement = document.getElementById(`descripcion-${tipo}-${id}`);
            const currentDescription = descripcionElement.innerText;

            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'form-control';
            input.value = currentDescription;

            const saveButton = document.createElement('button');
            saveButton.className = 'btn btn-sm btn-outline-success';
            saveButton.innerHTML = '<i class="fas fa-save"></i>';

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'objetivos.php';
            form.style.display = 'inline';

            const hiddenId = document.createElement('input');
            hiddenId.type = 'hidden';
            hiddenId.name = 'id_objetivo';
            hiddenId.value = id;

            const hiddenDesc = document.createElement('input');
            hiddenDesc.type = 'hidden';
            hiddenDesc.name = 'descripcion';

            const hiddenUpdate = document.createElement('input');
            hiddenUpdate.type = 'hidden';
            hiddenUpdate.name = 'update_objetivo';
            hiddenUpdate.value = '1';

            form.appendChild(hiddenId);
            form.appendChild(hiddenDesc);
            form.appendChild(hiddenUpdate);
            form.appendChild(saveButton);

            descripcionElement.innerHTML = '';
            descripcionElement.appendChild(input);
            
            const actionsContainer = this.parentElement;
            actionsContainer.innerHTML = '';
            actionsContainer.appendChild(form);

            saveButton.addEventListener('click', function(e) {
                e.preventDefault();
                hiddenDesc.value = input.value;
                form.submit();
            });
        });
    });
});
</script>

<?php if (strpos($mensaje, 'alert-success') !== false || strpos($mensaje, 'alert-info') !== false): ?>
<script>
    // Recargar la página después de 3 segundos para mostrar cambios a otros colaboradores
    setTimeout(function() {
        window.location.reload();
    }, 3000);
</script>
<?php endif; ?>

<?php
require_once 'includes/footer.php';
$mysqli->close();
?>