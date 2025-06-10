<?php
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
    <title>Ayuda - Control de Asistencia</title> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    
    <link rel="stylesheet" href="css/styleIndex.css">
    <style>
        /* Estilos generales y de perfil (mantenidos de tu código) */
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
            border: 5px solid var(--color-primary, #5B86B2); /* Default color si no está definido */
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .profile-picture:hover { opacity: 0.8; }
        .change-photo-text { margin-top: 10px; color: var(--color-primary, #5B86B2); font-weight: bold; cursor: pointer; }
        #fileInput { display: none; }
        .form-container { max-width: 600px; margin: 0 auto; background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); }
        .form-title { color: var(--color-text, #5B86B2); text-align: center; margin-bottom: 30px; }
        .btn-save { background-color: var(--color-primary, #5B86B2); color: white; padding: 10px 30px; border: none; border-radius: 5px; font-size: 16px; transition: background-color 0.3s; }
        .btn-save:hover { background-color: #4a7099; color: white; }
        .error-message { color: #dc3545; font-size: 14px; margin-top: 5px; display: none; }
        .error .error-message { display: block; }
        .error input { border-color: #dc3545; }

        /* --- Estilos Mejorados para el Acordeón --- */
       
        

        .accordion-item {
           /* Se quita el borde individual, se usará sombra y borde en el título */
           margin-bottom: 8px; /* Espacio entre items */
           border-radius: 5px; /* Bordes redondeados */
           box-shadow: 0 1px 3px rgba(0,0,0,0.1); /* Sombra sutil */
           overflow: hidden; /* Para que el contenido no se salga del borde redondeado */
           background-color: #fff; /* Fondo blanco por defecto */
        }

        .accordion-title { /* Estilo aplicado a <summary> */
           background-color: var(--color-primary); /* Gris claro Bootstrap */
           padding: 1rem 1.25rem; /* Padding Bootstrap (un poco más grande) */
           cursor: pointer;
           font-weight: 600; /* Un poco más grueso */
           display: flex; /* Para alinear título e icono */
           justify-content: space-between; /* Pone espacio entre texto e icono */
           align-items: center; /* Centra verticalmente */
           width: 100%;
           text-align: left;
           border: none;
           outline: none;
           transition: background-color 0.3s ease;
           border-bottom: 1px solid #dee2e6; /* Línea divisoria sutil */
           list-style: none; /* Quita el marcador por defecto de summary */
        }

        .accordion-title::-webkit-details-marker {
             display: none; /* Oculta marcador en Chrome/Safari */
        }

        .accordion-title:hover {
           background-color: var(--color-secondary); /* Gris un poco más oscuro al pasar el mouse */
        }

        details[open] > .accordion-title {
           /* Opcional: cambiar fondo si está abierto */
           /* background-color: #e2e6ea; */
           border-bottom: 1px solid transparent; /* Oculta borde inferior cuando está abierto */
        }

        .accordion-icon {
            font-size: 0.9em; /* Tamaño del icono */
            color: var(--color-primary); /* Color gris Bootstrap */
            transition: transform 0.3s ease; /* Transición para la rotación */
        }

        details[open] .accordion-icon {
            transform: rotate(180deg); /* Rota el icono al abrir */
        }

        .accordion-content {
            padding: 0 1.25rem; /* Padding horizontal inicial */
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out, padding 0.3s ease-out;
            background-color: var(--color-primary); /* Fondo del contenido */
            line-height: 1.6; /* Espaciado de línea para mejor lectura */
            color: var(--color-text);
        }

        .accordion-content p,
        .accordion-content ul {
            margin-top: 10px;
            margin-bottom: 15px; /* Más espacio al final del párrafo/lista */
        }
         .accordion-content ul {
             padding-left: 20px; /* Indentación estándar para listas */
         }

        details[open] .accordion-content {
            max-height: 600px; /* Altura máxima suficiente (ajustar si es necesario) */
            padding: 1rem 1.25rem; /* Padding completo cuando está abierto */
        }
        /* --- Fin Estilos Acordeón --- */

        /* Asegurar que el contenido principal tenga padding respecto a los menús */
        
        /* Versión desktop grande (1200px en adelante) */
        @media (min-width: 1200px) {
            .main-content {
                padding-left: 10px; /* Ajustar según el ancho de tu menú desktop */
            }
        }

        /* Versión pantallas extra grandes (1600px en adelante) */
        @media (min-width: 1600px) {
            .main-content {
                padding-left: 240px; /* Ajustar según el ancho de tu menú desktop */
            }
        }

        .back-button {
            position: absolute;
            color: var(--color-primary);
            border: none;
            border-radius: 50%;
            padding: 10px;
            cursor: pointer;
            font-size: 1.5rem;
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
        <div class="container mt-4"> 
            <h2 class="form-title">Centro de Ayuda - <?php echo $es_admin ? 'Administrador' : 'Empleado'; ?></h2>

            <div class="accordion-container">
                <!-- Contenido común para ambos roles -->
                <details class="accordion-item">
                    <summary class="accordion-title">
                        Iniciar Sesión
                        <i class="fas fa-chevron-down accordion-icon"></i>
                    </summary>
                    <div class="accordion-content">
                        <p>Para acceder a la aplicación, ingresa tu <strong>Usuario</strong> y <strong>Contraseña</strong> en los campos correspondientes en la pantalla inicial. Luego, presiona el botón <strong>Ingresar</strong>. Si olvidaste tu contraseña, utiliza el enlace <strong>Olvidé mi contraseña</strong>.</p>
                    </div>
                </details>

                <?php if ($es_admin): ?>
                    <!-- Contenido solo para administradores -->
                    <details class="accordion-item">
                        <summary class="accordion-title">
                            Menú Administrador: Vista Principal
                            <i class="fas fa-chevron-down accordion-icon"></i>
                        </summary>
                        <div class="accordion-content">
                            <p>Al iniciar sesión como administrador, verás un panel principal que incluye:</p>
                            <ul>
                                <li>Un <strong>Slider</strong> con información o noticias relevantes.</li>
                                <li>Sección <strong>Presentes</strong>: Muestra la cantidad de personas presentes en una fecha específica.</li>
                                <li>Sección <strong>Jornada Finalizada</strong>: Muestra la cantidad de personas que finalizaron su jornada en una fecha específica.</li>
                                <li><strong>Reportes</strong>: Accesos rápidos a "Reporte de ingresos y salidas" y "Reporte de personas que se encuentran en las dependencias".</li>
                                <li><strong>Barra de Navegación Inferior</strong>: Contiene iconos para acceder a diferentes secciones (ej. Inicio, Usuarios, Búsqueda, Notificaciones).</li>
                            </ul>
                        </div>
                    </details>

                    <details class="accordion-item">
                        <summary class="accordion-title">
                            Menú Administrador: Ver Asistencia
                            <i class="fas fa-chevron-down accordion-icon"></i>
                        </summary>
                        <div class="accordion-content">
                            <p>Desde el menú de administrador, puedes acceder a vistas detalladas:</p>
                            <ul>
                                <li><strong>Presentes</strong>: Lista las personas que están actualmente en las instalaciones.</li>
                                <li><strong>Asistencia</strong>: Muestra un registro detallado de la asistencia.</li>
                            </ul>
                        </div>
                    </details>

                     <details class="accordion-item">
                        <summary class="accordion-title">
                            Sección de Registro y Estadísticas
                            <i class="fas fa-chevron-down accordion-icon"></i>
                        </summary>
                        <div class="accordion-content">
                            <p>En esta sección podrás visualizar y analizar tu actividad laboral:</p>
                            <ul>
                                <li><strong>Horario </strong>: 
                                    <ul>
                                        <li>Visualiza tu distribución de horas por día de la semana y mensual o de un filtro especifico</li>
                                        <li>Identifica tus días y horarios de trabajo habituales</li>
                                    </ul>
                                </li>
                                
                                
                                <li><strong>Estadísticas Clave</strong>:
                                    <ul>
                                        <li><strong>Promedio de horas trabajadas</strong>: Horas diarias/semanales promedio</li>
                                        <li><strong>Días trabajados</strong>: Total de días con registro de actividad</li>
                                    </ul>
                                </li>
                                
                                <li><strong>Filtros y Exportación</strong>:
                                    <ul>
                                        <li>Selecciona rangos de fechas específicos para analizar</li>
                                        <li>Compara períodos diferentes</li>
                                    </ul>
                                </li>
                            </ul>
                            
                            <p><strong>Nota</strong>: Los datos se actualizan automáticamente cada vez que registras una entrada/salida.</p>
                        </div>
                    </details>

                    

                <?php else: ?>
                    <!-- Contenido solo para empleados -->
                    <details class="accordion-item">
                        <summary class="accordion-title">
                            Menú Empleado: Vista Principal
                            <i class="fas fa-chevron-down accordion-icon"></i>
                        </summary>
                        <div class="accordion-content">
                            <p>La pantalla principal para empleados incluye:</p>
                            <ul>
                                <li>Un <strong>Slider</strong> informativo.</li>
                                <li><strong>Botón para hacer Ingreso automático</strong>: Marca tu hora de entrada rápidamente.</li>
                                <li><strong>Botón para marcar Salida automática</strong>: Marca tu hora de salida rápidamente.</li>
                            </ul>
                        </div>
                    </details>

                    <details class="accordion-item">
                        <summary class="accordion-title">
                            Marcar Ingreso/Salida Manualmente
                            <i class="fas fa-chevron-down accordion-icon"></i>
                        </summary>
                        <div class="accordion-content">
                            <p>Si no usas los botones automáticos:</p>
                            <ul>
                                <li><strong>Pantalla de Ingreso</strong>: Completa los datos solicitados y presiona <strong>Ingresar</strong>.</li>
                                <li><strong>Pantalla de Salida</strong>: Completa los datos y presiona <strong>Registrar Salida</strong>.</li>
                            </ul>
                        </div>
                    </details>

                    <details class="accordion-item">
                        <summary class="accordion-title">
                            Sección de Registro y Estadísticas
                            <i class="fas fa-chevron-down accordion-icon"></i>
                        </summary>
                        <div class="accordion-content">
                            <p>En esta sección podrás visualizar y analizar tu actividad laboral:</p>
                            <ul>
                                <li><strong>Horario </strong>: 
                                    <ul>
                                        <li>Visualiza tu distribución de horas por día de la semana y mensual o de un filtro especifico</li>
                                        <li>Identifica tus días y horarios de trabajo habituales</li>
                                    </ul>
                                </li>
                                
                                
                                <li><strong>Estadísticas Clave</strong>:
                                    <ul>
                                        <li><strong>Promedio de horas trabajadas</strong>: Horas diarias/semanales promedio</li>
                                        <li><strong>Días trabajados</strong>: Total de días con registro de actividad</li>
                                    </ul>
                                </li>
                                
                                <li><strong>Filtros y Exportación</strong>:
                                    <ul>
                                        <li>Selecciona rangos de fechas específicos para analizar</li>
                                        <li>Compara períodos diferentes</li>
                                    </ul>
                                </li>
                            </ul>
                            
                            <p><strong>Nota</strong>: Los datos se actualizan automáticamente cada vez que registras una entrada/salida.</p>
                        </div>
                    </details>
                <?php endif; ?>

                <!-- Contenido común para ambos roles -->
                <details class="accordion-item">
                    <summary class="accordion-title">
                        Opciones de Cuenta
                        <i class="fas fa-chevron-down accordion-icon"></i>
                    </summary>
                    <div class="accordion-content">
                        <p>Desde el menú de tu perfil puedes acceder a:</p>
                        <ul>
                            <li><strong>Editar Perfil</strong>: Modificar tu información personal.</li>
                            <li><strong>Ayuda</strong>: Acceder a guías de uso.</li>
                            <li><strong>Cerrar sesión</strong>: Salir de tu cuenta.</li>
                        </ul>
                    </div>
                </details>
            </div> 
        </div> 
    </main>
    


    
    <script src="scripts/cargarConfiguracion.js"></script>
    <script type="module" src="scripts/main.js"></script>
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>

    <script>
      // Pequeño script para asegurar que los iconos de reporte y menú funcionen si no están en iniciarVolver.js
      document.addEventListener('DOMContentLoaded', function() {
          const menuToggle = document.getElementById('menuToggle');
          const mobileMenu = document.getElementById('mobileMenu');
          const closeMenu = document.getElementById('closeMenu');

          if(menuToggle && mobileMenu) {
              menuToggle.addEventListener('click', () => mobileMenu.classList.add('active')); // Asume que 'active' muestra el menú
          }
          if(closeMenu && mobileMenu) {
              closeMenu.addEventListener('click', () => mobileMenu.classList.remove('active'));
          }

          // Lógica para el submenú de reportes en móvil (si aplica)
          const reportsToggle = document.getElementById('reportsToggle');
          const reportsSubmenu = document.getElementById('reportsSubmenu');
          if(reportsToggle && reportsSubmenu){
              reportsToggle.addEventListener('click', (e) => {
                  e.preventDefault(); // Prevenir navegación si es un enlace '#'
                  reportsSubmenu.classList.toggle('active'); // Asume que 'active' muestra el submenú
              });
          }

          // Lógica para submenú en Desktop (si aplica - expandir/colapsar)
          document.querySelectorAll('.desktop-menu .submenu-parent > a').forEach(item => {
              item.addEventListener('click', function(e) {
                  e.preventDefault();
                  let submenu = this.nextElementSibling;
                  if (submenu && submenu.classList.contains('submenu')) {
                      submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';
                      this.querySelector('.fa-chevron-down')?.classList.toggle('rotated'); // Opcional: rotar flecha
                  }
              });
          });

      });
    </script>
    <script src="scripts/iniciarVolver.js"></script> 
    <script src="scripts/cargarConfiguracion.js"></script>
    <script>
        iniciarVolver('backButton', 'indexEmpleado.html'); // Lógica para el botón de volver
    </script>
</body>
</html>