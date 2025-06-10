<?php
// Verificar si la sesión está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}







// Obtener el rol del usuario desde la sesión o variables globales
$es_admin = false;
if (isset($_SESSION['id_usuario'])) {
    // Asumiendo que ya tienes una función para obtener los datos del usuario
    require_once 'conexion.php';
    require_once 'includes/funciones.php';
    $usuario = obtener_usuario($_SESSION['id_usuario']);
    $rol_usuario = isset($usuario['rol']) ? strtolower(trim($usuario['rol'])) : 'empleado';
    $es_admin = ($rol_usuario === 'admin' || $rol_usuario === 'administrator');
}
?>

<!-- Menú inferior (solo mobile) -->
<?php if ($es_admin): ?>
    <!-- Menú para administradores -->
    <nav class="bottom-nav">
        <a href="ingreso.php" class="nav-item">
            <i class="fas fa-sign-in-alt"></i>
            <span>Ingreso</span>
        </a>
        <a href="salida.php" class="nav-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>Salida</span>
        </a>
        <a href="#" class="nav-item" id="reportsToggle">
            <i class="fas fa-search"></i>
            <span>Reportes</span>
        </a>
    </nav>

    <div class="reports-submenu" id="reportsSubmenu">
        <a href="reporteInOut.php" class="report-item">
            <i class="fas fa-list-alt"></i>
            <span>Reporte de ingresos/salidas</span>
        </a>
        <a href="reportePersonas.php" class="report-item">
            <i class="fas fa-user-friends"></i>
            <span>Personas en la dependencia</span>
        </a>
        <a href="reportePropio.php" class="report-item">
            <i class="fas fa-user-friends"></i>
            <span>Registro de asistencia propio</span>
        </a>
    </div>
<?php else: ?>
    <!-- Menú para empleados -->
    <nav class="bottom-nav">
        <a href="ingreso.php" class="nav-item">
            <i class="fas fa-sign-in-alt"></i>
            <span>Ingreso</span>
        </a>
        <a href="salida.php" class="nav-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>Salida</span>
        </a>
        <a href="reportePropio.php" class="nav-item">
            <i class="fas fa-calendar-check"></i>
            <span>Registro</span>
        </a>
    </nav>
<?php endif; ?>