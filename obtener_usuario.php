<?php
session_start();
require_once 'conexion.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

try {
    $stmt = $conexion->prepare("SELECT nombre, rut, foto_perfil FROM usuarios WHERE id_usuario = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($usuario = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'nombre' => $usuario['nombre'],
            'rut' => $usuario['rut'],
            'foto_perfil' => $usuario['foto_perfil'] ?? null // Usamos null si no existe
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Usuario no encontrado']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>