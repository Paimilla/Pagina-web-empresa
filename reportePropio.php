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

// Obtener datos del usuario con manejo de errores
$usuario = obtener_usuario($id_usuario);
if (!$usuario) {
    $_SESSION['mensaje'] = "Error: No se pudieron obtener los datos del usuario.";
    $_SESSION['tipo_mensaje'] = "error";
    header("Location: inicio.php");
    exit();
}

// Configuración regional y zona horaria
setlocale(LC_TIME, 'es_ES.UTF-8');
date_default_timezone_set('America/Santiago');

// Guardamos el rol en una variable para usar en PHP y JavaScript
$rol_usuario = isset($usuario['rol']) ? strtolower(trim($usuario['rol'])) : 'empleado';
$es_admin = ($rol_usuario === 'admin' || $rol_usuario === 'administrator');
$titulo_pagina = $es_admin ? 'Panel de Administrador' : 'Panel de Empleado';
$foto_perfil = isset($usuario['foto_perfil']) && !empty($usuario['foto_perfil']) ? $usuario['foto_perfil'] : 'assets/default-profile.jpg';
$nombre_usuario = isset($usuario['nombre']) && !empty($usuario['nombre']) ? $usuario['nombre'] : 'Usuario';

// Función para obtener los registros de asistencia
function obtener_registros_asistencia($id_usuario, $fecha_inicio, $fecha_fin) {
    global $conexion;
    
    if (empty($fecha_inicio)) $fecha_inicio = date('Y-m-d');
    if (empty($fecha_fin)) $fecha_fin = date('Y-m-d');
    
    $sql = "SELECT r.*, DATE(r.fecha_hora) as fecha, TIME(r.fecha_hora) as hora 
            FROM registros r 
            WHERE r.id_usuario = ? 
            AND DATE(r.fecha_hora) BETWEEN ? AND ? 
            ORDER BY r.fecha_hora ASC";
            
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("iss", $id_usuario, $fecha_inicio, $fecha_fin);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    $registros = array();
    
    while ($row = $resultado->fetch_assoc()) {
        $fecha = $row['fecha'];
        
        if (!isset($registros[$fecha])) {
            $registros[$fecha] = array(
                'fecha' => $fecha,
                'registros' => array()
            );
        }
        
        $registros[$fecha]['registros'][] = array(
            'tipo' => $row['tipo_registro'],
            'hora' => $row['hora'],
            'observacion' => $row['observacion']
        );
    }
    
    return $registros;
}

// Función mejorada para formatear fechas en español
function formatear_fecha($fecha, $formato = 'l, d M') {
    if (empty($fecha)) return 'Fecha inválida';
    
    try {
        $date = new DateTime($fecha);
    } catch (Exception $e) {
        return 'Fecha inválida';
    }
    
    // Usar IntlDateFormatter si está disponible (mejor para internacionalización)
    if (class_exists('IntlDateFormatter')) {
        $formatter = new IntlDateFormatter(
            'es_ES',
            IntlDateFormatter::FULL,
            IntlDateFormatter::FULL,
            'UTC',
            IntlDateFormatter::GREGORIAN,
            str_replace(['l', 'F'], ['EEEE', 'MMMM'], $formato)
        );
        return $formatter->format($date);
    }
    
    // Si Intl no está disponible, usar método manual
    $dias = array(
        'Monday' => 'Lunes', 'Tuesday' => 'Martes', 'Wednesday' => 'Miércoles',
        'Thursday' => 'Jueves', 'Friday' => 'Viernes', 'Saturday' => 'Sábado', 'Sunday' => 'Domingo'
    );
    
    $meses = array(
        'January' => 'Enero', 'February' => 'Febrero', 'March' => 'Marzo',
        'April' => 'Abril', 'May' => 'Mayo', 'June' => 'Junio',
        'July' => 'Julio', 'August' => 'Agosto', 'September' => 'Septiembre',
        'October' => 'Octubre', 'November' => 'Noviembre', 'December' => 'Diciembre'
    );
    
    $fecha_formateada = $date->format($formato);
    
    foreach ($dias as $en => $es) {
        $fecha_formateada = str_replace($en, $es, $fecha_formateada);
    }
    
    foreach ($meses as $en => $es) {
        $fecha_formateada = str_replace($en, $es, $fecha_formateada);
    }
    
    return $fecha_formateada;
}

