<?php
// mobile_menu.php - Reusable mobile menu component
// Include this in your pages with: include 'components/mobile_menu.php';

// Verificar que los datos del usuario existen
// Esta verificación es necesaria si este componente se incluye directamente
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

<style>
    /* Oculta el menú mobile en desktop por defecto */
    .mobile-menu {
        display: none;
    }

    @media (max-width: 768px) {
        .mobile-menu {
            display: block;
            /* Solo visible en móviles */
        }
    }
</style>
<!-- Menú lateral (mobile) -->
<nav class="mobile-menu" id="mobileMenu">
    <div class="menu-header">
        <button class="close-menu" id="closeMenu">
            <i class="fas fa-times"></i>
        </button>
        <div class="user-profile">
            <div class="profile-photo">
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
            <h3 class="user-name"><?= htmlspecialchars($usuario['nombre'] ?? 'Usuario') ?></h3>
        </div>
    </div>

    <div class="menu-content">
        <?php if ($es_admin): ?>
            <div class="menu-section">
                <div class="menu-divider"></div>
                <a href="indexAdmin.php" class="menu-item <?= $pagina_actual == 'indexAdmin.php' ? 'active' : '' ?>">
                    <i class="fas fa-home"></i>
                    <span>Inicio</span>
                </a>
            </div>
        <?php else: ?>
            <div class="menu-section">
                <div class="menu-divider"></div>
                <a href="indexEmpleado.php" class="menu-item <?= $pagina_actual == 'indexEmpleado.php' ? 'active' : '' ?>">
                    <i class="fas fa-home"></i>
                    <span>Inicio</span>
                </a>
            </div>
        <?php endif; ?>



        <!-- Sección de Configuración -->
        <div class="menu-section">
            <div class="menu-divider"></div>
            <span class="section-title">Configuración</span>
            <a href="editPerfil.php" class="menu-item <?= $pagina_actual == 'editPerfil.php' ? 'active' : '' ?>">
                <i class="fas fa-user-edit"></i>
                <span>Editar perfil</span>
            </a>
            <a href="configuracion.php" class="menu-item <?= $pagina_actual == 'configuracion.php' ? 'active' : '' ?>">
                <i class="fas fa-cog"></i>
                <span>Configuración</span>
            </a>
            <a href="ayuda.php" class="menu-item <?= $pagina_actual == 'ayuda.html' ? 'active' : '' ?>">
                <i class="fas fa-question-circle"></i>
                <span>Ayuda</span>
            </a>
        </div>

        <div class="menu-section">
            <div class="menu-divider"></div>
            <a href="cerrar_sesion.php" class="menu-item logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>Cerrar sesión</span>
            </a>
        </div>
    </div>
</nav>