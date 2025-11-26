<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit();
}

require_once 'includes/db_connection.php';
require_once 'includes/header.php'; // Incluye Bootstrap CSS

$id_usuario_actual = $_SESSION['id_usuario'];
$nombre_usuario_actual = $_SESSION['nombre'];

// --- Lógica de Negocio (INTACTA) ---

$id_empresa_seleccionada = null;
if (isset($_SESSION['id_empresa_actual'])) {
    $id_empresa_seleccionada = $_SESSION['id_empresa_actual'];
}

$empresas = [];
$stmt = $mysqli->prepare("SELECT id, nombre_empresa FROM empresa WHERE id_usuario = ?");
$stmt->bind_param("i", $id_usuario_actual);
$stmt->execute();
$resultado_empresas = $stmt->get_result();
while ($fila = $resultado_empresas->fetch_assoc()) {
    $fila['tipo'] = 'propia';
    $empresas[] = $fila;
}
$stmt->close();

$stmt_colaborativas = $mysqli->prepare("
    SELECT e.id, e.nombre_empresa, u.nombre, u.apellido 
    FROM empresa e 
    JOIN colaboradores_empresa c ON e.id = c.id_empresa 
    JOIN usuario u ON e.id_usuario = u.id 
    WHERE c.id_usuario_colaborador = ? AND c.estado = 'activo'
");
$stmt_colaborativas->bind_param("i", $id_usuario_actual);
$stmt_colaborativas->execute();
$resultado_colaborativas = $stmt_colaborativas->get_result();
while ($fila = $resultado_colaborativas->fetch_assoc()) {
    $fila['tipo'] = 'colaborativa';
    $fila['propietario'] = $fila['nombre'] . ' ' . $fila['apellido'];
    $empresas[] = $fila;
}
$stmt_colaborativas->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['seleccionar_empresa_id'])) {
        $_SESSION['id_empresa_actual'] = $_POST['seleccionar_empresa_id'];
        header('Location: dashboard.php');
        exit();
    } elseif (isset($_POST['nueva_nombre_empresa'])) {
        $nueva_nombre_empresa = $_POST['nueva_nombre_empresa'];
        $stmt_insert = $mysqli->prepare("INSERT INTO empresa (id_usuario, nombre_empresa) VALUES (?, ?)");
        $stmt_insert->bind_param("is", $id_usuario_actual, $nueva_nombre_empresa);
        if ($stmt_insert->execute()) {
            $_SESSION['id_empresa_actual'] = $mysqli->insert_id;
            header('Location: dashboard.php');
            exit();
        } else {
            echo '<div class="alert alert-danger">Error al crear la nueva empresa.</div>';
        }
        $stmt_insert->close();
    }
}

$nombre_empresa_actual = "Selecciona un Proyecto";
$es_propietario_empresa = false;
$imagen_actual_db = '';

if ($id_empresa_seleccionada) {
    $stmt_info = $mysqli->prepare("SELECT nombre_empresa, id_usuario, imagen FROM empresa WHERE id = ?");
    $stmt_info->bind_param("i", $id_empresa_seleccionada);
    $stmt_info->execute();
    $stmt_info->bind_result($nombre_empresa_actual_db, $id_propietario_empresa, $img_db);
    $stmt_info->fetch();
    $stmt_info->close();
    if ($nombre_empresa_actual_db) {
        $nombre_empresa_actual = $nombre_empresa_actual_db;
        $es_propietario_empresa = ($id_propietario_empresa == $id_usuario_actual);
        $imagen_actual_db = $img_db;
    }
}
?>

