<?php
// Encabezados para evitar caché
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Content-Type: application/json');

// Iniciar sesión
session_start();
require_once 'conexion.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No hay sesión activa']);
    exit();
}

// Verificar que se recibió el tipo de registro
if (!isset($_POST['tipo_registro']) || ($_POST['tipo_registro'] != 'entrada' && $_POST['tipo_registro'] != 'salida')) {
    echo json_encode(['success' => false, 'message' => 'Tipo de registro no válido']);
    exit();
}

$tipo_registro = $_POST['tipo_registro'];
$id_usuario = $_SESSION['id_usuario'];
$id_dependencia = 1; // Valor por defecto, podría ser dinámico

// Validaciones según el tipo de registro
if ($tipo_registro == 'entrada') {
    // Verificar si ya hay un ingreso sin salida
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
        echo json_encode(['success' => false, 'message' => 'Ya tienes un ingreso registrado sin salida. Debes registrar tu salida antes de ingresar nuevamente.']);
        exit();
    }
} else { // Es salida
    // Verificar si hay una entrada previa sin salida
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

    if ($entradas_sin_salida == 0) {
        echo json_encode(['success' => false, 'message' => 'No hay un ingreso registrado. Debes registrar tu entrada antes de registrar una salida.']);
        exit();
    }
}

// Si pasó todas las validaciones, registrar la entrada/salida
try {
    $stmt = $conexion->prepare("INSERT INTO registros (id_usuario, tipo_registro, id_dependencia) VALUES (?, ?, ?)");
    $stmt->bind_param("isi", $id_usuario, $tipo_registro, $id_dependencia);
    $resultado = $stmt->execute();
    $stmt->close();
    
    if ($resultado) {
        $mensaje = ($tipo_registro == 'entrada') ? 'Ingreso registrado correctamente.' : 'Salida registrada correctamente.';
        echo json_encode(['success' => true, 'message' => $mensaje]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al registrar: ' . $conexion->error]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al registrar: ' . $e->getMessage()]);
}
?>