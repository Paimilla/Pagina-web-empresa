<?php
// Encabezados para evitar caché
// Iniciar sesión una sola vez
session_start();
require_once 'conexion.php';
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



$rol = $usuario['rol'] ?? 'empleado'; 
$_SESSION['rol'] = $rol;


// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo_registro = 'entrada';
    $id_dependencia = 1;
    $fecha_hoy = date('Y-m-d');

    // Verificar si ya registró entrada hoy
   // Verificar si hay una entrada sin salida posterior
    $stmt = $conexion->prepare("
    SELECT COUNT(*) 
    FROM registros 
    WHERE id_usuario = ? 
    AND tipo_registro = 'entrada'
    AND NOT EXISTS (
        SELECT 1 FROM registros r2 
        WHERE r2.id_usuario = registros.id_usuario 
            AND r2.tipo_registro = 'salida' 
            AND r2.fecha_hora > registros.fecha_hora
    )
    ");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $stmt->bind_result($entradas_sin_salida);
    $stmt->fetch();
    $stmt->close();

    if ($entradas_sin_salida > 0) {
    $_SESSION['mensaje'] = "Ya tienes un ingreso registrado sin salida. Debes registrar tu salida antes de ingresar nuevamente.";
    }
    else {
        try {
            $stmt = $conexion->prepare("INSERT INTO registros (id_usuario, tipo_registro, id_dependencia) VALUES (?, ?, ?)");
            $stmt->bind_param("isi", $id_usuario, $tipo_registro, $id_dependencia);
            $stmt->execute();
            $stmt->close();
            $_SESSION['mensaje'] = "Ingreso registrado correctamente.";
        } catch (Exception $e) {
            $_SESSION['mensaje'] = "Error al registrar el ingreso: " . $e->getMessage();
        }
    }

    // Redirigir según el rol
    if (strtolower(trim($rol)) === 'admin') {
        header("Location: indexAdmin.php");
    } else {
        header("Location: indexEmpleado.php");
    }
    exit();
}
?>

<!-- Resto del HTML (igual que tienes) -->

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingreso - Control de Asistencia</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <link rel="stylesheet" href="css/styleIndex.css">
    <link rel="stylesheet" href="css/entrada_salida.css">
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
        <div class="form-container">
            <div class="back-button" id="backButton">
                <i class="fas fa-arrow-left"></i>
            </div>  
            
            <h2 class="form-title">Registro de Ingreso</h2>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form id="formIngreso" method="POST" action="ingreso.php">
                <div class="custom-control custom-switch mb-4">
                    <input type="checkbox" class="custom-control-input" id="autoCompletar">
                    <label class="custom-control-label" for="autoCompletar">Completar automáticamente todos los registros</label>
                </div>

                <div class="form-group">
                    <label for="rut"><i class="fas fa-id-card mr-2"></i>RUT</label>
                    <input type="text" class="form-control form-control-lg" id="rut" name="rut" placeholder="Ej: 12345678-9" required>
                </div>

                <div class="form-group">
                    <label for="nombre"><i class="fas fa-user mr-2"></i>Nombre Completo</label>
                    <input type="text" class="form-control form-control-lg" id="nombre" name="nombre" placeholder="Ingrese nombre completo" required>
                </div>

                <div class="form-group">
                    <label for="fechaIngreso"><i class="fas fa-calendar-alt mr-2"></i>Fecha de Ingreso</label>
                    <input type="date" class="form-control form-control-lg" id="fechaIngreso" name="fechaIngreso" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-clock mr-2"></i>Hora de Ingreso</label>
                    <p class="form-control-plaintext form-control-lg hora-automatica" id="horaIngreso">--:--:-- (Se registrará automáticamente)</p>
                </div>

                <button type="submit" class="btn btn-primary btn-lg btn-block mt-4 btn-ingresar">
                    <i class="fas fa-sign-in-alt mr-2"></i>Ingresar
                </button>
            </form>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const hoy = new Date();
            const fechaInput = document.getElementById('fechaIngreso');
            if (fechaInput) {
                const yyyy = hoy.getFullYear();
                const mm = String(hoy.getMonth() + 1).padStart(2, '0');
                const dd = String(hoy.getDate()).padStart(2, '0');
                fechaInput.value = `${yyyy}-${mm}-${dd}`;
            }

            function actualizarHora() {
                const ahora = new Date();
                const hh = String(ahora.getHours()).padStart(2, '0');
                const min = String(ahora.getMinutes()).padStart(2, '0');
                const ss = String(ahora.getSeconds()).padStart(2, '0');
                const horaDisplay = document.getElementById('horaIngreso');
                if (horaDisplay) {
                    horaDisplay.textContent = `${hh}:${min}:${ss} (Se registrará automáticamente)`;
                }
            }

            setInterval(actualizarHora, 1000);
            actualizarHora();

            const autoCompletarSwitch = document.getElementById('autoCompletar');
            if (autoCompletarSwitch) {
                autoCompletarSwitch.addEventListener('change', function() {
                    if (this.checked) {
                        fetch('obtener_usuario.php')
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Error en la respuesta del servidor');
                                }
                                return response.json();
                            })
                            .then(data => {
                                if (data.success) {
                                    document.getElementById('rut').value = data.rut;
                                    document.getElementById('nombre').value = data.nombre;
                                } else {
                                    alert(data.error || 'Error al obtener datos del usuario');
                                    this.checked = false;
                                }
                            });
                    } else {
                        document.getElementById('rut').value = '';
                        document.getElementById('nombre').value = '';
                    }
                });
            }
        });
    </script>   
    
    
    <script type="module" src="scripts/main.js"></script>
    <script src="scripts/validaciones.js"></script>
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" crossorigin="anonymous"></script>
    <script src="scripts/cargarConfiguracion.js"></script>
    <script src="scripts/entrada_salida.js"></script>
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
