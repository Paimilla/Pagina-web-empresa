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

// Procesar modificaciones de registros (solo para administradores)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $es_admin) {
    $accion = $_POST['accion'] ?? '';
    $id_empleado = $_POST['id_empleado'] ?? '';
    $fecha = $_POST['fecha'] ?? '';
    $hora = $_POST['hora'] ?? '';
    $tipo_registro = $_POST['tipo_registro'] ?? '';
    $id_registro = $_POST['id_registro'] ?? '';
    
    if ($accion === 'agregar_registro') {
        // Agregar nuevo registro
        $fecha_hora = $fecha . ' ' . $hora . ':00';
        $sql = "INSERT INTO registros (id_usuario, tipo_registro, fecha_hora) VALUES (?, ?, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("iss", $id_empleado, $tipo_registro, $fecha_hora);
        
        if ($stmt->execute()) {
            $_SESSION['mensaje'] = "Registro agregado exitosamente";
            $_SESSION['tipo_mensaje'] = "success";
        } else {
            $_SESSION['mensaje'] = "Error al agregar el registro";
            $_SESSION['tipo_mensaje'] = "error";
        }
    } elseif ($accion === 'modificar_registro') {
        // Modificar registro existente
        $fecha_hora = $fecha . ' ' . $hora . ':00';
        $sql = "UPDATE registros SET fecha_hora = ?, tipo_registro = ? WHERE id_registro = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ssi", $fecha_hora, $tipo_registro, $id_registro);
        
        if ($stmt->execute()) {
            $_SESSION['mensaje'] = "Registro modificado exitosamente";
            $_SESSION['tipo_mensaje'] = "success";
        } else {
            $_SESSION['mensaje'] = "Error al modificar el registro";
            $_SESSION['tipo_mensaje'] = "error";
        }
    } elseif ($accion === 'eliminar_registro') {
        // Eliminar registro
        $sql = "DELETE FROM registros WHERE id_registro = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id_registro);
        
        if ($stmt->execute()) {
            $_SESSION['mensaje'] = "Registro eliminado exitosamente";
            $_SESSION['tipo_mensaje'] = "success";
        } else {
            $_SESSION['mensaje'] = "Error al eliminar el registro";
            $_SESSION['tipo_mensaje'] = "error";
        }
    }
    
    // Redireccionar para evitar reenvío del formulario
    header("Location: " . $_SERVER['PHP_SELF'] . "?fecha=" . ($_GET['fecha'] ?? date('Y-m-d')));
    exit();
}

// Obtener fecha seleccionada (por defecto hoy)
$fecha_seleccionada = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');

// Función para obtener reporte de asistencia con IDs de registros
function obtener_reporte_asistencia($fecha, $conexion) {
    $sql = "SELECT 
                u.id_usuario,
                u.nombre,
                u.foto_perfil,
                u.rol,
                MIN(CASE WHEN r.tipo_registro = 'entrada' THEN r.fecha_hora END) as primera_entrada,
                MAX(CASE WHEN r.tipo_registro = 'salida' THEN r.fecha_hora END) as ultima_salida,
                COUNT(CASE WHEN r.tipo_registro = 'entrada' THEN 1 END) as total_entradas,
                COUNT(CASE WHEN r.tipo_registro = 'salida' THEN 1 END) as total_salidas,
                GROUP_CONCAT(
                    CASE WHEN r.tipo_registro = 'entrada' THEN 
                        CONCAT(r.id_registro, ':', TIME(r.fecha_hora))
                    END ORDER BY r.fecha_hora SEPARATOR '|'
                ) as entradas_detalle,
                GROUP_CONCAT(
                    CASE WHEN r.tipo_registro = 'salida' THEN 
                        CONCAT(r.id_registro, ':', TIME(r.fecha_hora))
                    END ORDER BY r.fecha_hora SEPARATOR '|'
                ) as salidas_detalle
            FROM usuarios u
            LEFT JOIN registros r ON u.id_usuario = r.id_usuario 
                AND DATE(r.fecha_hora) = ?
            WHERE u.estado = 1
            GROUP BY u.id_usuario, u.nombre, u.foto_perfil, u.rol
            ORDER BY u.nombre";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $fecha);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    $empleados = [];
    while ($row = $resultado->fetch_assoc()) {
        $empleados[] = $row;
    }
    
    return $empleados;
}

