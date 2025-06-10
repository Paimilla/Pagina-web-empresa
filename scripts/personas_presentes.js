document.addEventListener('DOMContentLoaded', function() {
    // Función para obtener fecha actual en Chile
    const getFechaActualChile = () => {
        const ahora = new Date();
        // Ajuste para Chile (UTC-4 o UTC-3 según horario de verano)
        const offsetChile = ahora.getTimezoneOffset() + (ahora.getMonth() > 4 && ahora.getMonth() < 8 ? 240 : 180);
        return new Date(ahora.getTime() + offsetChile * 60000);
    };

    // Formatear fecha para mostrar (sin conversión de zona horaria)
    const formatearFecha = (fechaStr) => {
        const [year, month, day] = fechaStr.split('-');
        const meses = [
            'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
            'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'
        ];
        return `${day} de ${meses[parseInt(month)-1]} de ${year}`;
    };

    // Actualizar UI con fecha
    const actualizarFechaUI = (fecha) => {
        document.getElementById('fecha-actual').textContent = formatearFecha(fecha);
    };

    // Cargar datos
    const cargarPersonasPresentes = (fecha = null) => {
        const fechaParaServidor = fecha || getFechaActualChile().toISOString().split('T')[0];
        
        fetch(`obtener_personas_presentes.php?fecha=${fechaParaServidor}`)
            .then(response => response.json())
            .then(data => {
                console.log('Debug:', {
                    fechaRecibida: data.fecha,
                    fechaServidor: data.fecha_hora_servidor
                });
                
                actualizarFechaUI(data.fecha);
                
                // Actualizar estadísticas
                document.getElementById('total-presentes').textContent = data.total_presentes;
                document.getElementById('promedio-tiempo').textContent = data.promedio_tiempo;
                // Actualizar lista de empleados
                const listaEmpleados = document.getElementById('listaEmpleados');
                listaEmpleados.innerHTML = '';
                
                if (data.personas.length === 0) {
                    listaEmpleados.innerHTML = '<div class="no-data">No hay personas presentes en esta fecha</div>';
                    return;
                }
                
                data.personas.forEach(persona => {
                    const empleadoCard = document.createElement('div');
                    empleadoCard.className = 'empleado-card';
                    empleadoCard.innerHTML = `
                        <div class="empleado-info">
                            <div class="empleado-foto">
                                <img src="${persona.foto_perfil}" alt="${persona.nombre}">
                            </div>
                            <div class="empleado-datos">
                                <div class="empleado-nombre">${persona.nombre}</div>
                                <div class="empleado-departamento">${persona.dependencia}</div>
                            </div>
                        </div>
                        
                        <div class="empleado-tiempo">
                            <div class="estadistica-icono">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="tiempo-detalle">
                                <div class="tiempo-label">Tiempo trabajando</div>
                                <div class="tiempo-valor">${persona.tiempo_transcurrido}</div>
                            </div>
                        </div>
                    `;
                    listaEmpleados.appendChild(empleadoCard);
                });
            })
            .catch(error => {
                console.error('Error al cargar personas presentes:', error);
                document.getElementById('listaEmpleados').innerHTML = 
                    '<div class="error-message">Error al cargar los datos. Intente más tarde.</div>';
            });
    };
    
    // Obtener fecha actual en formato YYYY-MM-DD (considerando zona horaria)
    const ahoraChile = new Date().toLocaleString('en-US', { timeZone: 'America/Santiago' });
    const fechaActual = new Date(ahoraChile).toISOString().split('T')[0];
    
    // Inicializar con la fecha actual
    cargarPersonasPresentes();

    // Manejo del calendario
    const fechaInput = document.getElementById('fechaInput');
    const aplicarFecha = document.getElementById('aplicarFecha');

    
    // Modal de fecha - actualizado para manejar zonas horarias
    const modalFecha = document.getElementById('modalFecha');
    const calendarBtn = document.getElementById('calendarBtn');
    
    
    calendarBtn.addEventListener('click', () => {
        fechaInput.value = fechaActual;
        modalFecha.style.display = 'flex';
    });
    
    
    // Cerrar modal
    cerrarModal.addEventListener('click', () => {
        modalFecha.style.display = 'none';
    });

    
    cancelarFecha.addEventListener('click', () => {
        modalFecha.style.display = 'none';
    });
    
    // Aplicar fecha seleccionada
    aplicarFecha.addEventListener('click', () => {
        const fechaSeleccionada = fechaInput.value;
        console.log('Fecha seleccionada:', fechaSeleccionada);
        
        // Enviar fecha exactamente como fue seleccionada
        cargarPersonasPresentes(fechaSeleccionada);
        modalFecha.style.display = 'none';
    });
    
    
    // Cerrar modal al hacer clic fuera
    window.addEventListener('click', (e) => {
        if (e.target === modalFecha) {
            modalFecha.style.display = 'none';
        }
    });
});