<?php
session_start();

// Limpiar todas las variables de sesión
$_SESSION = array();

// Destruir la cookie de sesión completamente
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// Destruir la sesión
session_destroy();

// Limpiar caché del navegador
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Redirigir con parámetro aleatorio para evitar caché
header("Location: inicio.php?logout=" . time());
exit;
?>