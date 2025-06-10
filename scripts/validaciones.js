// validaciones.js MODIFICADO

// --- Mantén tus funciones existentes ---
// Función para validar y limpiar RUT chileno (versión mejorada)
function validarYLimpiarRUT(rut) {
    if (!rut || typeof rut !== 'string') return { valido: false, limpio: '' };
    const rutLimpio = rut.replace(/[.-]/g, '');
    const cuerpo = rutLimpio.slice(0, -1);
    const dv = rutLimpio.slice(-1).toUpperCase();
    if (!/^\d+$/.test(cuerpo)) return { valido: false, limpio: '' };
    let suma = 0;
    let multiplo = 2;
    for (let i = cuerpo.length - 1; i >= 0; i--) {
        suma += parseInt(cuerpo.charAt(i)) * multiplo;
        multiplo = multiplo === 7 ? 2 : multiplo + 1;
    }
    const dvEsperado = 11 - (suma % 11);
    const dvCalculado = dvEsperado === 11 ? '0' : dvEsperado === 10 ? 'K' : dvEsperado.toString();
    return {
        valido: dvCalculado === dv,
        limpio: cuerpo + dv
    };
}

// Función para mostrar errores
function mostrarError(input, mensaje) {
    const inputGroup = input.parentElement; // Asume que el input está directamente en un form-group
                                         // Si no, ajusta para encontrar el contenedor correcto
    let errorDisplay = inputGroup.querySelector('.error-message');
    if (!errorDisplay) {
        errorElement = document.createElement('div'); // Usar div o span según tu CSS
        errorElement.className = 'error-message text-danger mt-1'; // Clases de Bootstrap para errores
        inputGroup.appendChild(errorElement);
        errorDisplay = errorElement;
    }
    errorDisplay.textContent = mensaje;
    input.classList.add('is-invalid'); // Clase de Bootstrap para campos inválidos
}

// Función para limpiar errores
function limpiarError(input) {
    const inputGroup = input.parentElement;
    const errorDisplay = inputGroup.querySelector('.error-message');
    if (errorDisplay) {
        errorDisplay.remove();
    }
    input.classList.remove('is-invalid');
    input.classList.add('is-valid'); // Opcional: para mostrar feedback de validez
}

// --- Nueva función de validación para el formulario de INGRESO ---
function validarFormularioIngreso(event) {
    event.preventDefault();
    let esValido = true;
    const formulario = event.target;

    // Campos específicos del formIngreso en ingreso.php
    const rutInput = document.getElementById('rut');
    const nombreInput = document.getElementById('nombre');
    // El campo fechaIngreso ya tiene 'required' y el navegador lo maneja,
    // pero podrías añadir validación JS si necesitas algo más específico.

    // Validar RUT
    if (rutInput) {
        if (!rutInput.value.trim()) {
            mostrarError(rutInput, 'El RUT es requerido.');
            esValido = false;
        } else {
            const validacionRUT = validarYLimpiarRUT(rutInput.value);
            if (!validacionRUT.valido) {
                mostrarError(rutInput, 'El RUT no es válido.');
                esValido = false;
            } else {
                // Opcional: actualizar el valor del RUT al formato limpio si es necesario antes de enviar
                // rutInput.value = validacionRUT.limpio; 
                limpiarError(rutInput);
            }
        }
    } else {
        console.warn("Elemento con ID 'rut' no encontrado para validación.");
    }

    // Validar Nombre
    if (nombreInput) {
        if (!nombreInput.value.trim()) {
            mostrarError(nombreInput, 'El Nombre Completo es requerido.');
            esValido = false;
        } else {
            limpiarError(nombreInput);
        }
    } else {
        console.warn("Elemento con ID 'nombre' no encontrado para validación.");
    }

    // Si todo es válido, enviar el formulario
    if (esValido) {
        // Remover el listener temporalmente para evitar loop si algo sale mal, aunque no debería ser necesario
        // formulario.removeEventListener('submit', validarFormularioIngreso);
        formulario.submit(); // Envía el formulario
    }
}

// --- Modificar la inicialización para apuntar al formulario de INGRESO ---
function iniciarValidacionesParaIngreso() {
    const formularioIngreso = document.getElementById('formIngreso'); // Apunta específicamente a tu formulario
    if (formularioIngreso) {
        formularioIngreso.addEventListener('submit', validarFormularioIngreso);

        // Mantener el formateo automático del RUT si lo deseas
        const rutInput = document.getElementById('rut');
        if (rutInput) {
            rutInput.addEventListener('input', formatearRUT); // Asumiendo que tienes formatearRUT
        }
    }
}

// --- Asegúrate de tener la función formatearRUT si la usas ---
function formatearRUT(e) {
    const cursorPos = e.target.selectionStart;
    const originalLength = e.target.value.length;
    let value = e.target.value.replace(/[^\dkK]/gi, ''); // Limpiar no dígitos y no K

    if (!value) {
        e.target.value = '';
        return;
    }

    let body = value.slice(0, -1);
    let dv = value.slice(-1).toUpperCase();

    if (value.length === 1 && (dv === 'K' || !isNaN(parseInt(dv)))) { // Solo DV
        body = '';
    } else if (!isNaN(parseInt(value.charAt(value.length -1)))) { // Si el último es número, todo es cuerpo
        body = value;
        dv = '';
    }


    let formattedBody = "";
    if (body) {
        // Formatear cuerpo con puntos
        let count = 0;
        for (let i = body.length - 1; i >= 0; i--) {
            formattedBody = body.charAt(i) + formattedBody;
            count++;
            if (count % 3 === 0 && i !== 0) {
                formattedBody = "." + formattedBody;
            }
        }
    }
    
    e.target.value = formattedBody + (dv ? "-" + dv : "");

    // Restaurar cursor
    const newLength = e.target.value.length;
    if (newLength === originalLength) {
         e.target.setSelectionRange(cursorPos, cursorPos);
    } else {
         e.target.setSelectionRange(cursorPos + (newLength - originalLength), cursorPos + (newLength - originalLength));
    }
}


// Ejecutar cuando el DOM esté cargado
document.addEventListener('DOMContentLoaded', iniciarValidacionesParaIngreso); // Llama a la función específica