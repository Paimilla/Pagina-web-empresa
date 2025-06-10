<?php
/**
 * config.php - Archivo de configuración para el sistema de control de asistencia
 * Este archivo incorpora tu conexión.php existente y añade configuraciones adicionales
 */

// Configuración de zona horaria
date_default_timezone_set('America/Santiago'); // Cambia a tu zona horaria

// Incluir el archivo de conexión existente
require_once 'conexion.php'; 
// Esto ya establece la variable $conexion que usaremos en todo el sistema

// Opciones de visualización de errores
// Comentar estas líneas en producción
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Otras configuraciones globales
define('DIRECTORIO_IMAGENES', 'uploads/imagenes/');
define('URL_BASE', 'http://localhost/control_asistencia/'); // Cambia a la URL de tu proyecto