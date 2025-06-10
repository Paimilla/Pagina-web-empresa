// scripts/iniciarSelectorTipo.js
function iniciarSelectorTipo(adminId, empleadoId, inputId) {
    const adminElement = document.getElementById(adminId);
    const empleadoElement = document.getElementById(empleadoId);
    const inputElement = document.getElementById(inputId);
    
    // Función para activar/desactivar los elementos
    function activarTipo(esAdmin) {
        if (esAdmin) {
            adminElement.classList.add('active');
            empleadoElement.classList.remove('active');
            inputElement.value = 'admin';
        } else {
            adminElement.classList.remove('active');
            empleadoElement.classList.add('active');
            inputElement.value = 'empleado';
        }
    }
    
    // Configurar eventos click
    adminElement.addEventListener('click', function() {
        activarTipo(true);
    });
    
    empleadoElement.addEventListener('click', function() {
        activarTipo(false);
    });
    
    // Configuración inicial (asegurando que uno esté seleccionado)
    if (!adminElement.classList.contains('active') && !empleadoElement.classList.contains('active')) {
        activarTipo(true); // Por defecto seleccionar admin
    }
}