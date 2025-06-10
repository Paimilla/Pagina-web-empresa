/**
 * Maneja el botón "Atrás" del navegador
 * Muestra confirmación y cierra sesión si el usuario acepta
 */

document.addEventListener('DOMContentLoaded', function() {
    // Solo aplicar en páginas protegidas
    if (window.location.pathname.includes('indexadmin.php') || 
        window.location.pathname.includes('indexempleado.php')) {
        
        // Agregar estado al historial para detectar el retroceso
        window.history.pushState(null, null, window.location.href);
        
        // Manejar el evento de retroceso
        window.addEventListener('popstate', function(event) {
            // Mostrar confirmación
            if (confirm('¿Estás seguro que deseas salir? Se cerrará tu sesión.')) {
                // Cerrar sesión via AJAX
                fetch('cerrar_sesion.php', {
                    method: 'POST',
                    credentials: 'same-origin'
                })
                .then(response => {
                    // Redirigir al login después de cerrar sesión
                    window.location.href = 'inicio.php?logout=' + Date.now();
                })
                .catch(error => {
                    console.error('Error al cerrar sesión:', error);
                    window.location.href = 'inicio.php?logout=' + Date.now();
                });
            } else {
                // Si cancela, volver a agregar el estado al historial
                window.history.pushState(null, null, window.location.href);
            }
        });
    }
});