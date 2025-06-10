<?php

session_start();

// Refrescar cache para actualizar el menú

if (!isset($_SESSION['id_usuario'])) {
    header("Location: inicio.php");
    exit();
}
require_once 'includes/config.php';
require_once 'includes/funciones.php';

$id_usuario = $_SESSION['id_usuario'];

$usuario = obtener_usuario($id_usuario);
// Establecer valores por defecto
$es_admin = isset($usuario['rol']) && (strtolower($usuario['rol']) === 'admin' || strtolower($usuario['rol']) === 'administrator');
$titulo_pagina = $es_admin ? 'Panel de Administrador' : 'Panel de Empleado';
$foto_perfil = isset($usuario['foto_perfil']) && !empty($usuario['foto_perfil']) ? $usuario['foto_perfil'] : 'assets/default-profile.jpg';
$nombre_usuario = isset($usuario['nombre']) && !empty($usuario['nombre']) ? $usuario['nombre'] : 'Usuario';

// Obtener datos del usuario
$queryUsuario = "SELECT nombre, foto_perfil FROM usuarios WHERE id_usuario = ?";
$stmtUsuario = $conexion->prepare($queryUsuario);
$stmtUsuario->bind_param("i", $id_usuario);
$stmtUsuario->execute();
$resultUsuario = $stmtUsuario->get_result();
$usuario = $resultUsuario->fetch_assoc();

// Obtener número de personas presentes (entrada sin salida correspondiente)
$queryPresentes = "SELECT COUNT(*) AS total FROM registros r1 WHERE tipo_registro = 'entrada' AND NOT EXISTS (
    SELECT 1 FROM registros r2
    WHERE r2.id_usuario = r1.id_usuario
    AND r2.tipo_registro = 'salida'
    AND r2.fecha_hora > r1.fecha_hora
)";
$resultPresentes = $conexion->query($queryPresentes);
$presentes = $resultPresentes->fetch_assoc()['total'];

// Obtener número de personas que finalizaron jornada (entrada y salida)
$queryFinalizada = "SELECT COUNT(DISTINCT r1.id_usuario) AS total FROM registros r1
JOIN registros r2 ON r1.id_usuario = r2.id_usuario
WHERE r1.tipo_registro = 'entrada'
AND r2.tipo_registro = 'salida'
AND r2.fecha_hora > r1.fecha_hora
AND DATE(r1.fecha_hora) = CURDATE()
AND DATE(r2.fecha_hora) = CURDATE()";
$resultFinalizada = $conexion->query($queryFinalizada);
$finalizada = $resultFinalizada->fetch_assoc()['total'];

// Verificar si ya hay un ingreso sin salida (para mostrar el estado actual)
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

$estado_actual = ($entradas_sin_salida > 0) ? 'dentro' : 'fuera';