// Función para calcular horas trabajadas
function calcular_horas_trabajadas($registros_dia) {
    $entradas = array();
    $salidas = array();
    
    foreach ($registros_dia as $registro) {
        if (strtolower($registro['tipo']) == 'entrada') {
            $entradas[] = $registro['hora'];
        } else if (strtolower($registro['tipo']) == 'salida') {
            $salidas[] = $registro['hora'];
        }
    }
    
    if (count($entradas) > 0 && count($salidas) > 0) {
        sort($entradas);
        sort($salidas);
        
        $primera_entrada = reset($entradas);
        $ultima_salida = end($salidas);
        
        $entrada = new DateTime($primera_entrada);
        $salida = new DateTime($ultima_salida);
        
        if ($salida <= $entrada) {
            return array(
                'entrada' => $primera_entrada,
                'salida' => $ultima_salida,
                'horas' => 0,
                'minutos' => 0,
                'total_segundos' => 0,
                'formato' => '0h 00m'
            );
        }
        
        $diferencia = $entrada->diff($salida);
        $total_segundos = $diferencia->h * 3600 + $diferencia->i * 60 + $diferencia->s;
        
        return array(
            'entrada' => $primera_entrada,
            'salida' => $ultima_salida,
            'horas' => $diferencia->h,
            'minutos' => $diferencia->i,
            'total_segundos' => $total_segundos,
            'formato' => sprintf("%dh %02dm", $diferencia->h, $diferencia->i)
        );
    }
    
    return array(
        'entrada' => isset($entradas[0]) ? $entradas[0] : null,
        'salida' => isset($salidas[0]) ? $salidas[0] : null,
        'horas' => 0,
        'minutos' => 0,
        'total_segundos' => 0,
        'formato' => '0h 00m'
    );
}

// Determinar período de tiempo
$periodo = isset($_GET['periodo']) ? $_GET['periodo'] : 'semana';
$hoy = new DateTime();
$fecha_inicio = $fecha_fin = null;

switch ($periodo) {
    case 'mes':
        $fecha_inicio = $hoy->format('Y-m-01');
        $fecha_fin = $hoy->format('Y-m-t');
        break;
        
    case 'personalizado':
        $fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : $hoy->format('Y-m-d');
        $fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : $hoy->format('Y-m-d');
        break;
        
    case 'semana':
    default:
        $lunes = clone $hoy;
        $lunes->modify('this week');
        $domingo = clone $lunes;
        $domingo->modify('+6 days');
        
        $fecha_inicio = $lunes->format('Y-m-d');
        $fecha_fin = $domingo->format('Y-m-d');
        break;
}

// Validar fechas
if (!strtotime($fecha_inicio) || !strtotime($fecha_fin)) {
    $periodo = 'semana';
    $lunes = clone $hoy;
    $lunes->modify('this week');
    $domingo = clone $lunes;
    $domingo->modify('+6 days');
    
    $fecha_inicio = $lunes->format('Y-m-d');
    $fecha_fin = $domingo->format('Y-m-d');
}

// Obtener registros para el período seleccionado
$registros_periodo = obtener_registros_asistencia($id_usuario, $fecha_inicio, $fecha_fin);

// Calcular estadísticas del período
$total_dias_trabajados = 0;
$total_segundos_trabajados = 0;

foreach ($registros_periodo as $fecha => $dia) {
    $horas_dia = calcular_horas_trabajadas($dia['registros']);
    if ($horas_dia['total_segundos'] > 0) {
        $total_dias_trabajados++;
    }
    $total_segundos_trabajados += $horas_dia['total_segundos'];
}

$total_horas = floor($total_segundos_trabajados / 3600);
$total_minutos = floor(($total_segundos_trabajados % 3600) / 60);

