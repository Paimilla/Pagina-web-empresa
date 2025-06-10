<?php
// mobile_menu.php - Reusable mobile menu component
// Include this in your pages with: include 'components/mobile_menu.php';

if (!isset($usuario)) {
    if (isset($_SESSION['usuario'])) {
        $usuario = $_SESSION['usuario'];
    } else {
        // Si no está definido $usuario y no hay sesión, inicializar con valores predeterminados
        $usuario = [
            'nombre' => 'Usuario',
            'foto_perfil' => 'assets/default-profile.jpg'
        ];
    }
}

// Determine if user is admin or employee for navigation purposes
$es_admin = isset($usuario['rol']) && $usuario['rol'] === 'admin';
$pagina_actual = basename($_SERVER['PHP_SELF']);
?>
<header class="app-header">
    <div class="user-circle">
       <?php 
                // Fix para mostrar correctamente la imagen del usuario
                if (!empty($usuario['foto_perfil'])) {
                    // Si ya es una cadena base64 completa (incluye el prefijo data:image)
                    if (strpos($usuario['foto_perfil'], 'data:image') === 0) {
                        echo '<img src="' . htmlspecialchars($usuario['foto_perfil']) . '" alt="Foto de perfil" class="user-photo">';
                    } 
                    // Si es solo el código base64 sin el prefijo
                    elseif (strpos($usuario['foto_perfil'], 'base64') !== false || preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $usuario['foto_perfil'])) {
                        echo '<img src="data:image/jpeg;base64,' . htmlspecialchars($usuario['foto_perfil']) . '" alt="Foto de perfil" class="user-photo">';
                    } 
                    // Si es una ruta de archivo
                    else {
                        echo '<img src="' . htmlspecialchars($usuario['foto_perfil']) . '" alt="Foto de perfil" class="user-photo">';
                    }
                } else {
                    echo '<img src="assets/default-profile.jpg" alt="Foto de perfil" class="user-photo">';
                }
                ?>
    </div>
    <h1 class="app-title">Control de Asistencia</h1>
    <button class="menu-toggle" id="menuToggle">
        <span></span>
        <span></span>
        <span></span>
    </button>
</header>

<!--barra superior desktop-->
<div class="desktop-header">
    <h1 class="app-title">Control de Asistencia</h1>
</div>