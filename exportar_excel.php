<?php
session_start();
require_once 'conexion.php';
require_once 'includes/funciones.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    header("Location: inicio.php");
    exit();
}

// Obtener fecha seleccionada
$fecha_seleccionada = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');

// Función para obtener reporte de asistencia
function obtener_reporte_asistencia($fecha, $conexion) {
    $sql = "SELECT 
                u.id_usuario,
                u.nombre,
                u.rol,
                MIN(CASE WHEN r.tipo_registro = 'entrada' THEN r.fecha_hora END) as primera_entrada,
                MAX(CASE WHEN r.tipo_registro = 'salida' THEN r.fecha_hora END) as ultima_salida,
                COUNT(CASE WHEN r.tipo_registro = 'entrada' THEN 1 END) as total_entradas,
                COUNT(CASE WHEN r.tipo_registro = 'salida' THEN 1 END) as total_salidas
            FROM usuarios u
            LEFT JOIN registros r ON u.id_usuario = r.id_usuario 
                AND DATE(r.fecha_hora) = ?
            WHERE u.estado = 1
            GROUP BY u.id_usuario, u.nombre, u.rol
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

// Función para calcular tiempo trabajado
function calcular_tiempo_trabajado($entrada, $salida) {
    if (!$entrada) return "Sin entrada";
    if (!$salida) return "En turno";
    
    $inicio = new DateTime($entrada);
    $fin = new DateTime($salida);
    $diferencia = $inicio->diff($fin);
    
    return $diferencia->format('%h:%I');
}

// Función para formatear hora
function formatear_hora($fecha_hora) {
    if (!$fecha_hora) return "-";
    return date('H:i', strtotime($fecha_hora));
}

// Obtener datos
$empleados = obtener_reporte_asistencia($fecha_seleccionada, $conexion);

// Configurar headers para descarga de Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Reporte_Asistencia_' . $fecha_seleccionada . '.xls"');
header('Cache-Control: max-age=0');

// Crear contenido HTML que Excel puede interpretar
echo '<html>';
echo '<head>';
echo '<meta charset="UTF-8">';
echo '<style>';
echo 'table { border-collapse: collapse; width: 100%; }';
echo 'th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }';
echo 'th { background-color: #f2f2f2; font-weight: bold; }';
echo '.header { background-color: #4CAF50; color: white; text-align: center; padding: 10px; }';
echo '.fecha { text-align: center; margin: 10px 0; font-size: 14px; }';
echo '</style>';
echo '</head>';
echo '<body>';

// Título del reporte
echo '<div class="header">';
echo '<h2>REPORTE DE ASISTENCIA</h2>';
echo '</div>';

echo '<div class="fecha">';
echo '<strong>Fecha: ' . date('d/m/Y', strtotime($fecha_seleccionada)) . '</strong>';
echo '</div>';

// Tabla de datos
echo '<table>';
echo '<thead>';
echo '<tr>';
echo '<th>Empleado</th>';
echo '<th>Rol</th>';
echo '<th>Hora de Entrada</th>';
echo '<th>Hora de Salida</th>';
echo '<th>Tiempo Trabajado</th>';
echo '<th>Total Entradas</th>';
echo '<th>Total Salidas</th>';
echo '<th>Estado</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

foreach ($empleados as $empleado) {
    $tiene_entrada = !is_null($empleado['primera_entrada']);
    $tiene_salida = !is_null($empleado['ultima_salida']);
    $tiempo_trabajado = calcular_tiempo_trabajado($empleado['primera_entrada'], $empleado['ultima_salida']);
    
    // Determinar estado
    $estado = 'Sin entrada';
    if ($tiene_entrada && !$tiene_salida) {
        $estado = 'Trabajando';
    } elseif ($tiene_entrada && $tiene_salida) {
        $estado = 'Turno finalizado';
    }
    
    echo '<tr>';
    echo '<td>' . htmlspecialchars($empleado['nombre']) . '</td>';
    echo '<td>' . htmlspecialchars($empleado['rol']) . '</td>';
    echo '<td>' . formatear_hora($empleado['primera_entrada']) . '</td>';
    echo '<td>' . formatear_hora($empleado['ultima_salida']) . '</td>';
    echo '<td>' . $tiempo_trabajado . '</td>';
    echo '<td>' . $empleado['total_entradas'] . '</td>';
    echo '<td>' . $empleado['total_salidas'] . '</td>';
    echo '<td>' . $estado . '</td>';
    echo '</tr>';
}

echo '</tbody>';
echo '</table>';

// Resumen
$total_empleados = count($empleados);
$empleados_con_entrada = 0;
$empleados_con_salida = 0;

foreach ($empleados as $empleado) {
    if (!is_null($empleado['primera_entrada'])) $empleados_con_entrada++;
    if (!is_null($empleado['ultima_salida'])) $empleados_con_salida++;
}

echo '<br><br>';
echo '<table style="width: 50%;">';
echo '<tr><th colspan="2" style="background-color: #2196F3; color: white;">RESUMEN DEL DÍA</th></tr>';
echo '<tr><td><strong>Total de empleados:</strong></td><td>' . $total_empleados . '</td></tr>';
echo '<tr><td><strong>Empleados que ingresaron:</strong></td><td>' . $empleados_con_entrada . '</td></tr>';
echo '<tr><td><strong>Empleados que salieron:</strong></td><td>' . $empleados_con_salida . '</td></tr>';
echo '</table>';

echo '<br><br>';
echo '<p style="font-size: 12px; color: #666;">Reporte generado el ' . date('d/m/Y H:i:s') . '</p>';

echo '</body>';
echo '</html>';
?>