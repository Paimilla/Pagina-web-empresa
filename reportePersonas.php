<?php

session_start();
require_once 'includes/config.php';
require_once 'includes/funciones.php';

// Establecer valores por defecto


// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    header("Location: inicio.php");
    exit();
}
$id_usuario = $_SESSION['id_usuario'];

$usuario = obtener_usuario($id_usuario);

$es_admin = isset($usuario['rol']) && (strtolower($usuario['rol']) === 'admin' || strtolower($usuario['rol']) === 'administrator');
$titulo_pagina = $es_admin ? 'Panel de Administrador' : 'Panel de Empleado';
$foto_perfil = isset($usuario['foto_perfil']) && !empty($usuario['foto_perfil']) ? $usuario['foto_perfil'] : 'assets/default-profile.jpg';
$nombre_usuario = isset($usuario['nombre']) && !empty($usuario['nombre']) ? $usuario['nombre'] : 'Usuario';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personas en Instalación</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/styleIndex.css">
    <link rel="stylesheet" href="css/personas_presentes.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
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

    <!-- Contenido principal -->
    <main class="main-content">
        <div class="back-button" id="backButton">
            <i class="fas fa-arrow-left"></i>
        </div>
        <div class="reporte-container">
            <h2 class="reporte-titulo">Personal Presente</h2>
            
            <!-- Resumen estadístico -->
            <div class="resumen-asistencia">
                <div class="resumen-header">
                    <h3>Resumen Actual</h3>
                    <div class="resumen-fecha">
                        <span id="fecha-actual"></span>
                        <i class="fas fa-calendar-alt calendar-icon" id="calendarBtn"></i>
                    </div>
                </div>
                
                <div class="resumen-estadisticas">
                    <div class="estadistica-item">
                        <div class="estadistica-icono">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="estadistica-valor" id="total-presentes"></div>
                        <div class="estadistica-label">Presentes</div>
                    </div>
                    <div class="estadistica-item">
                        <div class="estadistica-icono">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="estadistica-valor" id="promedio-tiempo"></div>
                        <div class="estadistica-label">Promedio</div>
                    </div>
                </div>
            </div>
            
            <!-- Lista de empleados presentes -->
            <div class="lista-empleados" id="listaEmpleados">
            
            </div>
        </div>
    </main>

    <!-- Modal para filtro de fecha -->
    <div class="modal-fecha" id="modalFecha">
        <div class="modal-contenido">
            <div class="modal-header">
                <h3>Seleccionar Fecha</h3>
                <button class="modal-cerrar" id="cerrarModal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="date" class="fecha-input" id="fechaInput">
            </div>
            <div class="modal-footer">
                <button class="modal-btn secundario" id="cancelarFecha">Cancelar</button>
                <button class="modal-btn primario" id="aplicarFecha">Aplicar</button>
            </div>
        </div>
    </div>


    
    <script type="module" src="scripts/main.js"></script>
    
    <script src="scripts/personas_presentes.js"></script>
    <script src="scripts/cargarConfiguracion.js"></script>
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

    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js"></script>
</body>
</html>