// Calcular promedio diario
$promedio_formato = "0h 00m";
if ($total_dias_trabajados > 0) {
    $promedio_segundos = intval($total_segundos_trabajados / $total_dias_trabajados);
    $promedio_horas = intval($promedio_segundos / 3600);
    $promedio_minutos = intval(($promedio_segundos % 3600) / 60);
    $promedio_formato = sprintf("%dh %02dm", $promedio_horas, $promedio_minutos);
}

// Formatear rango de fechas para mostrar
$fecha_inicio_obj = new DateTime($fecha_inicio);
$fecha_fin_obj = new DateTime($fecha_fin);

if ($periodo == 'mes') {
    $rango_fechas = formatear_fecha($fecha_inicio, 'F Y');
} else {
    $rango_fechas = formatear_fecha($fecha_inicio, 'd M') . ' - ' . formatear_fecha($fecha_fin, 'd M, Y');
}

// Obtener datos del mes completo para el resumen mensual
$mes_inicio = $hoy->format('Y-m-01');
$mes_fin = $hoy->format('Y-m-t');
$registros_mes = obtener_registros_asistencia($id_usuario, $mes_inicio, $mes_fin);

$total_dias_mes = 0;
$total_segundos_mes = 0;

foreach ($registros_mes as $fecha => $dia) {
    $horas_dia = calcular_horas_trabajadas($dia['registros']);
    if ($horas_dia['total_segundos'] > 0) {
        $total_dias_mes++;
    }
    $total_segundos_mes += $horas_dia['total_segundos'];
}

$total_horas_mes = floor($total_segundos_mes / 3600);
$total_minutos_mes = floor(($total_segundos_mes % 3600) / 60);

// Calcular horas extras (8 horas diarias estándar)
$horas_estandar = (int)($total_dias_mes * 8 * 3600);
$horas_extras_segundos = (int)max(0, $total_segundos_mes - $horas_estandar);
// Usar conversión explícita a int antes de las operaciones
$horas_extras = (int)floor($horas_extras_segundos / 3600);
$minutos_extras = (int)floor(($horas_extras_segundos % 3600) / 60);

