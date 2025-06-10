// Mostrar y actualizar fechas en la interfaz
export function actualizarFechas() {
    const ahora = new Date();
    const opcionesFecha = { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    };
    const opcionesHora = { 
        hour: '2-digit', 
        minute: '2-digit' 
    };
    
    // Actualizar elementos con las fechas formateadas
    actualizarElemento('currentDate', ahora.toLocaleDateString('es-ES', opcionesFecha));
    actualizarElemento('lastUpdate', ahora.toLocaleTimeString('es-ES', opcionesHora));
    actualizarElemento('lastUpdate2', ahora.toLocaleTimeString('es-ES', opcionesHora));
}

// Función auxiliar para actualizar elementos del DOM
function actualizarElemento(id, contenido) {
    const elemento = document.getElementById(id);
    if (elemento) {
        elemento.textContent = contenido;
    }
}