// Función para obtener estadísticas del día
function obtener_estadisticas_dia($fecha, $conexion) {
    $sql = "SELECT 
                COUNT(DISTINCT u.id_usuario) as total_empleados,
                COUNT(DISTINCT CASE WHEN r.tipo_registro = 'entrada' THEN r.id_usuario END) as ingresaron,
                COUNT(DISTINCT CASE WHEN r.tipo_registro = 'salida' THEN r.id_usuario END) as salieron
            FROM usuarios u
            LEFT JOIN registros r ON u.id_usuario = r.id_usuario 
                AND DATE(r.fecha_hora) = ?
            WHERE u.estado = 1";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $fecha);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    return $resultado->fetch_assoc();
}

// Obtener datos
$empleados = obtener_reporte_asistencia($fecha_seleccionada, $conexion);
$estadisticas = obtener_estadisticas_dia($fecha_seleccionada, $conexion);

// Función para calcular tiempo trabajado
function calcular_tiempo_trabajado($entrada, $salida) {
    if (!$entrada) return "Sin entrada";
    if (!$salida) return "En turno";
    
    $inicio = new DateTime($entrada);
    $fin = new DateTime($salida);
    $diferencia = $inicio->diff($fin);
    
    return $diferencia->format('%hh %im');
}

// Función para formatear hora
function formatear_hora($fecha_hora) {
    if (!$fecha_hora) return "-";
    return date('H:i', strtotime($fecha_hora));
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Asistencia</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/styleIndex.css">
    <link rel="stylesheet" href="css/reporte_asistencia.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
    
</head>
<body>
    <?php 
    // Mostrar mensajes
    if (isset($_SESSION['mensaje'])) {
        echo '<div class="mensaje ' . $_SESSION['tipo_mensaje'] . '">';
        echo $_SESSION['mensaje'];
        echo '</div>';
        unset($_SESSION['mensaje']);
        unset($_SESSION['tipo_mensaje']);
    }
    
    // Incluir componentes
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
            <h2 class="reporte-titulo">Reporte de Asistencia</h2>
            
            <!-- Resumen estadístico -->
            <div class="resumen-asistencia">
                <div class="resumen-header">
                    <h3>Resumen del Día</h3>
                    <div class="resumen-fecha">
                        <span id="fecha-actual"><?php echo date('d \d\e F, Y', strtotime($fecha_seleccionada)); ?></span>
                        <i class="fas fa-calendar-alt calendar-icon" id="calendarBtn"></i>
                    </div>
                </div>
                
                <div class="resumen-estadisticas">
                    <div class="estadistica-item">
                        <div class="estadistica-icono">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="estadistica-valor"><?php echo $estadisticas['total_empleados']; ?></div>
                        <div class="estadistica-label">Total Empleados</div>
                    </div>
                    <div class="estadistica-item">
                        <div class="estadistica-icono">
                            <i class="fas fa-sign-in-alt"></i>
                        </div>
                        <div class="estadistica-valor"><?php echo $estadisticas['ingresaron']; ?></div>
                        <div class="estadistica-label">Ingresaron</div>
                    </div>
                    <div class="estadistica-item">
                        <div class="estadistica-icono">
                            <i class="fas fa-sign-out-alt"></i>
                        </div>
                        <div class="estadistica-valor"><?php echo $estadisticas['salieron']; ?></div>
                        <div class="estadistica-label">Salieron</div>
                    </div>
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="filtros-container">
                <button class="filtro-btn active" data-filtro="todos">Todos</button>
                <button class="filtro-btn" data-filtro="trabajando">Trabajando</button>
                <button class="filtro-btn" data-filtro="finalizado">Turno Finalizado</button>
                <button class="filtro-btn" data-filtro="sin_entrada">Sin Entrada</button>
            </div>
            
            <!-- Lista de empleados -->
            <div class="lista-empleados" id="listaEmpleados">
                <?php foreach ($empleados as $empleado): 
                    $tiene_entrada = !is_null($empleado['primera_entrada']);
                    $tiene_salida = !is_null($empleado['ultima_salida']);
                    $tiempo_trabajado = calcular_tiempo_trabajado($empleado['primera_entrada'], $empleado['ultima_salida']);
                    
                    // Determinar estado
                    $estado = 'sin_entrada';
                    if ($tiene_entrada && !$tiene_salida) {
                        $estado = 'trabajando';
                    } elseif ($tiene_entrada && $tiene_salida) {
                        $estado = 'finalizado';
                    }
                    
                    $foto_empleado = !empty($empleado['foto_perfil']) ? $empleado['foto_perfil'] : 'assets/default-profile.jpg';
                ?>
                <div class="empleado-card" data-estado="<?php echo $estado; ?>">
                    <div class="empleado-info">
                        <div class="empleado-foto">
                            <?php
                                // Determina la fuente de la imagen (puedes ajustar esto según tus necesidades)
                                $fuente_imagen = isset($foto_empleado) ? $foto_empleado : (isset($usuario['foto_perfil']) ? $usuario['foto_perfil'] : '');

                                // Verifica y muestra la imagen correspondiente
                                if (!empty($fuente_imagen)) {
                                    // Si ya es una cadena base64 completa (incluye el prefijo data:image)
                                    if (strpos($fuente_imagen, 'data:image') === 0) {
                                        echo '<img src="' . htmlspecialchars($fuente_imagen) . '" alt="' . htmlspecialchars($empleado['nombre'] ?? 'Foto de perfil') . '" class="profile-picture" id="profilePicture">';
                                    }
                                    // Si es solo el código base64 sin el prefijo
                                    elseif (strpos($fuente_imagen, 'base64') !== false || preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $fuente_imagen)) {
                                        echo '<img src="data:image/jpeg;base64,' . htmlspecialchars($fuente_imagen) . '" alt="' . htmlspecialchars($empleado['nombre'] ?? 'Foto de perfil') . '" class="profile-picture" id="profilePicture">';
                                    }
                                    // Si es una ruta de archivo
                                    else {
                                        echo '<img src="' . htmlspecialchars($fuente_imagen) . '" alt="' . htmlspecialchars($empleado['nombre'] ?? 'Foto de perfil') . '" class="profile-picture" id="profilePicture">';
                                    }
                                } else {
                                    // Imagen por defecto
                                    echo '<img src="assets/default-profile.jpg" alt="Foto de perfil" class="profile-picture" id="profilePicture">';
                                }
                                ?>

                        </div>
                        <div class="empleado-datos">
                            <div class="empleado-nombre"><?php echo htmlspecialchars($empleado['nombre']); ?></div>
                            <div class="empleado-horarios">
                                <?php if ($tiene_entrada): ?>
                                    <div class="empleado-hora entrada">
                                        <i class="fas fa-sign-in-alt"></i>
                                        Entrada: <?php echo formatear_hora($empleado['primera_entrada']); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($tiene_salida): ?>
                                    <div class="empleado-hora salida">
                                        <i class="fas fa-sign-out-alt"></i>
                                        Salida: <?php echo formatear_hora($empleado['ultima_salida']); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!$tiene_entrada): ?>
                                    <div class="empleado-hora sin-registro">
                                        <i class="fas fa-exclamation-circle"></i>
                                        Sin registro de entrada
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="empleado-tiempo">
                        <div class="tiempo-label">Tiempo</div>
                        <div class="tiempo-valor <?php echo $estado === 'trabajando' ? 'tiempo-activo' : ''; ?>">
                            <?php echo $tiempo_trabajado; ?>
                        </div>
                    </div>
                    <div class="empleado-estado">
                        <div class="estado-indicador <?php echo $estado; ?>">
                            <?php if ($estado === 'trabajando'): ?>
                                <i class="fas fa-play"></i>
                                <span>Trabajando</span>
                            <?php elseif ($estado === 'finalizado'): ?>
                                <i class="fas fa-stop"></i>
                                <span>Finalizado</span>
                            <?php else: ?>
                                <i class="fas fa-minus"></i>
                                <span>Sin entrada</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($es_admin): ?>
                    <div class="empleado-acciones">
                        <button class="btn-accion btn-editar" onclick="abrirModalEdicion(<?php echo $empleado['id_usuario']; ?>, '<?php echo htmlspecialchars($empleado['nombre']); ?>', '<?php echo $empleado['entradas_detalle']; ?>', '<?php echo $empleado['salidas_detalle']; ?>')">
                            <i class="fas fa-edit"></i> Editar
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Exportar reporte -->
            <div class="acciones-reporte">
                <button class="btn-exportar" id="exportarExcel">
                    <i class="fas fa-file-excel"></i>
                    Exportar a Excel
                </button>
                
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
                <input type="date" class="fecha-input" id="fechaInput" value="<?php echo $fecha_seleccionada; ?>">
            </div>
            <div class="modal-footer">
                <button class="modal-btn secundario" id="cancelarFecha">Cancelar</button>
                <button class="modal-btn primario" id="aplicarFecha">Aplicar</button>
            </div>
        </div>
    </div>

    <!-- Modal para edición de registros -->
    <?php if ($es_admin): ?>
    <div class="modal-edicion" id="modalEdicion">
        <div class="modal-contenido-edicion">
            <div class="modal-header">
                <h3>Editar Registros - <span id="nombreEmpleadoModal"></span></h3>
                <button class="modal-cerrar" onclick="cerrarModalEdicion()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="registrosExistentes"></div>
                
                <hr style="margin: 20px 0;">
                
                <h4>Agregar Nuevo Registro</h4>
                <form id="formAgregarRegistro" method="POST">
                    <input type="hidden" name="accion" value="agregar_registro">
                    <input type="hidden" name="id_empleado" id="idEmpleadoModal">
                    <input type="hidden" name="fecha" value="<?php echo $fecha_seleccionada; ?>">
                    
                    <div class="form-group">
                        <label for="tipoRegistro">Tipo de Registro:</label>
                        <select class="form-control" name="tipo_registro" id="tipoRegistro" required>
                            <option value="entrada">Entrada</option>
                            <option value="salida">Salida</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="horaRegistro">Hora:</label>
                        <input type="time" class="form-control" name="hora" id="horaRegistro" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Agregar Registro</button>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="cerrarModalEdicion()">Cerrar</button>
            </div>
        </div>
    </div>

    <!-- Modal para modificar registro específico -->
    <div class="modal-edicion" id="modalModificar">
        <div class="modal-contenido-edicion">
            <div class="modal-header">
                <h3>Modificar Registro</h3>
                <button class="modal-cerrar" onclick="cerrarModalModificar()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="formModificarRegistro" method="POST">
                    <input type="hidden" name="accion" value="modificar_registro">
                    <input type="hidden" name="id_registro" id="idRegistroModificar">
                    <input type="hidden" name="fecha" value="<?php echo $fecha_seleccionada; ?>">
                    
                    <div class="form-group">
                        <label for="tipoRegistroModificar">Tipo de Registro:</label>
                        <select class="form-control" name="tipo_registro" id="tipoRegistroModificar" required>
                            <option value="entrada">Entrada</option>
                            <option value="salida">Salida</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="horaRegistroModificar">Hora:</label>
                        <input type="time" class="form-control" name="hora" id="horaRegistroModificar" required>
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                        <button type="button" class="btn btn-danger" onclick="eliminarRegistro()">Eliminar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Formulario oculto para eliminar -->
    <form id="formEliminarRegistro" method="POST" style="display: none;">
        <input type="hidden" name="accion" value="eliminar_registro">
        <input type="hidden" name="id_registro" id="idRegistroEliminar">
    </form>
    <?php endif; ?>

    <script src="scripts/iniciarVolver.js"></script>
    <script>
        iniciarVolver('backButton', 'indexAdmin.html');
    </script>

    <script>// Agregar este código al archivo scripts/reporte_asistencia.js o crear uno nuevo

