import { toggleMenu } from './menu.js';
import { toggleReportes } from './reportes.js';
import { actualizarFechas } from './fechas.js';

// Inicializar todas las funcionalidades cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    toggleMenu();
    toggleReportes();
    actualizarFechas();
});