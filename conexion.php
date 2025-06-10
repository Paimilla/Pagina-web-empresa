<?php
// Configuración de la conexión a la base de datos
$host = 'localhost';      // Servidor de base de datos
$usuario = 'root';        // Usuario de MySQL
$password = '';           // Contraseña
$db_name = 'empresa';     // Nombre de la base de datos

// Crear conexión
$conexion = new mysqli($host, $usuario, $password, $db_name);

// Verificar conexión
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Establecer conjunto de caracteres UTF-8
$conexion->set_charset("utf8");

// Hacer la variable global (si es necesario)
global $conexion;
?>