date_default_timezone_set('America/Santiago');
$fechaActual = date("d/m/Y H:i:s");
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Asistencia</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/styleIndex.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">

    <style>
        .loading-spinner {
            display: none;
            width: 2rem;
            height: 2rem;
            margin-right: 0.5rem;
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
    

    <!-- Contenido principal -->
    <main class="main-content">
        
        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['mensaje']; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php unset($_SESSION['mensaje']); ?>
        <?php endif; ?>

        <!-- Estado actual -->
        <div class="alert alert-info mt-3 text-center">
            <strong>Estado actual:</strong>
            <?php if ($estado_actual == 'dentro'): ?>
                <span class="badge badge-success">Dentro de la dependencia</span>
            <?php else: ?>
                <span class="badge badge-secondary">Fuera de la dependencia</span>
            <?php endif; ?>
        </div>

        <div id="carouselExampleIndicators" class="carousel slide" data-ride="carousel">
            <ol class="carousel-indicators">
                <li data-target="#carouselExampleIndicators" data-slide-to="0" class="active"></li>
                <li data-target="#carouselExampleIndicators" data-slide-to="1"></li>
                <li data-target="#carouselExampleIndicators" data-slide-to="2"></li>
            </ol>
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <img class="d-block w-100" src="img/imagen8.png" alt="First slide" >
                    <div class="carousel-caption d-none d-md-block">
                        <h5>Gato Frutilla</h5>
                        <p>La mejor empresa del mundo</p>
                    </div>
                </div>
                <div class="carousel-item">
                    <img class="d-block w-100" src="img/noticia6.png" alt="Second slide" >
                    <div class="carousel-caption d-none d-md-block">
                        <h5>Alza de Acciones a un 100%</h5>
                        <p>como un hecho totalmente historico para nuestra empresa se logro alzar a un 100% las acciones</p>
                    </div>
                </div>
                <div class="carousel-item">
                    <img class="d-block w-100" src="img/noticia4.png" alt="Third slide">
                    <div class="carousel-caption d-none d-md-block">
                         <h5>Aun mantenemos el 100%</h5>
                        <p>HECHO TOTALMENTE HISTORICO</p>
                    </div>
                </div>
            </div>
            <a class="carousel-control-prev" href="#carouselExampleIndicators" role="button" data-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="sr-only">Previous</span>
            </a>
            <a class="carousel-control-next" href="#carouselExampleIndicators" role="button" data-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="sr-only">Next</span>
            </a>
        </div>

        <div class="status-container">
            <section class="status-card">
                <div class="status-header"><h2>Presentes</h2><div class="status-date"><i class="fas fa-user-check"></i></div></div>
                <div class="status-count"><?= $presentes ?></div>
                <div class="status-update">Ultima actualización: <?= $fechaActual ?></div>
            </section>
            <section class="status-card">
                <div class="status-header"><h2>Jornada Finalizada</h2><div class="status-date"><i class="fa fa-user-times"></i></div></div>
                <div class="status-count"><?= $finalizada ?></div>
                <div class="status-update">Ultima actualización: <?= $fechaActual ?></div>
            </section>
        </div>
        

        <!-- Botones de Autoregistro y Salida Automática -->
        <div class="status-container">
            <section class="status-card">
                <div class="status-header">
                    <h2>Autoregistro</h2>
                    <div class="status-date">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
                <button class="btn btn-success btn-lg btn-block py-3" id="btnEntrada" onclick="confirmAction('entrada')">
                    <div class="spinner-border text-light loading-spinner" id="spinnerEntrada" role="status">
                        <span class="sr-only">Cargando...</span>
                    </div>
                    Registrar Entrada
                </button>
            </section>
       
            <section class="status-card">
                <div class="status-header">
                    <h2>Salida Automática</h2>
                    <div class="status-date">
                        <i class="fas fa-user-times"></i>
                    </div>
                </div>
                <button class="btn btn-danger btn-lg btn-block py-3" id="btnSalida" onclick="confirmAction('salida')">
                    <div class="spinner-border text-light loading-spinner" id="spinnerSalida" role="status">
                        <span class="sr-only">Cargando...</span>
                    </div>
                    Registrar Salida
                </button>
            </section>
        </div>
        

        <!-- Modal de confirmación -->
        <div class="modal fade" id="confirmationModal" tabindex="-1" role="dialog" aria-labelledby="confirmationModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmationModalLabel">Confirmar acción</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" id="confirmText">
                        <!-- Texto dinámico -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" id="confirmBtn">Aceptar</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="toast" id="responseToast" style="position: fixed; bottom: 20px; right: 20px; min-width: 300px;" data-delay="5000">
            <div class="toast-header" id="toastHeader">
                <strong class="mr-auto" id="toastTitle">Notificación</strong>
                <button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="toast-body" id="toastMessage">
                <!-- Mensaje dinámico -->
            </div>
        </div>
        

    </main>
   <script type="module" src="scripts/main.js"></script>
    <script>
        let currentAction = "";
      
        function confirmAction(action) {
            currentAction = action;
            const message = action === "entrada"
                ? "¿Está seguro que desea registrar su entrada?"
                : "¿Está seguro que desea registrar su salida?";
            document.getElementById("confirmText").textContent = message;
            $('#confirmationModal').modal('show');
        }
      
        document.getElementById("confirmBtn").addEventListener("click", () => {
            // Ocultar modal de confirmación
            $('#confirmationModal').modal('hide');
            
            // Mostrar spinner de carga
            const spinnerId = currentAction === "entrada" ? "spinnerEntrada" : "spinnerSalida";
            const btnId = currentAction === "entrada" ? "btnEntrada" : "btnSalida";
            document.getElementById(spinnerId).style.display = "inline-block";
            document.getElementById(btnId).disabled = true;
            
            // Crear FormData para enviar
            const formData = new FormData();
            formData.append('tipo_registro', currentAction);
            
            // Enviar solicitud AJAX
            fetch('registro_automatico.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Ocultar spinner
                document.getElementById(spinnerId).style.display = "none";
                document.getElementById(btnId).disabled = false;
                
                // Configurar y mostrar toast
                const toastHeader = document.getElementById('toastHeader');
                const toastTitle = document.getElementById('toastTitle');
                const toastMessage = document.getElementById('toastMessage');
                
                if (data.success) {
                    toastHeader.className = 'toast-header bg-success text-white';
                    toastTitle.textContent = '¡Éxito!';
                } else {
                    toastHeader.className = 'toast-header bg-danger text-white';
                    toastTitle.textContent = 'Error';
                }
                
                toastMessage.textContent = data.message;
                $('#responseToast').toast('show');
                
                // Si fue exitoso, actualizar la página después de 3 segundos
                if (data.success) {
                    setTimeout(() => {
                        location.reload();
                    }, 3000);
                }
            })
            .catch(error => {
                // Ocultar spinner
                document.getElementById(spinnerId).style.display = "none";
                document.getElementById(btnId).disabled = false;
                
                // Mostrar error
                const toastHeader = document.getElementById('toastHeader');
                const toastTitle = document.getElementById('toastTitle');
                const toastMessage = document.getElementById('toastMessage');
                
                toastHeader.className = 'toast-header bg-danger text-white';
                toastTitle.textContent = 'Error';
                toastMessage.textContent = 'Error de conexión. Intente nuevamente.';
                $('#responseToast').toast('show');
            });
        });
    </script>
    <script src="scripts/navigationControl.js"></script>
    <script src="scripts/cargarConfiguracion.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>


    
</body>

</html>