<?php
    // Iniciar sesión
    session_start();
   
    require_once 'conexion.php';
    require_once 'includes/config.php';
    require_once 'includes/funciones.php';

    // Verificar si el usuario está logueado
    if (!isset($_SESSION['id_usuario'])) {
        header("Location: inicio.php");
        exit();
    }
    
    $id_usuario = $_SESSION['id_usuario'];

    // Intentar obtener usuario con la función existente primero
    if (function_exists('obtener_usuario')) {
        $usuario = obtener_usuario($id_usuario);
    } else {
        $usuario = [];
    }
    
    // Si no tenemos datos completos del usuario, consultarlos directamente
    if (empty($usuario) || !isset($usuario['nombre']) || !isset($usuario['rut'])) {
        // Consultar datos del usuario
        $query = "SELECT id_usuario, nombre, rut, correo, rol, foto_perfil, estado FROM usuarios WHERE id_usuario = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $usuario = $result->fetch_assoc();
        } else {
            // Si no se encuentra el usuario, redirigir
            $_SESSION['mensaje'] = "No se pudo encontrar la información del usuario.";
            $_SESSION['tipo_mensaje'] = "danger";
            header('Location: inicio.php');
            exit;
        }
    }

    $usuario = obtener_usuario($id_usuario) ?: [];

    // Si la foto está en la base de datos como base64
    if (!empty($usuario['foto_perfil']) && !filter_var($usuario['foto_perfil'], FILTER_VALIDATE_URL)) {
        // Asumimos que está almacenada como base64
        $foto_perfil = 'data:image/jpeg;base64,' . $usuario['foto_perfil'];
    } else {
        // Si no, usar la imagen por defecto
        $foto_perfil = isset($usuario['foto_perfil']) && !empty($usuario['foto_perfil']) ? 
                    $usuario['foto_perfil'] : 'img/default-profile.png';
    }

    // Establecer valores y variables para la página
    $es_admin = isset($usuario['rol']) && (strtolower($usuario['rol']) === 'admin' || strtolower($usuario['rol']) === 'administrator');
    $titulo_pagina = "Editar Perfil";
    $foto_perfil = isset($usuario['foto_perfil']) && !empty($usuario['foto_perfil']) ? $usuario['foto_perfil'] : 'img/default-profile.png';
    $nombre_usuario = isset($usuario['nombre']) && !empty($usuario['nombre']) ? $usuario['nombre'] : 'Usuario';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil - Control de Asistencia</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <link rel="stylesheet" href="css/styleIndex.css">
    <link rel="stylesheet" href="css/entrada_salida.css">
    <style>
        /* Estilos adicionales para la página de editar perfil */
        .profile-picture-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid var(--color-primary);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .profile-picture:hover {
            opacity: 0.8;
        }
        
        .change-photo-text {
            margin-top: 10px;
            color: var(--color-primary);
            font-weight: bold;
            cursor: pointer;
        }
        
        #fileInput {
            display: none;
        }
        
        .form-container {
            max-width: 600px;
            margin: 10px auto;
            margin-bottom: 60px;
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);

        }
        
        .form-title {
            color: var(--color-primary);
            text-align: center;
            margin-bottom: 30px;
        }
        
        .btn-save {
            background-color: var(--color-primary);
            color: white;
            padding: 10px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        
        .btn-save:hover {
            background-color: #4a7099;
            color: white;
        }
        
        .error-message {
            color: #dc3545;
            font-size: 14px;
            margin-top: 5px;
            display: none;
        }
        
        .error .error-message {
            display: block;
        }
        
        .error input {
            border-color: #dc3545;
        }
        
        .alert {
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <?php 
    // Incluir componentes de manera más robusta
    $components = ['header.php', 'mobile_menu.php', 'desktop_menu.php', 'bottom_nav.php'];
    $component_paths = ['components/', '', 'includes/components/'];
    
    foreach ($components as $component) {
        $included = false;
        foreach ($component_paths as $path) {
            $component_file = $path . $component;
            if (file_exists($component_file)) {
                try {
                    include $component_file;
                    $included = true;
                    break;
                } catch (Exception $e) {
                    error_log("Error al incluir el componente $component_file: " . $e->getMessage());
                }
            }
        }
        if (!$included) {
            // Solo registrar, no mostrar al usuario
            error_log("No se pudo incluir el componente $component");
        }
    }
    ?>

    <main class="main-content">
        <div class="form-container">
            <div class="back-button" id="backButton"><i class="fas fa-arrow-left"></i></div>
            <h2 class="form-title">Editar Perfil</h2>
            
            <?php if (isset($_SESSION['mensaje'])): ?>
                <div class="alert alert-<?= $_SESSION['tipo_mensaje'] ?> alert-dismissible fade show">
                    <?= htmlspecialchars($_SESSION['mensaje']) ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php 
                // Limpiar el mensaje después de mostrarlo
                unset($_SESSION['mensaje']);
                unset($_SESSION['tipo_mensaje']);
                ?>
            <?php endif; ?>

            <form id="editProfileForm" action="actualizar_perfil.php" method="POST" enctype="multipart/form-data">
                <div class="profile-picture-container">
                    <input type="file" id="fileInput" name="foto_perfil" accept="image/*">
                    <?php 
                    // Fix para mostrar correctamente la imagen del usuario
                    if (!empty($usuario['foto_perfil'])) {
                        // Si ya es una cadena base64 completa (incluye el prefijo data:image)
                        if (strpos($usuario['foto_perfil'], 'data:image') === 0) {
                            echo '<img src="' . htmlspecialchars($usuario['foto_perfil']) . '" alt="Foto de perfil" class="profile-picture" id="profilePicture">';
                        } 
                        // Si es solo el código base64 sin el prefijo
                        elseif (strpos($usuario['foto_perfil'], 'base64') !== false || preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $usuario['foto_perfil'])) {
                            echo '<img src="data:image/jpeg;base64,' . htmlspecialchars($usuario['foto_perfil']) . '" alt="Foto de perfil" class="profile-picture" id="profilePicture">';
                        } 
                        // Si es una ruta de archivo
                        else {
                            echo '<img src="' . htmlspecialchars($usuario['foto_perfil']) . '" alt="Foto de perfil" class="profile-picture" id="profilePicture">';
                        }
                    } else {
                        echo '<img src="assets/default-profile.jpg" alt="Foto de perfil" class="profile-picture" id="profilePicture">';
                    }
                    ?>
                    <span class="change-photo-text">Cambiar foto</span>
                </div>

               

                <div class="form-group">
                    <label for="nombre"><i class="fas fa-user mr-2"></i>Nombre Completo</label>
                    <input type="text" class="form-control form-control-lg" id="nombre" name="nombre" 
                           placeholder="Ingrese su nombre completo" value="<?= htmlspecialchars($usuario['nombre']) ?>" required>
                    <span class="error-message">El nombre es requerido</span>
                </div>

                <div class="form-group">
                    <label for="rut"><i class="fas fa-id-card mr-2"></i>RUT</label>
                    <input type="text" class="form-control form-control-lg" id="rut" name="rut" 
                           placeholder="Ej: 12.345.678-9" value="<?= htmlspecialchars($usuario['rut']) ?>" required>
                    <span class="error-message">El RUT no es válido</span>
                </div>

                <!-- Campo oculto para el token CSRF -->
                <input type="hidden" name="csrf_token" value="<?= isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : bin2hex(random_bytes(32)) ?>">
                <?php if (!isset($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); ?>

                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-save">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </main>
    <script src="scripts/cargarConfiguracion.js"></script>
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
    
    <!-- Cargar scripts asegurando que existan -->
    <?php foreach(['scripts/validaciones.js', 'scripts/iniciarVolver.js'] as $script): ?>
        <?php if(file_exists($script)): ?>
            <script src="<?= htmlspecialchars($script) ?>"></script>
        <?php else: ?>
            <script>console.warn("No se pudo cargar el script: <?= htmlspecialchars($script) ?>");</script>
        <?php endif; ?>
    <?php endforeach; ?>

    <script>
        // Función para cargar la imagen seleccionada
        document.getElementById('fileInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validar tamaño (máximo 5MB)
                const maxSize = 5 * 1024 * 1024; // 5MB en bytes
                if (file.size > maxSize) {
                    alert('La imagen es demasiado grande. El tamaño máximo permitido es 5MB.');
                    this.value = ''; // Limpiar el input
                    return;
                }
                
                // Validar tipo (solo imágenes)
                if (!file.type.startsWith('image/')) {
                    alert('Por favor, seleccione un archivo de imagen válido.');
                    this.value = ''; // Limpiar el input
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('profilePicture').src = event.target.result;
                }
                reader.readAsDataURL(file);
            }
        });

        // Permitir hacer clic en la imagen o el texto para abrir el selector de archivos
        document.getElementById('profilePicture').addEventListener('click', function() {
            document.getElementById('fileInput').click();
        });

        document.querySelector('.change-photo-text').addEventListener('click', function() {
            document.getElementById('fileInput').click();
        });

        // Funciones auxiliares para validación
        function mostrarError(elemento, mensaje) {
            const formGroup = elemento.closest('.form-group');
            formGroup.classList.add('error');
            const errorMessage = formGroup.querySelector('.error-message');
            if (errorMessage) {
                errorMessage.textContent = mensaje;
                errorMessage.style.display = 'block';
            }
        }
        
        function limpiarError(elemento) {
            const formGroup = elemento.closest('.form-group');
            formGroup.classList.remove('error');
            const errorMessage = formGroup.querySelector('.error-message');
            if (errorMessage) {
                errorMessage.style.display = 'none';
            }
        }
        
        // Función para validar RUT (en caso de que no esté en validaciones.js)
        function validarRUT(rut) {
            // Eliminar puntos y guión
            rut = rut.replace(/\./g, '').replace('-', '');
            
            // Validar formato
            if (!/^[0-9]{7,8}[0-9kK]$/.test(rut)) {
                return false;
            }
            
            // Calcular dígito verificador
            const rutSinDv = rut.slice(0, -1);
            const dvIngresado = rut.slice(-1).toLowerCase();
            
            // Calcular dígito verificador
            let suma = 0;
            let multiplicador = 2;
            
            for (let i = rutSinDv.length - 1; i >= 0; i--) {
                suma += parseInt(rutSinDv.charAt(i)) * multiplicador;
                multiplicador = multiplicador === 7 ? 2 : multiplicador + 1;
            }
            
            const dvEsperado = 11 - (suma % 11);
            const dvCalculado = dvEsperado === 11 ? '0' : dvEsperado === 10 ? 'k' : dvEsperado.toString();
            
            return dvCalculado === dvIngresado;
        }

        // Validar formulario antes de enviar
        document.addEventListener('DOMContentLoaded', function() {
            // Intentar utilizar iniciarValidaciones si existe
            if (typeof iniciarValidaciones === 'function') {
                iniciarValidaciones();
            }
            
            const formulario = document.getElementById('editProfileForm');
            formulario.addEventListener('submit', function(e) {
                let esValido = true;
                
                // Validar nombre
                const nombre = document.getElementById('nombre');
                if (!nombre.value.trim()) {
                    mostrarError(nombre, 'El nombre es requerido');
                    esValido = false;
                } else {
                    limpiarError(nombre);
                }
                
                // Validar RUT
                const rut = document.getElementById('rut');
                if (!rut.value.trim()) {
                    mostrarError(rut, 'El RUT es requerido');
                    esValido = false;
                } else if (!validarRUT(rut.value)) {
                    mostrarError(rut, 'El RUT no es válido');
                    esValido = false;
                } else {
                    limpiarError(rut);
                }
                
                // Si no es válido, prevenir envío del formulario
                if (!esValido) {
                    e.preventDefault();
                }
            });
            
            
        });
    </script>
    <script>
        // Versión mejorada y simplificada
        document.addEventListener('DOMContentLoaded', function() {
            // Configurar el botón de retroceso
            const backButton = document.getElementById('backButton');
            if (backButton) {
                backButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Usar el rol directamente desde PHP
                    const rolUsuario = "<?php echo isset($_SESSION['rol']) ? strtolower(trim($_SESSION['rol'])) : 'empleado'; ?>";
                    const destino = rolUsuario === 'admin' ? 'indexAdmin.php' : 'indexEmpleado.php';
                    
                    console.log('Redirigiendo a:', destino);
                    window.location.href = destino;
                });
            }
        });
    </script>
</body>
</html>