// Calcular promedio diario del mes
// Calcular promedio diario del mes
// Calcular promedio diario del mes
$promedio_formato_mes = "0h 00m";
if ($total_dias_mes > 0) {
    // Evitar todas las conversiones problemáticas
    $promedio_segundos_mes = intval($total_segundos_mes / $total_dias_mes);
    $promedio_horas_mes = intval($promedio_segundos_mes / 3600);
    $promedio_minutos_mes = intval(($promedio_segundos_mes % 3600) / 60);
    $promedio_formato_mes = sprintf("%dh %02dm", $promedio_horas_mes, $promedio_minutos_mes);
}
// Definir mes actual para el resumen mensual
$mes_actual_nombre = formatear_fecha($hoy->format('Y-m-d'), 'F Y');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Asistencia</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/styleIndex.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
   <style>
        /* Estilos específicos para el reporte de asistencia personal */
        body {
            overflow-y: scroll; /* Siempre mostrar barra de desplazamiento */
        }
        .reporte-container {
            padding: 15px;
            max-width: 800px;
            margin: 0 auto;
            min-height: calc(100vh - 150px); /* Ajustar según necesidades */
            display: flex;
            flex-direction: column;
        }

        .reporte-titulo {
            color: var(--color-primary);
            font-size: 1.5rem;
            margin-bottom: 20px;
            font-weight: 600;
            text-align: center;
        }

        /* Resumen de asistencia personal */
        .resumen-asistencia {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 1px 4px 10px rgba(0, 0, 0, 0.2), -4px -4px 10px rgba(0, 0, 0, 0.1);
        }
        

        .resumen-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .resumen-header h3 {
            font-size: 1.1rem;
            color: var(--color-primary);
        }

        .resumen-fecha {
            display: flex;
            align-items: center;
            color: #666;
            font-size: 0.9rem;
        }

        .calendar-icon {
            margin-left: 10px;
            color: var(--color-primary);
            cursor: pointer;
        }

        .resumen-estadisticas {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 20px;
            width: 100%;
            overflow-x: auto;
           
        }

        .estadistica-item {
            background-color: #f5f5f5;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            
        }

        .estadistica-valor {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--color-primary);
            margin-bottom: 5px;
        }

        .estadistica-label {
            font-size: 0.8rem;
            color: #666;
        }

        .estadistica-icono {
            font-size: 1.2rem;
            color: var(--color-primary);
            margin-bottom: 5px;
        }

        /* Filtros */
        .filtros-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            overflow-x: auto;
        }

        .filtro-btn {
            background: white;
            border: 1px solid #ddd;
            padding: 8px 15px;
            margin-right: 10px;
            border-radius: 20px;
            font-size: 0.9rem;
            color: #666;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.3s;
        }

        .filtro-btn.active {
            background: var(--color-primary);
            color: white;
            border-color: var(--color-primary);
        }

        /* Lista de registros */
        .registros-container {
            background-color: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 1px 4px 10px rgba(0, 0, 0, 0.2), -4px -4px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            /* Altura mínima para evitar saltos */
            min-height: 300px;
        }

        .registro-header {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            font-weight: bold;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            margin-bottom: 10px;
            text-align: center;
        }

        .registro-item {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            padding: 12px 0;
            border-bottom: 1px solid #f5f5f5;
            align-items: center;
        }

        .registro-item:last-child {
            border-bottom: none;
        }

        .registro-dia {
            font-weight: 600;
            color: var(--color-success);
            font-size: 17px;
        }

        .registro-horas {
            display: flex;
            flex-direction: column;
        }

        .hora-entrada, .hora-salida {
            display: flex;
            align-items: center;
            margin-bottom: 3px;
        }

        .hora-entrada i {
            color: var(--color-success);
            margin-right: 5px;
        }

        .hora-salida i {
            color: var(--color-danger);
            margin-right: 5px;
        }

        .registro-total {
            text-align: right;
            font-weight: 600;
            color: var(--color-primary);
        }
        
        .sin-registros {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 200px; /* Altura fija para el mensaje de sin registros */
            color: #666;
            font-style: italic;
            text-align: center;
        }

        /* Resumen mensual */
        .resumen-mensual {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            box-shadow: 1px 4px 10px rgba(0, 0, 0, 0.2), -4px -4px 10px rgba(0, 0, 0, 0.1);
        }

        .resumen-mensual h3 {
            font-size: 1.1rem;
            color: var(--color-text-secondary);
            margin-bottom: 15px;
            text-align: center;
        }

        .resumen-mensual-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .mensual-item {
            background-color: #f5f5f5;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }

        .mensual-label {
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 5px;
        }

        .mensual-valor {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--color-primary);
        }

        /* Modal de fecha */
        .modal-fecha {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0,0,0,0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .modal-contenido {
            background-color: white;
            width: 90%;
            max-width: 400px;
            border-radius: 10px;
            overflow: hidden;
            animation: modalFadeIn 0.3s;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .modal-header {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            font-size: 1.2rem;
            color: var(--color-text-secondary);
        }

        .modal-cerrar {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #666;
            cursor: pointer;
        }

        .modal-body {
            padding: 20px 15px;
        }

        .fecha-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            margin-bottom: 10px;
        }

        .modal-footer {
            padding: 15px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
        }

        .modal-btn {
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 0.9rem;
            cursor: pointer;
            margin-left: 10px;
            transition: all 0.3s;
        }

        .secundario {
            background-color: white;
            border: 1px solid #ddd;
            color: #666;
        }

        .primario {
            background-color: var(--color-primary);
            border: 1px solid var(--color-primary);
            color: white;
        }
        .reporte-container .resumen-mensual {
            margin-top: auto;
        }
        .filtro-btn.active {
            background: var(--color-primary);
            color: white;
            border-color: var(--color-primary);
            transition: all 0.3s ease;
        }

        /* Asegurar que todos los elementos tienen transiciones suaves */
        * {
            transition: all 0.2s ease;
        }

        /* Excluir de transiciones elementos que no deben tenerlas */
        input, button {
            transition: background-color 0.3s, color 0.3s, border-color 0.3s;
        }
        


        /* Versión tablet */
        @media (min-width: 768px) {
            .reporte-container {
                padding: 30px;

            }
            
            .reporte-titulo {
                font-size: 1.8rem;
            }
            
            .resumen-mensual-grid {
                grid-template-columns: repeat(4, 1fr);
            }
            
            .registro-header, .registro-item {
                text-align: center;
            }
            
            .registro-dia {
                text-align: left;
            }
            
            .registro-total {
                text-align: right;
            }
            .registros-container {
                min-height: 200px;
            }
        }

        /* Versión desktop */
        @media (min-width: 992px) {
            .registros-container {
                min-height: 200px;
            }
            .resumen-estadisticas {
                grid-template-columns: repeat(3, 1fr);
            }
            
            .main-content {
                margin: 200 auto;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
            }
        }

        /* Fix para que el botón de volver esté correctamente posicionado */
        .back-button {
            position: absolute;
            top: 15px;
            left: 15px;
            z-index: 10;
            cursor: pointer;
            background: var(--color-primary);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        /* Mejora de container principal para mejor centrado */
        .main-content {
            position: relative;
            padding-top: 30px;
            padding-bottom: 70px;
            min-height: calc(100vh - 60px);
        }
        
        /* Fix para cuando no hay registros */
        .sin-registros {
            grid-column: span 3;
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
    <div id="loading-overlay">
        <div class="spinner"></div>
    </div>
    

    <!-- Contenido principal -->
    <main class="main-content">
        <div class="back-button" id="backButton">
            <i class="fas fa-arrow-left"></i>
        </div>
        <div class="reporte-container">
            <h2 class="reporte-titulo">Mi Asistencia</h2>
            
            <!-- Resumen estadístico -->
            <div class="resumen-asistencia">
                <div class="resumen-header">
                    <h3>Resumen <?php echo $periodo == 'mes' ? 'Mensual' : ($periodo == 'semana' ? 'Semanal' : 'Personalizado'); ?></h3>
                    <div class="resumen-fecha">
                        <span id="fecha-actual"><?php echo $rango_fechas; ?></span>
                        <i class="fas fa-calendar-alt calendar-icon" id="calendarBtn"></i>
                    </div>
                </div>
                
                <div class="resumen-estadisticas">
                    <div class="estadistica-item">
                        <div class="estadistica-icono">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                        <div class="estadistica-valor" id="dias-trabajados"><?php echo $total_dias_trabajados; ?></div>
                        <div class="estadistica-label">Días trabajados</div>
                    </div>
                    <div class="estadistica-item">
                        <div class="estadistica-icono">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="estadistica-valor" id="horas-totales"><?php echo "{$total_horas}h {$total_minutos}m"; ?></div>
                        <div class="estadistica-label">Horas totales</div>
                    </div>
                    <div class="estadistica-item">
                        <div class="estadistica-icono">
                            <i class="fas fa-flag-checkered"></i>
                        </div>
                        <div class="estadistica-valor" id="promedio-diario"><?php echo $promedio_formato; ?></div>
                        <div class="estadistica-label">Promedio diario</div>
                    </div>
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="filtros-container">
                <button class="filtro-btn <?php echo $periodo == 'semana' ? 'active' : ''; ?>" data-filtro="semana">Esta semana</button>
                <button class="filtro-btn <?php echo $periodo == 'mes' ? 'active' : ''; ?>" data-filtro="mes">Este mes</button>
                <button class="filtro-btn <?php echo $periodo == 'personalizado' ? 'active' : ''; ?>" data-filtro="personalizado">Personalizado</button>
            </div>
            
            <!-- Lista de registros -->
            <div class="registros-container">
                <div class="registro-header">
                    <div>Día</div>
                    <div>Horario</div>
                    <div>Total</div>
                </div>
                
                <?php if (count($registros_periodo) > 0): ?>
                    <?php 
                    // Ordenar registros por fecha (más reciente primero)
                    $fechas = array_keys($registros_periodo);
                    rsort($fechas);
                    
                    foreach ($fechas as $fecha):
                        $dia = $registros_periodo[$fecha];
                        $horas_trabajadas = calcular_horas_trabajadas($dia['registros']);
                        $fecha_formateada = formatear_fecha($fecha);
                    ?>
                    <div class="registro-item">
                        <div class="registro-dia"><?php echo $fecha_formateada; ?></div>
                        <div class="registro-horas">
                            <?php if (!is_null($horas_trabajadas['entrada'])): ?>
                            <div class="hora-entrada">
                                <i class="fas fa-sign-in-alt"></i> <?php echo date('h:i A', strtotime($horas_trabajadas['entrada'])); ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!is_null($horas_trabajadas['salida'])): ?>
                            <div class="hora-salida">
                                <i class="fas fa-sign-out-alt"></i> <?php echo date('h:i A', strtotime($horas_trabajadas['salida'])); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="registro-total"><?php echo $horas_trabajadas['formato']; ?></div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="sin-registros">No hay registros de asistencia para este período</div>
                <?php endif; ?>
            </div>
            
            <!-- Resumen mensual -->
            <div class="resumen-mensual">
                <h3>Resumen Mensual - <?php echo $mes_actual_nombre; ?></h3>
                <div class="resumen-mensual-grid">
                    <div class="mensual-item">
                        <div class="mensual-label">Días trabajados</div>
                        <div class="mensual-valor"><?php echo $total_dias_mes; ?></div>
                    </div>
                    <div class="mensual-item">
                        <div class="mensual-label">Horas totales</div>
                        <div class="mensual-valor"><?php echo "{$total_horas_mes}h {$total_minutos_mes}m"; ?></div>
                    </div>
                    <div class="mensual-item">
                        <div class="mensual-label">Promedio diario</div>
                        <div class="mensual-valor"><?php echo $promedio_formato_mes; ?></div>
                    </div>
                    <div class="mensual-item">
                        <div class="mensual-label">Horas extras</div>
                        <div class="mensual-valor"><?php echo "{$horas_extras}h {$minutos_extras}m"; ?></div>
                    </div>
                </div>
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
                <div class="form-group">
                    <label>Desde:</label>
                    <input type="date" id="fecha_inicio" class="fecha-input" value="<?php echo $fecha_inicio; ?>">
                </div>
                <div class="form-group">
                    <label>Hasta:</label>
                    <input type="date" id="fecha_fin" class="fecha-input" value="<?php echo $fecha_fin; ?>">
                </div>
            </div>
            <div class="modal-footer">
                <button class="modal-btn secundario" id="cancelarFecha">Cancelar</button>
                <button class="modal-btn primario" id="aplicarFecha">Aplicar</button>
            </div>
        </div>
    </div>


    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.min.js"></script>
    <script>
                /**
         * Script mejorado para evitar saltos en la interfaz al cambiar entre opciones
         */
        document.addEventListener('DOMContentLoaded', function() {
            // Elementos del DOM
            const modalFecha = document.getElementById('modalFecha');
            const calendarBtn = document.getElementById('calendarBtn');
            const cerrarModal = document.getElementById('cerrarModal');
            const cancelarFecha = document.getElementById('cancelarFecha');
            const aplicarFecha = document.getElementById('aplicarFecha');
            const fechaInicioInput = document.getElementById('fecha_inicio');
            const fechaFinInput = document.getElementById('fecha_fin');
            const backButton = document.getElementById('backButton');
            const filtroBtns = document.querySelectorAll('.filtro-btn');
            const registrosContainer = document.querySelector('.registros-container');
            
            // Recordar posición de scroll antes de recargar la página
            const saveScrollPosition = () => {
                sessionStorage.setItem('scrollPosition', window.scrollY);
            };
            
            // Restaurar posición de scroll después de cargar la página
            const restoreScrollPosition = () => {
                const scrollPosition = sessionStorage.getItem('scrollPosition');
                if (scrollPosition !== null) {
                    window.scrollTo(0, parseInt(scrollPosition));
                    sessionStorage.removeItem('scrollPosition');
                }
            };
            
            // Añadir indicador de carga
            const showLoading = () => {
                // Crear y mostrar indicador de carga
                const loadingDiv = document.createElement('div');
                loadingDiv.id = 'loading-indicator';
                loadingDiv.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background-color: rgba(255, 255, 255, 0.7);
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    z-index: 9999;
                `;
                
                const spinner = document.createElement('div');
                spinner.style.cssText = `
                    border: 4px solid #f3f3f3;
                    border-top: 4px solid var(--color-primary, #1e88e5);
                    border-radius: 50%;
                    width: 40px;
                    height: 40px;
                    animation: spin 1s linear infinite;
                `;
                
                // Añadir animación de rotación
                const style = document.createElement('style');
                style.textContent = `
                    @keyframes spin {
                        0% { transform: rotate(0deg); }
                        100% { transform: rotate(360deg); }
                    }
                `;
                document.head.appendChild(style);
                
                loadingDiv.appendChild(spinner);
                document.body.appendChild(loadingDiv);
            };
            
            // Abrir modal de fecha
            calendarBtn.addEventListener('click', function(e) {
                e.preventDefault();
                modalFecha.style.display = 'flex';
            });
            
            // Cerrar modal
            function cerrarModalFecha() {
                modalFecha.style.display = 'none';
            }
            
            cerrarModal.addEventListener('click', cerrarModalFecha);
            cancelarFecha.addEventListener('click', cerrarModalFecha);
            
            // Cerrar modal al hacer clic fuera del contenido
            modalFecha.addEventListener('click', function(e) {
                if (e.target === modalFecha) {
                    cerrarModalFecha();
                }
            });
            
            // Aplicar filtro de fecha personalizado con indicador de carga
            aplicarFecha.addEventListener('click', function() {
                const inicio = fechaInicioInput.value;
                const fin = fechaFinInput.value;
                
                if (inicio && fin) {
                    if (new Date(fin) < new Date(inicio)) {
                        alert('La fecha final no puede ser anterior a la fecha inicial');
                        return;
                    }
                    
                    saveScrollPosition();
                    showLoading();
                    window.location.href = `reportePropio.php?periodo=personalizado&fecha_inicio=${inicio}&fecha_fin=${fin}`;
                } else {
                    alert('Por favor selecciona ambas fechas');
                }
            });
            
            // Manejar filtros rápidos (semana/mes) con indicador de carga
            filtroBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const filtro = this.getAttribute('data-filtro');
                    
                    if (filtro === 'personalizado') {
                        modalFecha.style.display = 'flex';
                    } else {
                        saveScrollPosition();
                        showLoading();
                        window.location.href = `reportePropio.php?periodo=${filtro}`;
                    }
                });
            });
            
            // Configurar el botón de retroceso
            if (backButton) {
                backButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    showLoading();
                    const rolUsuario = document.body.getAttribute('data-role') || "<?php echo $rol_usuario; ?>";
                    const destino = rolUsuario === 'admin' ? 'indexAdmin.php' : 'indexEmpleado.php';
                    window.location.href = destino;
                });
            }
            
            // Configurar valores máximos/minimos para las fechas en el modal
            const hoy = new Date().toISOString().split('T')[0];
            fechaInicioInput.setAttribute('max', hoy);
            fechaFinInput.setAttribute('max', hoy);
            
            fechaInicioInput.addEventListener('change', function() {
                fechaFinInput.setAttribute('min', this.value);
            });
            
            fechaFinInput.addEventListener('change', function() {
                fechaInicioInput.setAttribute('max', this.value);
            });
            
            // Establecer altura mínima para el contenedor de registros
            const setMinHeight = () => {
                const viewportHeight = window.innerHeight;
                const headerHeight = document.querySelector('.resumen-asistencia').offsetHeight + 
                                    document.querySelector('.filtros-container').offsetHeight + 100;
                const footerHeight = document.querySelector('.resumen-mensual').offsetHeight + 50;
                
                const minHeight = viewportHeight - headerHeight - footerHeight;
                registrosContainer.style.minHeight = `${Math.max(minHeight, 200)}px`;
            };
            
            // Ejecutar al cargar y al redimensionar
            window.addEventListener('load', () => {
                setMinHeight();
                restoreScrollPosition();
            });
            window.addEventListener('resize', setMinHeight);
        });
    </script>

    <script type="module" src="scripts/main.js"></script>
    <script src="scripts/cargarConfiguracion.js"></script>
</body>
</html>