document.addEventListener('DOMContentLoaded', function () {
    // --- Lógica del botón volver ---
    if (typeof iniciarVolver === "function") {
        iniciarVolver('backButton', 'indexEmpleado.html');
    }

    // --- Lógica básica del menú ---
    const menuToggle = document.getElementById('menuToggle');
    const mobileMenu = document.getElementById('mobileMenu');
    const closeMenu = document.getElementById('closeMenu');

    if (menuToggle && mobileMenu) {
        menuToggle.addEventListener('click', () => {
            mobileMenu.classList.toggle('active');
        });
    }
    if (closeMenu && mobileMenu) {
        closeMenu.addEventListener('click', () => {
            mobileMenu.classList.remove('active');
        });
    }

    // --- Lógica del formulario de Ingreso ---
    const fechaInput = document.getElementById('fechaIngreso');
    const horaDisplay = document.getElementById('horaIngreso');
    const autoCompletarSwitch = document.getElementById('autoCompletar');
    const rutInput = document.getElementById('rut');
    const nombreInput = document.getElementById('nombre');
    const formIngreso = document.getElementById('formIngreso');

    // 1. Establecer la fecha actual por defecto (si no está ya establecida)
    if (fechaInput && !fechaInput.value) {
        const hoy = new Date();
        const yyyy = hoy.getFullYear();
        const mm = String(hoy.getMonth() + 1).padStart(2, '0');
        const dd = String(hoy.getDate()).padStart(2, '0');
        fechaInput.value = `${yyyy}-${mm}-${dd}`;
    }

    // 2. Mostrar la hora actual
    function actualizarHora() {
        const ahora = new Date();
        const hh = String(ahora.getHours()).padStart(2, '0');
        const min = String(ahora.getMinutes()).padStart(2, '0');
        const ss = String(ahora.getSeconds()).padStart(2, '0');
        if (horaDisplay) {
            horaDisplay.textContent = `${hh}:${min}:${ss} (Se registrará automáticamente)`;
        }
    }
    setInterval(actualizarHora, 1000);
    actualizarHora();

    // 3. Lógica del switch (autocompletar)
    if (autoCompletarSwitch) {
        autoCompletarSwitch.addEventListener('change', function () {
            if (this.checked) {
                // Obtener datos del usuario mediante AJAX
                fetch('obtener_usuario.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            rutInput.value = data.rut;
                            nombreInput.value = data.nombre;
                            rutInput.readOnly = true;
                            nombreInput.readOnly = true;
                        }
                    })
                    .catch(error => console.error('Error:', error));
            } else {
                rutInput.readOnly = false;
                nombreInput.readOnly = false;
            }
        });
    }

    
});