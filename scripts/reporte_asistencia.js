// scripts/reporte_asistencia.js

document.addEventListener('DOMContentLoaded', function() {
    // Elementos del DOM
    const filtrosBtns = document.querySelectorAll('.filtro-btn');
    const empleadosCards = document.querySelectorAll('.empleado-card');
    const calendarBtn = document.getElementById('calendarBtn');
    const modalFecha = document.getElementById('modalFecha');
    const cerrarModal = document.getElementById('cerrarModal');
    const cancelarFecha = document.getElementById('cancelarFecha');
    const aplicarFecha = document.getElementById('aplicarFecha');
    const fechaInput = document.getElementById('fechaInput');
    const exportarExcel = document.getElementById('exportarExcel');
    

    // Manejo de filtros
    filtrosBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Remover clase active de todos los botones
            filtrosBtns.forEach(b => b.classList.remove('active'));
            // Agregar clase active al botón clickeado
            this.classList.add('active');
            
            const filtro = this.dataset.filtro;
            filtrarEmpleados(filtro);
        });
    });

    // Función para filtrar empleados
    function filtrarEmpleados(filtro) {
        empleadosCards.forEach(card => {
            const estado = card.dataset.estado;
            
            if (filtro === 'todos') {
                card.style.display = 'flex';
            } else if (filtro === estado) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
        
        // Actualizar contador de empleados visibles
        actualizarContador();
    }

    // Función para actualizar contador
    function actualizarContador() {
        const empleadosVisibles = document.querySelectorAll('.empleado-card[style*="flex"], .empleado-card:not([style*="none"])');
        const contador = empleadosVisibles.length;
        
        // Actualizar el contador en la interfaz si existe
        const contadorElement = document.getElementById('contador-empleados');
        if (contadorElement) {
            contadorElement.textContent = contador;
        }
    }

    // Manejo del modal de fecha
    calendarBtn.addEventListener('click', function() {
        modalFecha.style.display = 'flex';
    });

    cerrarModal.addEventListener('click', function() {
        modalFecha.style.display = 'none';
    });

    cancelarFecha.addEventListener('click', function() {
        modalFecha.style.display = 'none';
    });

    aplicarFecha.addEventListener('click', function() {
        const fechaSeleccionada = fechaInput.value;
        if (fechaSeleccionada) {
            // Recargar la página con la nueva fecha
            window.location.href = `?fecha=${fechaSeleccionada}`;
        }
    });

    // Cerrar modal al hacer click fuera
    modalFecha.addEventListener('click', function(e) {
        if (e.target === modalFecha) {
            modalFecha.style.display = 'none';
        }
    });

    // Manejo de exportación
    exportarExcel.addEventListener('click', function() {
        const fecha = fechaInput.value || new Date().toISOString().split('T')[0];
        window.open(`exportar_reporte.php?formato=excel&fecha=${fecha}`, '_blank');
    });

    

    // Actualizar reloj en tiempo real para empleados trabajando
    setInterval(actualizarTiemposTrabajando, 60000); // Actualizar cada minuto

    function actualizarTiemposTrabajando() {
        const empleadosTrabajando = document.querySelectorAll('.empleado-card[data-estado="trabajando"]');
        
        empleadosTrabajando.forEach(card => {
            const horaEntrada = card.querySelector('.empleado-hora.entrada');
            if (horaEntrada) {
                const textoHora = horaEntrada.textContent;
                const match = textoHora.match(/(\d{2}:\d{2})/);
                if (match) {
                    const horaEntradaStr = match[1];
                    const ahora = new Date();
                    const hoy = ahora.toISOString().split('T')[0];
                    const fechaEntrada = new Date(`${hoy}T${horaEntradaStr}:00`);
                    
                    const diff = ahora - fechaEntrada;
                    const horas = Math.floor(diff / (1000 * 60 * 60));
                    const minutos = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                    
                    const tiempoValor = card.querySelector('.tiempo-valor');
                    if (tiempoValor) {
                        tiempoValor.textContent = `${horas}h ${minutos}m`;
                    }
                }
            }
        });
    }

    // Funciones de búsqueda
    function agregarBusqueda() {
        const busquedaContainer = document.createElement('div');
        busquedaContainer.className = 'busqueda-container';
        busquedaContainer.innerHTML = `
            <div class="busqueda-input-container">
                <i class="fas fa-search"></i>
                <input type="text" id="busquedaEmpleado" placeholder="Buscar empleado...">
            </div>
        `;
        
        const filtrosContainer = document.querySelector('.filtros-container');
        filtrosContainer.parentNode.insertBefore(busquedaContainer, filtrosContainer);
        
        const busquedaInput = document.getElementById('busquedaEmpleado');
        busquedaInput.addEventListener('input', function() {
            const termino = this.value.toLowerCase();
            filtrarPorNombre(termino);
        });
    }

    function filtrarPorNombre(termino) {
        empleadosCards.forEach(card => {
            const nombre = card.querySelector('.empleado-nombre').textContent.toLowerCase();
            const coincide = nombre.includes(termino);
            
            if (coincide) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    }

    // Agregar funcionalidad de búsqueda
    agregarBusqueda();

    // Inicializar contador
    actualizarContador();
});

// Funciones globales para manejo de modales de edición

function abrirModalEdicion(idEmpleado, nombreEmpleado, entradasDetalle, salidasDetalle) {
    document.getElementById('idEmpleadoModal').value = idEmpleado;
    document.getElementById('nombreEmpleadoModal').textContent = nombreEmpleado;
    
    // Construir lista de registros existentes
    const registrosContainer = document.getElementById('registrosExistentes');
    registrosContainer.innerHTML = '<h4>Registros Existentes</h4>';
    
    // Procesar entradas
    if (entradasDetalle && entradasDetalle.trim()) {
        const entradas = entradasDetalle.split('|').filter(e => e.trim());
        entradas.forEach(entrada => {
            const [idRegistro, hora] = entrada.split(':');
            if (idRegistro && hora) {
                agregarRegistroALista(registrosContainer, idRegistro, 'entrada', hora);
            }
        });
    }
    
    // Procesar salidas
    if (salidasDetalle && salidasDetalle.trim()) {
        const salidas = salidasDetalle.split('|').filter(s => s.trim());
        salidas.forEach(salida => {
            const [idRegistro, hora] = salida.split(':');
            if (idRegistro && hora) {
                agregarRegistroALista(registrosContainer, idRegistro, 'salida', hora);
            }
        });
    }
    
    if (!entradasDetalle && !salidasDetalle) {
        registrosContainer.innerHTML += '<p>No hay registros para este día.</p>';
    }
    
    document.getElementById('modalEdicion').style.display = 'flex';
}

function agregarRegistroALista(container, idRegistro, tipo, hora) {
    const registroDiv = document.createElement('div');
    registroDiv.className = 'registro-item';
    registroDiv.innerHTML = `
        <div class="registro-info">
            <span class="registro-tipo ${tipo}">${tipo.toUpperCase()}</span>
            <span class="registro-hora">${hora}</span>
        </div>
        <div class="registro-acciones">
            <button class="btn-accion btn-editar" onclick="abrirModalModificar(${idRegistro}, '${tipo}', '${hora}')">
                <i class="fas fa-edit"></i>
            </button>
            <button class="btn-accion" style="background: #dc3545; color: white;" onclick="confirmarEliminar(${idRegistro})">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    container.appendChild(registroDiv);
}

function cerrarModalEdicion() {
    document.getElementById('modalEdicion').style.display = 'none';
    // Limpiar el formulario
    document.getElementById('formAgregarRegistro').reset();
}

function abrirModalModificar(idRegistro, tipo, hora) {
    document.getElementById('idRegistroModificar').value = idRegistro;
    document.getElementById('tipoRegistroModificar').value = tipo;
    document.getElementById('horaRegistroModificar').value = hora;
    
    // Cerrar modal de edición y abrir modal de modificación
    cerrarModalEdicion();
    document.getElementById('modalModificar').style.display = 'flex';
}

function cerrarModalModificar() {
    document.getElementById('modalModificar').style.display = 'none';
    document.getElementById('formModificarRegistro').reset();
}

function eliminarRegistro() {
    if (confirm('¿Está seguro de que desea eliminar este registro?')) {
        const idRegistro = document.getElementById('idRegistroModificar').value;
        document.getElementById('idRegistroEliminar').value = idRegistro;
        document.getElementById('formEliminarRegistro').submit();
    }
}

function confirmarEliminar(idRegistro) {
    if (confirm('¿Está seguro de que desea eliminar este registro?')) {
        document.getElementById('idRegistroEliminar').value = idRegistro;
        document.getElementById('formEliminarRegistro').submit();
    }
}

// Cerrar modales al hacer click fuera
document.addEventListener('click', function(e) {
    const modalEdicion = document.getElementById('modalEdicion');
    const modalModificar = document.getElementById('modalModificar');
    
    if (modalEdicion && e.target === modalEdicion) {
        cerrarModalEdicion();
    }
    
    if (modalModificar && e.target === modalModificar) {
        cerrarModalModificar();
    }
});

// Validaciones de formulario
document.addEventListener('DOMContentLoaded', function() {
    const formAgregar = document.getElementById('formAgregarRegistro');
    const formModificar = document.getElementById('formModificarRegistro');
    
    if (formAgregar) {
        formAgregar.addEventListener('submit', function(e) {
            const hora = document.getElementById('horaRegistro').value;
            if (!hora) {
                e.preventDefault();
                alert('Por favor, seleccione una hora.');
                return false;
            }
        });
    }
    
    if (formModificar) {
        formModificar.addEventListener('submit', function(e) {
            const hora = document.getElementById('horaRegistroModificar').value;
            if (!hora) {
                e.preventDefault();
                alert('Por favor, seleccione una hora.');
                return false;
            }
        });
    }
});