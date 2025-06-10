// Alternar visibilidad del submenú de reportes
export function toggleReportes() {
    const reportsToggle = document.getElementById('reportsToggle');
    const reportsSubmenu = document.getElementById('reportsSubmenu');
    
    if (reportsToggle && reportsSubmenu) {
        reportsToggle.addEventListener('click', function(e) {
            e.preventDefault();
            reportsSubmenu.classList.toggle('active');
        });
    }
}