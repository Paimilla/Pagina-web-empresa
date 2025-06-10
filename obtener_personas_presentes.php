<?php
require_once 'conexion.php';
require_once 'includes/funciones.php';

// Configuración EXPLÍCITA de zona horaria para Chile
date_default_timezone_set('America/Santiago');

// Obtener fecha sin conversiones adicionales
$fecha_recibida = $_GET['fecha'] ?? date('Y-m-d');

// Validar y formatear fecha (sin cambios de zona horaria)
$fecha_consulta = DateTime::createFromFormat('Y-m-d', $fecha_recibida);
if (!$fecha_consulta) {
    $fecha_consulta = new DateTime();
}

// Formatear para consulta SQL
$fecha_para_bd = $fecha_consulta->format('Y-m-d');

// Consulta SQL (usa $fecha_para_bd en los parámetros)
$query = "SELECT 
        u.id_usuario, 
        u.nombre, 
        u.foto_perfil,
        MIN(r_entrada.fecha_hora) AS hora_entrada,
        r_entrada.id_dependencia
    FROM usuarios u
    JOIN registros r_entrada ON u.id_usuario = r_entrada.id_usuario 
        AND DATE(r_entrada.fecha_hora) = ?
        AND r_entrada.tipo_registro = 'entrada'
    LEFT JOIN (
        SELECT id_usuario, MAX(fecha_hora) AS ultima_salida
        FROM registros
        WHERE tipo_registro = 'salida'
        GROUP BY id_usuario
    ) r_salida ON u.id_usuario = r_salida.id_usuario
    WHERE (r_salida.ultima_salida IS NULL OR r_salida.ultima_salida < r_entrada.fecha_hora)
    AND u.estado = 1
    GROUP BY u.id_usuario, u.nombre, u.foto_perfil, r_entrada.id_dependencia
    ORDER BY hora_entrada ASC";


$stmt = $conexion->prepare($query);
$stmt->bind_param("s", $fecha_para_bd);
$stmt->execute();
$resultado = $stmt->get_result();

$personas = [];
$total_segundos = 0;

while ($row = $resultado->fetch_assoc()) {
    $hora_entrada = new DateTime($row['hora_entrada']);
    $ahora = new DateTime();
    
    $diferencia = $hora_entrada->diff($ahora);
    $horas = $diferencia->h + ($diferencia->days * 24);
    $minutos = $diferencia->i;
    
    // Limitar a máximo 12 horas para el día actual
    $horas = min($horas, 12);
    
    $tiempo_transcurrido = sprintf('%dh %dm', $horas, $minutos);
    $total_segundos += ($horas * 3600) + ($minutos * 60);
    
    $personas[] = [
        'id_usuario' => $row['id_usuario'],
        'nombre' => $row['nombre'],
        'foto_perfil' => !empty($row['foto_perfil']) ? $row['foto_perfil'] : 'assets/default-profile.jpg',
        'hora_entrada' => $row['hora_entrada'],
        'tiempo_transcurrido' => $tiempo_transcurrido,
        'dependencia' => 'Área ' . $row['id_dependencia']
    ];
}



// Calcular tiempo promedio
$total_horas = 0;
$total_minutos = 0;
foreach ($personas as $persona) {
    $partes = explode('h ', $persona['tiempo_transcurrido']);
    $horas = intval($partes[0]);
    $minutos = intval(str_replace('m', '', $partes[1]));
    
    $total_horas += $horas;
    $total_minutos += $minutos;
}

// Calcular promedio
$promedio_segundos = count($personas) > 0 ? $total_segundos / count($personas) : 0;
$promedio_horas = floor($promedio_segundos / 3600);
$promedio_minutos = floor(($promedio_segundos % 3600) / 60);


$datos = [
    'personas' => $personas,
    'total_presentes' => count($personas),
    'promedio_tiempo' => count($personas) > 0 ? sprintf('%dh %02dm', $promedio_horas, $promedio_minutos) : '0h 00m',
    'fecha' => $fecha_para_bd
];

header('Content-Type: application/json');
echo json_encode($datos);
?>