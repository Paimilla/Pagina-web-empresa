<?php
// Iniciar sesión de manera segura
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación
if (!isset($_SESSION['id_usuario'])) {
    header('Location: inicio.php');
    exit();
}

// Verificar que el ID de usuario es válido
$id_usuario = $_SESSION['id_usuario'];
if (!is_numeric($id_usuario)) {
    die("Error: ID de usuario inválido");
}

// Cargar configuraciones
$config_path = file_exists('includes/config.php') ? 'includes/config.php' : 'config.php';
require_once $config_path;

// Verificar la conexión a la base de datos
if (!isset($conexion) || !$conexion) {
    die("Error: No se ha establecido la conexión a la base de datos");
}

// Cargar funciones
$funciones_path = file_exists('includes/funciones.php') ? 'includes/funciones.php' : 'funciones.php';
require_once $funciones_path;

// Obtener datos del usuario usando la función existente
$usuario = obtener_usuario($id_usuario);

// Verificar si se obtuvo el usuario correctamente
if (empty($usuario)) {
    die("Error: No se pudo cargar la información del usuario. 
         Verifica que el usuario con ID $id_usuario existe en la base de datos.");
}

// Establecer valores por defecto
$es_admin = isset($usuario['rol']) && (strtolower($usuario['rol']) === 'admin' || strtolower($usuario['rol']) === 'administrator');
$titulo_pagina = $es_admin ? 'Panel de Administrador' : 'Panel de Empleado';
$foto_perfil = isset($usuario['foto_perfil']) && !empty($usuario['foto_perfil']) ? $usuario['foto_perfil'] : 'assets/default-profile.jpg';
$nombre_usuario = isset($usuario['nombre']) && !empty($usuario['nombre']) ? $usuario['nombre'] : 'Usuario';

// Crear un arreglo normalizado para añadir a la sesión si no existe
if (!isset($_SESSION['usuario'])) {
    $_SESSION['usuario'] = [
        'id' => $id_usuario,
        'nombre' => $nombre_usuario,
        'rol' => $es_admin ? 'admin' : 'empleado',
        'foto_perfil' => $foto_perfil
    ];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - Sistema de Control de Asistencia</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/styleIndex.css">
    <style>
        /* Estilos específicos para la página de configuración */
        .config-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .config-section {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .config-title {
            color: var(--color-primary);
            font-size: 1.3rem;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .config-option {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f5f5f5;
        }
        
        .config-option:last-child {
            border-bottom: none;
        }
        
        .option-info {
            flex: 1;
        }
        
        .option-label {
            font-weight: 600;
            color: var(--color-text-secondary);
            margin-bottom: 5px;
        }
        
        .option-description {
            font-size: 0.85rem;
            color: #666;
        }
        
        /* Estilos para switches personalizados */
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
            margin-left: 15px;
        }
        
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background-color: var(--color-primary);
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        
        /* Estilos para selectores de tema */
        .theme-selector {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .theme-option {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.3s;
        }
        
        .theme-option:hover {
            transform: scale(1.1);
        }
        
        .theme-option.active {
            border-color: var(--color-primary);
            transform: scale(1.1);
        }
        
        /* Colores de temas predefinidos */
        .theme-default {
            background: linear-gradient(135deg, #5b88b2 50%, #4a7099 50%);
        }
        
        .theme-dark {
            background: linear-gradient(135deg, #333 50%, #222 50%);
        }
        
        .theme-green {
            background: linear-gradient(135deg, #4CAF50 50%, #388E3C 50%);
        }
        
        .theme-purple {
            background: linear-gradient(135deg, #9C27B0 50%, #7B1FA2 50%);
        }
        
        /* Estilos para el botón de guardar */
        .save-btn {
            background-color: var(--color-primary);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
            display: block;
            margin: 30px auto 0;
        }
        
        .save-btn:hover {
            background-color: #4a7099;
        }
        
        /* Versión tablet */
        @media (min-width: 768px) {
            .config-container {
                padding: 30px;
            }
            
            .config-section {
                padding: 25px;
            }
        }
    </style>
</head>
<body>
    <?php 
    // Intentar incluir los componentes, con mensajes de error en caso de fallo
    $components = ['header.php', 'mobile_menu.php', 'desktop_menu.php', 'bottom_nav.php'];
    $component_paths = ['components/', '', 'includes/components/'];
    
    foreach ($components as $component) {
        $included = false;
        foreach ($component_paths as $path) {
            if (file_exists($path . $component)) {
                include $path . $component;
                $included = true;
                break;
            }
        }
        if (!$included) {
            echo "<p class='alert alert-warning'>Advertencia: No se pudo incluir el componente $component</p>";
        }
    }
    ?>
    
    <main class="main-content">
        <div class="back-button" id="backButton"><i class="fas fa-arrow-left"></i></div>
        <div class="config-container">
            <!-- Sección de Apariencia -->
            <div class="config-section">
                <h3 class="config-title"><i class="fas fa-palette mr-2"></i>Apariencia</h3>
                
                <div class="config-option">
                    <div class="option-info">
                        <div class="option-label">Tema de la aplicación</div>
                        <div class="option-description">Personaliza los colores principales del sistema</div>
                        <div class="theme-selector">
                            <div class="theme-option theme-default active" data-theme="default"></div>
                            <div class="theme-option theme-dark" data-theme="dark"></div>
                            <div class="theme-option theme-green" data-theme="green"></div>
                            <div class="theme-option theme-purple" data-theme="purple"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sección de Notificaciones -->
            <div class="config-section">
                <h3 class="config-title"><i class="fas fa-bell mr-2"></i>Notificaciones</h3>
                
                <div class="config-option">
                    <div class="option-info">
                        <div class="option-label">Notificaciones por email</div>
                        <div class="option-description">Recibir resúmenes diarios y alertas importantes por correo</div>
                    </div>
                    <label class="switch">
                        <input type="checkbox" id="emailNotifications" checked>
                        <span class="slider"></span>
                    </label>
                </div>
            </div>
            
            <button class="save-btn" id="saveSettings"><i class="fas fa-save mr-2"></i>Guardar Cambios</button>
        </div>

        
    </main>
    <script type="module" src="scripts/main.js"></script>
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js"></script>
    
    <?php 
    // Incluir los scripts solo si existen
    $scripts = ['cargarConfiguracion.js', 'iniciarVolver.js', 'main.js'];
    foreach ($scripts as $script) {
        if (file_exists('scripts/' . $script)) {
            echo '<script' . ($script == 'main.js' ? ' type="module"' : '') . ' src="scripts/' . $script . '"></script>';
        }
    }
    ?>
    
    <script>
        // Solo inicializar el botón de volver si la función existe
        if (typeof iniciarVolver === 'function') {
            iniciarVolver('backButton', '<?php echo $es_admin ? "indexAdmin.php" : "indexEmpleado.php"; ?>');
        } else {
            // Fallback manual si la función no existe
            document.getElementById('backButton').addEventListener('click', function() {
                window.location.href = '<?php echo $es_admin ? "indexAdmin.php" : "indexEmpleado.php"; ?>';
            });
        }
    </script>
</body>
</html>