document.addEventListener('DOMContentLoaded', function() {
    // Botón exportar a Excel
    const btnExportarExcel = document.getElementById('exportarExcel');
    if (btnExportarExcel) {
        btnExportarExcel.addEventListener('click', function() {
            // Obtener la fecha actual seleccionada
            const fechaActual = obtenerFechaSeleccionada();
            
            // Mostrar loading
            btnExportarExcel.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando Excel...';
            btnExportarExcel.disabled = true;
            
            // Crear enlace de descarga
            const url = 'exportar_excel.php?fecha=' + fechaActual;
            
            // Crear un iframe oculto para la descarga
            const iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.src = url;
            document.body.appendChild(iframe);
            
            // Restaurar botón después de un tiempo
            setTimeout(function() {
                btnExportarExcel.innerHTML = '<i class="fas fa-file-excel"></i> Exportar a Excel';
                btnExportarExcel.disabled = false;
                document.body.removeChild(iframe);
            }, 3000);
            
            // Mostrar mensaje de éxito
            mostrarMensaje('Excel generado exitosamente', 'success');
        });
    }
    
    
});

// Función para obtener la fecha seleccionada actual
function obtenerFechaSeleccionada() {
    // Buscar la fecha en la URL
    const urlParams = new URLSearchParams(window.location.search);
    const fechaUrl = urlParams.get('fecha');
    
    if (fechaUrl) {
        return fechaUrl;
    }
    
    // Si no hay fecha en URL, usar la fecha actual
    const hoy = new Date();
    const año = hoy.getFullYear();
    const mes = String(hoy.getMonth() + 1).padStart(2, '0');
    const dia = String(hoy.getDate()).padStart(2, '0');
    
    return `${año}-${mes}-${dia}`;
}

