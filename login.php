<?php
session_start();

if (isset($_SESSION['id_usuario'])) {
    header('Location: dashboard.php');
    exit();
}

require_once 'includes/db_connection.php';
require_once 'includes/header.php';

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $mysqli->prepare("SELECT id, nombre, password FROM usuario WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();

        if ($password === $usuario['password']) {
            $_SESSION['id_usuario'] = $usuario['id'];
            $_SESSION['nombre'] = $usuario['nombre'];
            
            header('Location: dashboard.php');
            exit();
        } else {
            $mensaje = '<div class="alert alert-danger">La contraseña es incorrecta.</div>';
        }
    } else {
        $mensaje = '<div class="alert alert-danger">No se encontró un usuario con ese correo electrónico.</div>';
    }
    $stmt->close();
}
?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card bg-dark text-white border-secondary">
            <div class="card-header">
                <h3>Iniciar Sesión</h3>
            </div>
            <div class="card-body">
                <?php echo $mensaje; ?>
                <form action="login.php" method="POST">
                    </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>