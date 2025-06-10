<?php
session_start();
require_once 'conexion.php';

$mensaje = '';
$tipo_mensaje = '';

// Verificar si el usuario ya está logueado
if (isset($_SESSION['id_usuario'])) {
    if ($_SESSION['rol'] === 'admin') {
        header('Location: indexAdmin.php');
    } else {
        header('Location: indexEmpleado.php');
    }
    exit;
}
// En tu código PHP, modifica la limpieza del RUT:
    function limpiarDatos($dato) {
        $dato = trim($dato);
        $dato = stripslashes($dato);
        $dato = htmlspecialchars($dato);
        
        // Limpiar RUT específicamente
        if (strpos($dato, 'rut') !== false) { // Asumiendo que es el campo RUT
            $dato = str_replace(['.', '-'], '', $dato);
        }
        
        return $dato;
    }

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = limpiarDatos($_POST['email']);
    $password = $_POST['password'];

    // Validar datos
    if (empty($correo) || empty($password)) {
        $mensaje = 'Todos los campos son obligatorios.';
        $tipo_mensaje = 'error';
    } else {
        // Buscar el usuario en la base de datos
        $sql = "SELECT id_usuario, nombre, contraseña, rol, estado FROM usuarios WHERE correo = '$correo'";
        $resultado = $conexion->query($sql);
        
        if ($resultado && $resultado->num_rows > 0) {
            $usuario = $resultado->fetch_assoc();
            
            // Verificar la contraseña y el estado del usuario
            if (password_verify($password, $usuario['contraseña'])) {
                if ($usuario['estado'] == 1) {
                    // Iniciar sesión
                    $_SESSION['id_usuario'] = $usuario['id_usuario'];
                    $_SESSION['nombre'] = $usuario['nombre'];
                    $_SESSION['rol'] = $usuario['rol'];
                    
                    // Redirigir según el rol
                    if ($usuario['rol'] === 'admin') {
                        header('Location: indexAdmin.php');
                    } else {
                        header('Location: indexEmpleado.php');
                    }
                    exit;
                } else {
                    $mensaje = 'Tu cuenta está desactivada. Contacta al administrador.';
                    $tipo_mensaje = 'error';
                }
            } else {
                $mensaje = 'Correo o contraseña incorrectos.';
                $tipo_mensaje = 'error';
            }
        } else {
            $mensaje = 'Correo o contraseña incorrectos.';
            $tipo_mensaje = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de Sesión</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="login-container">
        <div class="logo-circle">
            <img src="img/logo empresa.png" alt="Logo de la empresa">
        </div>

        <h1>Iniciar Sesión</h1>

        <?php if (!empty($mensaje)): ?>
            <div style="padding: 10px; margin-bottom: 15px; border-radius: 5px; 
                <?php echo $tipo_mensaje === 'error' ? 'background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;' : 'background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb;'; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <div class="input-group">
                <input type="email" name="email" placeholder="nombre@ejemplo.com" required>
            </div>

            <div class="input-group">
                <input type="password" id="password" name="password" placeholder="Contraseña" required>
                
            </div>

            <button type="submit" class="login-btn">Ingresar</button>

            <!-- Modificar el enlace para que apunte al nuevo archivo registro.php -->
            <?php if (!isset($_SESSION['id_usuario'])): ?>
                <a href="crearcuenta.php" class="create-account">Crear cuenta</a>
            <?php endif; ?>
        </form>
    </div>

    <script>
        // Mostrar/ocultar contraseña
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this;
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    </script>
</body>
</html>