<style>
    /* --- ESTILOS PRO+++ (DARK MODE EMPRESARIAL) --- */
    :root {
        --bg-dark: #0B1120;       /* Fondo ultra oscuro */
        --bg-card: #1E293B;       /* Fondo de tarjetas/módulos */
        --bg-card-hover: #334155; /* Hover de tarjetas */
        --brand-primary: #3B82F6; /* Azul brillante */
        --brand-accent: #F43F5E;  /* Rojo/Rosa neón */
        --brand-green: #10B981;   /* Verde éxito */
        --text-main: #F8FAFC;     /* Blanco/Gris muy claro */
        --text-muted: #94A3B8;    /* Gris medio */
        --glass-border: 1px solid rgba(255, 255, 255, 0.08);
        --shadow-glow: 0 0 20px rgba(59, 130, 246, 0.15);
    }

    body {
        background-color: var(--bg-dark);
        background-image: 
            radial-gradient(circle at 15% 50%, rgba(59, 130, 246, 0.08), transparent 25%), 
            radial-gradient(circle at 85% 30%, rgba(16, 185, 129, 0.05), transparent 25%);
        font-family: 'Inter', 'Segoe UI', sans-serif;
        color: var(--text-main);
        min-height: 100vh;
    }

    /* Navbar */
    .navbar-dashboard {
        background: rgba(15, 23, 42, 0.85) !important;
        backdrop-filter: blur(12px);
        border-bottom: var(--glass-border);
        padding: 1rem 0;
    }
    
    .navbar-brand {
        font-weight: 800;
        letter-spacing: -0.5px;
        color: white !important;
    }

    .navbar-text { color: var(--text-muted) !important; }
    .navbar-text strong { color: white !important; }

    /* Hero Banner (Decorativo Empresarial) */
    .intro-section {
        position: relative;
        height: 280px;
        border-radius: 24px;
        overflow: hidden;
        margin-bottom: -60px; /* Superposición con el contenido */
        box-shadow: 0 20px 40px rgba(0,0,0,0.4);
        border: var(--glass-border);
        background-color: var(--bg-card);
    }

    .intro-bg {
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        background-image: url('https://images.unsplash.com/photo-1497366216548-37526070297c?q=80&w=2301&auto=format&fit=crop');
        background-size: cover;
        background-position: center;
        filter: brightness(0.4) contrast(1.1);
    }

    .intro-content {
        position: relative;
        z-index: 2;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 0 3rem;
    }

    .intro-content h1 {
        font-size: 2.5rem;
        font-weight: 800;
        color: white;
        margin-bottom: 0.5rem;
        background: linear-gradient(to right, #fff, #cbd5e1);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .intro-content p {
        color: #cbd5e1;
        font-size: 1.1rem;
        max-width: 600px;
    }

    /* Contenedor Principal elevado */
    .main-content-wrapper {
        position: relative;
        z-index: 10;
        padding-top: 0;
    }

    /* Barra de Control Sticky */
    .sticky-top-actions {
        background: rgba(30, 41, 59, 0.8);
        backdrop-filter: blur(16px);
        border: var(--glass-border);
        border-radius: 16px;
        padding: 1rem 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }

    .current-project-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--text-muted);
        margin-bottom: 4px;
    }

    .company-current {
        font-size: 1.25rem;
        font-weight: 700;
        color: white;
    }

    /* Botones de Acción */
    .btn-action {
        padding: 8px 20px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.3s;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-primary-glow {
        background: var(--brand-primary);
        color: white;
        box-shadow: 0 0 15px rgba(59, 130, 246, 0.4);
    }
    .btn-primary-glow:hover {
        background: #2563EB;
        transform: translateY(-2px);
        box-shadow: 0 0 25px rgba(59, 130, 246, 0.6);
        color: white;
    }

    .btn-outline-glow {
        background: transparent;
        border: 1px solid rgba(255,255,255,0.2);
        color: white;
    }
    .btn-outline-glow:hover {
        border-color: white;
        background: rgba(255,255,255,0.05);
        color: white;
    }

    /* Sección de Logo (Restaurada y Estilizada) */
    .empresa-image-section {
        background: var(--bg-card);
        border: 1px dashed rgba(255,255,255,0.15);
        border-radius: 16px;
        padding: 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 3rem;
    }

    .logo-preview-container {
        width: 80px;
        height: 80px;
        background: #0f172a;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(255,255,255,0.1);
        overflow: hidden;
    }
    
    .logo-preview-container img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    /* --- TARJETAS DE MÓDULOS (DARK MODE) --- */
    .section-divider {
        display: flex;
        align-items: center;
        color: var(--text-muted);
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 2px;
        font-weight: 700;
        margin: 2rem 0 1.5rem 0;
    }
    .section-divider::after {
        content: '';
        flex: 1;
        height: 1px;
        background: linear-gradient(90deg, rgba(255,255,255,0.1), transparent);
        margin-left: 15px;
    }

    .module-card {
        display: flex;
        flex-direction: column;
        height: 100%;
        background: linear-gradient(145deg, var(--bg-card), #162032);
        border: var(--glass-border);
        border-radius: 16px;
        padding: 1.5rem;
        text-decoration: none !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .module-card:hover {
        transform: translateY(-5px);
        background: var(--bg-card-hover);
        border-color: rgba(255,255,255,0.3);
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    }

    /* Efecto borde brillante al hover */
    .module-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; width: 2px; height: 0;
        background: var(--brand-green);
        transition: height 0.3s ease;
    }
    .module-card:hover::before { height: 100%; }

    .module-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        background: rgba(255,255,255,0.05);
        color: var(--brand-primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 1rem;
        transition: all 0.3s;
    }

    .module-card:hover .module-icon {
        background: var(--brand-primary);
        color: white;
        box-shadow: 0 0 15px rgba(59, 130, 246, 0.5);
    }

    .module-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: white; /* Texto blanco */
        margin-bottom: 0.5rem;
    }

    .module-desc {
        font-size: 0.85rem;
        color: var(--text-muted);
        line-height: 1.5;
    }

    .module-number {
        position: absolute;
        top: 1rem; right: 1.5rem;
        font-size: 3rem;
        font-weight: 900;
        color: rgba(255,255,255,0.03);
        line-height: 1;
    }

    /* Tarjeta Resumen (Especial) */
    .summary-card {
        background: linear-gradient(135deg, #059669, #047857);
        color: white;
        border: none;
        box-shadow: 0 0 30px rgba(16, 185, 129, 0.2);
    }
    .summary-card:hover {
        background: linear-gradient(135deg, #10B981, #059669);
        box-shadow: 0 0 40px rgba(16, 185, 129, 0.4);
    }
    .summary-card .module-icon {
        background: rgba(255,255,255,0.2);
        color: white;
    }
    .summary-card .module-desc { color: rgba(255,255,255,0.8); }

    /* Modales Oscuros */
    .modal-content {
        background-color: #1E293B;
        color: white;
        border: 1px solid rgba(255,255,255,0.1);
    }
    .modal-header { border-bottom: 1px solid rgba(255,255,255,0.1); }
    .modal-footer { border-top: 1px solid rgba(255,255,255,0.1); }
    .btn-close { filter: invert(1); }
    
    .form-control, .form-select {
        background-color: #0F172A;
        border: 1px solid rgba(255,255,255,0.2);
        color: white;
    }
    .form-control:focus, .form-select:focus {
        background-color: #0F172A;
        border-color: var(--brand-primary);
        color: white;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
    }
</style>

<nav class="navbar navbar-expand-lg navbar-dashboard fixed-top">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">
            <i class="fas fa-layer-group me-2 text-primary"></i>
            NexStrategy<span class="text-white-50">-IT</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <i class="fas fa-bars text-white"></i>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav ms-auto align-items-center gap-3">
                <li class="nav-item">
                    <span class="navbar-text small">
                        Bienvenido, <strong><?php echo htmlspecialchars($nombre_usuario_actual); ?></strong>
                    </span>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="btn btn-outline-glow btn-sm rounded-pill px-3">
                        <i class="fas fa-power-off me-1"></i> Salir
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container pb-5" style="margin-top: 100px;">
    
    <div class="intro-section">
        <div class="intro-bg"></div>
        <div class="intro-content">
            <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25 mb-3 align-self-start">
                <i class="fas fa-rocket me-1"></i> Dashboard Ejecutivo
            </span>
            <h1>Estrategia Digital</h1>
            <p>Plataforma integral para el diseño, análisis y ejecución de planes estratégicos de tecnología.</p>
        </div>
    </div>

    <div class="main-content-wrapper">
        <div class="sticky-top-actions">
            <div class="flex-grow-1">
                <div class="current-project-label">Proyecto Activo</div>
                <div class="company-current text-truncate">
                    <?php echo htmlspecialchars($nombre_empresa_actual); ?>
                    <?php if ($es_propietario_empresa): ?>
                        <span class="badge bg-success ms-2" style="font-size: 0.4em; vertical-align: middle;">ADMIN</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="d-flex gap-2">
                <button class="btn-action btn-outline-glow" data-bs-toggle="modal" data-bs-target="#seleccionarEmpresaModal">
                    <i class="fas fa-exchange-alt"></i> Cambiar
                </button>
                <button class="btn-action btn-primary-glow" data-bs-toggle="modal" data-bs-target="#crearEmpresaModal">
                    <i class="fas fa-plus"></i> Nuevo Proyecto
                </button>
                <?php if ($es_propietario_empresa && $id_empresa_seleccionada): ?>
                    <a href="gestionar_colaboradores.php" class="btn-action btn-outline-glow text-decoration-none">
                        <i class="fas fa-users"></i> Equipo
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($es_propietario_empresa && $id_empresa_seleccionada): ?>
        <div class="empresa-image-section">
            <div class="d-flex align-items-center gap-3">
                <div class="logo-preview-container">
                    <?php if (!empty($imagen_actual_db)): ?>
                        <img src="uploads/empresa_images/<?php echo htmlspecialchars($imagen_actual_db); ?>" alt="Logo">
                    <?php else: ?>
                        <i class="fas fa-building text-muted fa-2x"></i>
                    <?php endif; ?>
                </div>
                <div>
                    <h5 class="text-white mb-1">Logo Corporativo</h5>
                    <p class="text-muted small mb-0">Aparecerá en el resumen ejecutivo y reportes PDF.</p>
                </div>
            </div>
            <div class="d-flex gap-2">
                <?php if (!empty($imagen_actual_db)): ?>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteImage()"><i class="fas fa-trash"></i></button>
                <?php endif; ?>
                <button class="btn btn-sm btn-outline-glow" data-bs-toggle="modal" data-bs-target="#uploadImageModal">
                    <i class="fas fa-upload me-2"></i>Subir Logo
                </button>
            </div>
        </div>
        <?php endif; ?>

        <div class="section-divider"><i class="fas fa-compass me-2 text-primary"></i> Fase 1: Definición</div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <a href="mision.php" class="module-card">
                    <div class="module-number">01</div>
                    <div class="module-icon"><i class="fas fa-flag"></i></div>
                    <h3 class="module-title">Misión</h3>
                    <p class="module-desc">Razón de ser y propósito fundamental de la organización.</p>
                </a>
            </div>
            <div class="col-md-6 col-lg-3">
                <a href="vision.php" class="module-card">
                    <div class="module-number">02</div>
                    <div class="module-icon"><i class="fas fa-eye"></i></div>
                    <h3 class="module-title">Visión</h3>
                    <p class="module-desc">Aspiración futura y dirección estratégica a largo plazo.</p>
                </a>
            </div>
            <div class="col-md-6 col-lg-3">
                <a href="valores.php" class="module-card">
                    <div class="module-number">03</div>
                    <div class="module-icon"><i class="fas fa-gem"></i></div>
                    <h3 class="module-title">Valores</h3>
                    <p class="module-desc">Principios éticos y cultura organizacional.</p>
                </a>
            </div>
            <div class="col-md-6 col-lg-3">
                <a href="objetivos.php" class="module-card">
                    <div class="module-number">04</div>
                    <div class="module-icon"><i class="fas fa-bullseye"></i></div>
                    <h3 class="module-title">Objetivos</h3>
                    <p class="module-desc">Metas cuantificables para medir el éxito.</p>
                </a>
            </div>
        </div>

        <div class="section-divider"><i class="fas fa-chart-pie me-2 text-warning"></i> Fase 2: Análisis</div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <a href="analisis_info.php" class="module-card">
                    <div class="module-number">05</div>
                    <div class="module-icon"><i class="fas fa-swatchbook"></i></div>
                    <h3 class="module-title">Análisis FODA</h3>
                    <p class="module-desc">Diagnóstico de Fortalezas, Oportunidades, Debilidades y Amenazas.</p>
                </a>
            </div>
            <div class="col-md-6 col-lg-4">
                <a href="cadena_valor.php" class="module-card">
                    <div class="module-number">06</div>
                    <div class="module-icon"><i class="fas fa-link"></i></div>
                    <h3 class="module-title">Cadena de Valor</h3>
                    <p class="module-desc">Actividades generadoras de valor y margen.</p>
                </a>
            </div>
            <div class="col-md-6 col-lg-4">
                <a href="matriz_bcg.php" class="module-card">
                    <div class="module-number">07</div>
                    <div class="module-icon"><i class="fas fa-th-large"></i></div>
                    <h3 class="module-title">Matriz BCG</h3>
                    <p class="module-desc">Cartera de productos: Crecimiento vs Participación.</p>
                </a>
            </div>
            <div class="col-md-6 col-lg-4">
                <a href="porter_5fuerzas.php" class="module-card">
                    <div class="module-number">08</div>
                    <div class="module-icon"><i class="fas fa-shield-alt"></i></div>
                    <h3 class="module-title">5 Fuerzas Porter</h3>
                    <p class="module-desc">Análisis de competitividad y entorno de mercado.</p>
                </a>
            </div>
            <div class="col-md-6 col-lg-4">
                <a href="analisis_pest.php" class="module-card">
                    <div class="module-number">09</div>
                    <div class="module-icon"><i class="fas fa-globe"></i></div>
                    <h3 class="module-title">Análisis PEST</h3>
                    <p class="module-desc">Factores Políticos, Económicos, Sociales y Tecnológicos.</p>
                </a>
            </div>
            <div class="col-md-6 col-lg-4">
                <a href="id_estrategias.php" class="module-card">
                    <div class="module-number">10</div>
                    <div class="module-icon"><i class="fas fa-chess-knight"></i></div>
                    <h3 class="module-title">Estrategias</h3>
                    <p class="module-desc">Cruce de variables para definir acciones estratégicas.</p>
                </a>
            </div>
            <div class="col-md-6 col-lg-4">
                <a href="matriz_came.php" class="module-card">
                    <div class="module-number">11</div>
                    <div class="module-icon"><i class="fas fa-random"></i></div>
                    <h3 class="module-title">Matriz CAME</h3>
                    <p class="module-desc">Corregir, Afrontar, Mantener y Explotar.</p>
                </a>
            </div>
        </div>

        <div class="section-divider"><i class="fas fa-file-signature me-2 text-success"></i> Fase 3: Entrega</div>
        <div class="row mb-5">
            <div class="col-12">
                <a href="resumen_plan.php" class="module-card summary-card flex-row align-items-center gap-4">
                    <div class="module-icon mb-0 text-success bg-white"><i class="fas fa-check"></i></div>
                    <div>
                        <h3 class="module-title mb-1">Resumen Ejecutivo Final</h3>
                        <p class="module-desc text-white-50 mb-0">Consolidación de todos los análisis en un reporte profesional listo para exportar.</p>
                    </div>
                    <div class="ms-auto text-white fs-4"><i class="fas fa-chevron-right"></i></div>
                </a>
            </div>
        </div>

    </div>
</div>

<div class="modal fade" id="seleccionarEmpresaModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Cambiar de Proyecto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php if (empty($empresas)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No hay proyectos disponibles.</p>
                    </div>
                <?php else: ?>
                    <form action="dashboard.php" method="POST">
                        <div class="mb-4">
                            <label class="form-label text-uppercase small text-muted fw-bold">Seleccione un proyecto</label>
                            <select class="form-select" name="seleccionar_empresa_id" required>
                                <?php foreach ($empresas as $emp): ?>
                                    <option value="<?php echo htmlspecialchars($emp['id']); ?>"
                                        <?php echo ($emp['id'] == $id_empresa_seleccionada) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($emp['nombre_empresa']); ?>
                                        <?php if ($emp['tipo'] === 'colaborativa'): ?>
                                            (Colaboración)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary-glow w-100 py-2">Cargar Dashboard</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="crearEmpresaModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Nuevo Proyecto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="dashboard.php" method="POST">
                    <div class="mb-4">
                        <label class="form-label text-uppercase small text-muted fw-bold">Nombre de la Organización</label>
                        <input type="text" class="form-control" name="nueva_nombre_empresa" placeholder="Ej. Tech Solutions Inc." required>
                    </div>
                    <button type="submit" class="btn btn-primary-glow w-100 py-2">Crear Proyecto</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="uploadImageModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Subir Logo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="uploadImageForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Archivo de Imagen</label>
                        <input type="file" class="form-control" id="empresa_image" name="empresa_image" accept="image/*" required>
                    </div>
                    <div id="imagePreview" class="mt-3 text-center p-3 border border-dashed rounded" style="display: none; border-color: rgba(255,255,255,0.1) !important;">
                        <img id="previewImg" src="" alt="Preview" style="max-height: 100px;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-glow" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-glow">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php'; 
$mysqli->close();
?>

<script>
// Preview de imagen
document.getElementById('empresa_image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewImg').src = e.target.result;
            document.getElementById('imagePreview').style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
});

// Subir imagen
document.getElementById('uploadImageForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData();
    const fileInput = document.getElementById('empresa_image');
    
    if (fileInput.files.length === 0) { alert('Por favor selecciona una imagen'); return; }
    
    formData.append('empresa_image', fileInput.files[0]);
    formData.append('id_empresa', <?php echo $id_empresa_seleccionada ?? 0; ?>);
    
    fetch('upload_empresa_image.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        if (data.success) { location.reload(); } else { alert('Error: ' + data.message); }
    })
    .catch(error => { console.error('Error:', error); alert('Error al subir la imagen'); });
});

// Eliminar imagen
function deleteImage() {
    if (confirm('¿Estás seguro de que quieres eliminar la imagen?')) {
        fetch('delete_empresa_image.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_empresa: <?php echo $id_empresa_seleccionada ?? 0; ?> })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) { location.reload(); } else { alert('Error: ' + data.message); }
        });
    }
}
</script>