// Función para mostrar mensajes de éxito/error
function mostrarMensaje(mensaje, tipo) {
    // Crear elemento de mensaje
    const mensajeDiv = document.createElement('div');
    mensajeDiv.className = `mensaje ${tipo}`;
    mensajeDiv.innerHTML = `
        <i class="fas ${tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
        ${mensaje}
    `;
    
    // Agregar estilos si no existen
    if (!document.getElementById('estilosMensajes')) {
        const estilos = document.createElement('style');
        estilos.id = 'estilosMensajes';
        estilos.textContent = `
            .mensaje {
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 5px;
                color: white;
                font-weight: bold;
                z-index: 9999;
                min-width: 250px;
                text-align: center;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                opacity: 0;
                transform: translateX(100%);
                transition: all 0.3s ease;
            }
            
            .mensaje.success {
                background-color: #4CAF50;
            }
            
            .mensaje.error {
                background-color: #f44336;
            }
            
            .mensaje.show {
                opacity: 1;
                transform: translateX(0);
            }
        `;
        document.head.appendChild(estilos);
    }
    
    // Insertar mensaje en el DOM
    document.body.appendChild(mensajeDiv);
    
    // Mostrar con animación
    setTimeout(() => {
        mensajeDiv.classList.add('show');
    }, 100);
    
    // Ocultar después de 3 segundos
    setTimeout(() => {
        mensajeDiv.classList.remove('show');
        setTimeout(() => {
            if (mensajeDiv.parentNode) {
                mensajeDiv.parentNode.removeChild(mensajeDiv);
            }
        }, 300);
    }, 3000);
}

// Función para manejar errores en las descargas
function manejarErrorDescarga(tipo) {
    mostrarMensaje(`Error al generar el archivo ${tipo}. Inténtalo de nuevo.`, 'error');
}

// Verificar si los archivos PHP existen (opcional)
function verificarArchivosExportacion() {
    fetch('exportar_excel.php', { method: 'HEAD' })
        .then(response => {
            if (!response.ok) {
                console.warn('El archivo exportar_excel.php no está disponible');
            }
        })
        .catch(error => {
            console.warn('No se pudo verificar exportar_excel.php:', error);
        });
    
    
}

// Ejecutar verificación al cargar la página
verificarArchivosExportacion();
</script>

    <script type="module" src="scripts/main.js"></script>
    <script src="scripts/reporte_asistencia.js"></script>
    <script src="scripts/cargarConfiguracion.js"></script>

    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js"></script>
</body>
</html>