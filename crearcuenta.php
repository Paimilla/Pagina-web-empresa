<?php
session_start();
require_once 'conexion.php';

// Si ya está logueado, redirigir según el rol
if (isset($_SESSION['id_usuario'])) {
    if ($_SESSION['rol'] === 'admin') {
        header('Location: crearcuenta.php');
    } else {
        header('Location: indexEmpleado.php');
    }
    exit;
}

$mensaje = '';
$tipo_mensaje = '';

// Función para limpiar datos (estaba faltando)
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
    $nombre = limpiarDatos($_POST['name']);
    $rut = limpiarDatos($_POST['rut']);
    $correo = limpiarDatos($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirmPassword'];
    $rol = $_POST['userType']; // Obtener el rol directamente del campo oculto

    // Validar datos
    if (empty($nombre) || empty($rut) || empty($correo) || empty($password) || empty($confirm_password)) {
        $mensaje = 'Todos los campos son obligatorios.';
        $tipo_mensaje = 'error';
    } elseif ($password !== $confirm_password) {
        $mensaje = 'Las contraseñas no coinciden.';
        $tipo_mensaje = 'error';
    } else {
        // Verificar si el RUT ya existe
        $sql_check_rut = "SELECT id_usuario FROM usuarios WHERE rut = ?";
        $stmt_rut = $conexion->prepare($sql_check_rut);
        if (!$stmt_rut) {
            $mensaje = 'Error en la preparación de la consulta: ' . $conexion->error;
            $tipo_mensaje = 'error';
        } else {
            $stmt_rut->bind_param("s", $rut);
            $stmt_rut->execute();
            $resultado_rut = $stmt_rut->get_result();
            
            // Verificar si el correo ya existe
            $sql_check_email = "SELECT id_usuario FROM usuarios WHERE correo = ?";
            $stmt_email = $conexion->prepare($sql_check_email);
            if (!$stmt_email) {
                $mensaje = 'Error en la preparación de la consulta: ' . $conexion->error;
                $tipo_mensaje = 'error';
            } else {
                $stmt_email->bind_param("s", $correo);
                $stmt_email->execute();
                $resultado_email = $stmt_email->get_result();
                
                if ($resultado_rut->num_rows > 0) {
                    $mensaje = 'El RUT ya está registrado en el sistema.';
                    $tipo_mensaje = 'error';
                } elseif ($resultado_email->num_rows > 0) {
                    $mensaje = 'El correo ya está registrado en el sistema.';
                    $tipo_mensaje = 'error';
                } else {
                    // Hash de la contraseña
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Procesar la foto de perfil si existe
                    $foto_perfil = null;
                    if (isset($_FILES['profilePhoto']) && $_FILES['profilePhoto']['error'] == 0) {
                        $foto_temp = $_FILES['profilePhoto']['tmp_name'];
                        $foto_perfil = base64_encode(file_get_contents($foto_temp));
                    }
                    
                    // Preparar la consulta según si hay foto o no
                    if ($foto_perfil !== null) {
                        $sql = "INSERT INTO usuarios (nombre, rut, correo, contraseña, rol, foto_perfil, estado) VALUES (?, ?, ?, ?, ?, ?, 1)";
                        $stmt = $conexion->prepare($sql);
                        if (!$stmt) {
                            $mensaje = 'Error en la preparación de la consulta: ' . $conexion->error;
                            $tipo_mensaje = 'error';
                        } else {
                            $stmt->bind_param("ssssss", $nombre, $rut, $correo, $password_hash, $rol, $foto_perfil);
                            if ($stmt->execute()) {
                                $mensaje = 'Usuario creado correctamente. Ahora puedes iniciar sesión.';
                                $tipo_mensaje = 'success';
                            } else {
                                $mensaje = 'Error al crear el usuario: ' . $stmt->error;
                                $tipo_mensaje = 'error';
                            }
                            $stmt->close();
                        }
                    } else {
                        $sql = "INSERT INTO usuarios (nombre, rut, correo, contraseña, rol, estado) VALUES (?, ?, ?, ?, ?, 1)";
                        $stmt = $conexion->prepare($sql);
                        if (!$stmt) {
                            $mensaje = 'Error en la preparación de la consulta: ' . $conexion->error;
                            $tipo_mensaje = 'error';
                        } else {
                            $stmt->bind_param("sssss", $nombre, $rut, $correo, $password_hash, $rol);
                            if ($stmt->execute()) {
                                $mensaje = 'Usuario creado correctamente. Ahora puedes iniciar sesión.';
                                $tipo_mensaje = 'success';
                            } else {
                                $mensaje = 'Error al crear el usuario: ' . $stmt->error;
                                $tipo_mensaje = 'error';
                            }
                            $stmt->close();
                        }
                    }
                }
                $stmt_email->close();
            }
            $stmt_rut->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrarse</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .mensaje {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        .mensaje.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .mensaje.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        /* Estilos para el selector de tipo de usuario */
        .user-type-selector {
            display: flex;
            margin-bottom: 20px;
            border-radius: 5px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .user-type {
            flex: 1;
            text-align: center;
            padding: 10px;
            cursor: pointer;
            background-color: #f0f0f0;
            transition: all 0.3s ease;
        }
        .user-type.active {
            background-color: #4CAF50;
            color: white;
        }
        .user-type:first-child {
            border-right: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="signup-container">
        <!-- Botón de volver -->
        <div class="back-button" id="backButton">
            <i class="fas fa-arrow-left"></i>
        </div>

        <div class="logo-circle">
            <img src="img/logo empresa.png" alt="">
        </div>

        <h1>Crear Cuenta</h1>

        <?php if (!empty($mensaje)): ?>
            <div class="mensaje <?php echo $tipo_mensaje; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" enctype="multipart/form-data">
            <!-- Selector de tipo de usuario -->
            <div class="user-type-selector">
                <div class="user-type active" id="adminType">Admin</div>
                <div class="user-type" id="employeeType">Empleado</div>
                <input type="hidden" name="userType" id="userTypeInput" value="admin">
            </div>
            <div class="input-group">
                <label for="name">Nombre Completo</label>
                <input type="text" id="name" name="name" placeholder="Ingrese su nombre completo" required>
            </div>

            <div class="input-group">
                <label for="rut">RUT</label>
                <input type="text" id="rut" name="rut" placeholder="Ingrese su RUT (ej: 12345678-9)" required>
            </div>

            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Ingrese su email" required>
            </div>

            <div class="input-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" placeholder="Ingrese su contraseña" required>
            </div>

            <div class="input-group">
                <label for="confirmPassword">Repetir Contraseña</label>
                <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Repita su contraseña" required>
            </div>

            <div class="file-upload">
                <div class="file-upload-preview">
                    <i class="fas fa-user" id="defaultIcon"></i>
                    <img id="imagePreview" alt="Vista previa de la imagen" style="display: none;">
                </div>
                <label for="profilePhoto" class="file-upload-label">
                    <i class="fas fa-camera"></i> Seleccionar Foto
                </label>
                <input type="file" id="profilePhoto" name="profilePhoto" class="file-upload-input" accept="image/*">
            </div>

            <button type="submit" class="signup-btn">Crear Cuenta</button>
            <p style="text-align: center; margin-top: 15px;">¿Ya tienes una cuenta? <a href="inicio.php">Iniciar sesión</a></p>
        </form>
    </div>
    <script src="scripts/iniciarSelectorTipo.js"></script>
    <script>
        // Mostrar vista previa de imagen
        document.getElementById('profilePhoto').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const imagePreview = document.getElementById('imagePreview');
                    const defaultIcon = document.getElementById('defaultIcon');
                    
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                    defaultIcon.style.display = 'none';
                }
                reader.readAsDataURL(file);
            }
        });
        
        // Botón volver
        document.getElementById('backButton').addEventListener('click', function() {
            window.location.href = 'inicio.php';
        });
        
        // Cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', function() {
            iniciarSelectorTipo('adminType', 'employeeType', 'userTypeInput');
        });
    </script>
    <script src="scripts/validaciones.js"></script>
</body>
</html>