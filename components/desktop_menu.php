<?php
// Verificar sesión primero
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit();
}

// Obtener datos del usuario
if (!isset($_SESSION['usuario'])) {
    require_once __DIR__ . '/../conexion.php';
    require_once __DIR__ . '/../includes/funciones.php';
    
    $id_usuario = $_SESSION['id_usuario'];
    $usuario = obtener_usuario($id_usuario);
    $_SESSION['usuario'] = $usuario;
} else {
    $usuario = $_SESSION['usuario'];
}

// Usar la misma función de header.php para consistencia
if (!function_exists('getProfileImageUrl')) {
    function getProfileImageUrl($imagePath) {
        if (empty($imagePath)) {
            return 'assets/default-profile.jpg?v=' . time();
        }
        
        if (strpos($imagePath, 'data:image') === 0) {
            return $imagePath;
        }
        
        if (strpos($imagePath, 'base64') !== false || preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $imagePath)) {
            return 'data:image/jpeg;base64,' . $imagePath;
        }
        
        return $imagePath . '?v=' . (file_exists($imagePath) ? filemtime($imagePath) : time());
    }
}

$foto_perfil_url = getProfileImageUrl($usuario['foto_perfil'] ?? '');
$nombre_usuario = $usuario['nombre'] ?? 'Usuario';
$es_admin = isset($usuario['rol']) && strtolower($usuario['rol']) === 'admin';
$pagina_actual = basename($_SERVER['PHP_SELF']);
?>

<style>
    /* Oculta el menú desktop en móviles por defecto */
    .desktop-menu {
        display: block; /* Visible en desktop */
    }

    @media (max-width: 768px) {
        .desktop-menu {
            display: none !important; /* Oculta en móviles */
        }
    }
</style>

<!-- Menú lateral (desktop) -->
<nav class="desktop-menu">
    <div class="profile-section">
        <div class="profile-photo">
            <img src="<?= htmlspecialchars($foto_perfil_url) ?>" alt="Foto de perfil" class="user-photo">
        </div>
        <h3 class="user-name"><?= htmlspecialchars($nombre_usuario) ?></h3>
    </div>

    <ul>
        <?php if ($es_admin): ?>
            <li><a href="indexAdmin.php" class="<?= $pagina_actual == 'indexAdmin.php' ? 'active' : '' ?>"><i class="fas fa-home"></i> Inicio</a></li>
        <?php else: ?>
            <li><a href="indexEmpleado.php" class="<?= $pagina_actual == 'indexEmpleado.php' ? 'active' : '' ?>"><i class="fas fa-home"></i> Inicio</a></li>
        <?php endif; ?>
        
        <!-- Sección de Registros -->
        <li class="menu-section">
            <div class="menu-divider"></div>
            <span class="section-title">Registro de Asistencia</span>
        </li>
        <li><a href="ingreso.php" class="menu-item <?= $pagina_actual == 'ingreso.php' ? 'active' : '' ?>"><i class="fas fa-sign-in-alt"></i> Ingreso</a></li>
        <li><a href="salida.php" class="menu-item <?= $pagina_actual == 'salida.php' ? 'active' : '' ?>"><i class="fas fa-sign-out-alt"></i> Salida</a></li>
        
        <!-- Submenú de Reportes -->
        <li class="submenu-parent">
            <a href="#" class="menu-item"><i class="fas fa-search"></i> Reportes <i class="fas fa-chevron-down"></i></a>
            <ul class="submenu">
                <?php if ($es_admin): ?>
                    <li><a href="reporteInOut.php"><i class="fas fa-list-alt"></i> Reporte de ingresos/salidas</a></li>
                    <li><a href="reportePersonas.php"><i class="fas fa-user-friends"></i> Personas en la dependencia</a></li>
                     <li><a href="reportePropio.php"><i class="fas fa-list-alt"></i> Reporte personal de ingresos/salidas</a></li>
                <?php else: ?>
                    <li><a href="reportePropio.php"><i class="fas fa-list-alt"></i> Reporte personal de ingresos/salidas</a></li>
                <?php endif; ?>
            </ul>
        </li>
        
        <!-- Otras secciones -->
        <li class="menu-section">
            <div class="menu-divider"></div>
            <span class="section-title">Configuración</span>
        </li>
        <li><a href="editPerfil.php" class="menu-item <?= $pagina_actual == 'editPerfil.php' ? 'active' : '' ?>"><i class="fas fa-user-edit"></i> Editar perfil</a></li>
        <li><a href="configuracion.php" class="menu-item <?= $pagina_actual == 'configuracion.php' ? 'active' : '' ?>"><i class="fas fa-cog"></i> Configuración</a></li>
        <li><a href="ayuda.php" class="menu-item <?= $pagina_actual == 'ayuda.html' ? 'active' : '' ?>"><i class="fas fa-question-circle"></i> Ayuda</a></li>
        <li><a href="cerrar_sesion.php" class="menu-item logout"><i class="fas fa-sign-out-alt"></i><span>Cerrar sesión</span></a></li>
    </ul>
</nav>