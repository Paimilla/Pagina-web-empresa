// scripts/cargarConfiguracion.js

// Función principal para cargar la configuración
function cargarConfiguracion() {
    const defaultSettings = {
        theme: 'default',
        emailNotif: true
    };
    
    const savedSettings = JSON.parse(localStorage.getItem('appSettings')) || defaultSettings;
    
    // Aplicar configuración
    if (document.getElementById('emailNotifications')) {
        document.getElementById('emailNotifications').checked = savedSettings.emailNotif;
    }
    
    // Activar tema guardado
    aplicarTema(savedSettings.theme);
    
    // Si estamos en la página de configuración, marcar el tema activo
    if (document.querySelector('.theme-option')) {
        document.querySelectorAll('.theme-option').forEach(opt => opt.classList.remove('active'));
        const activeTheme = document.querySelector(`.theme-option[data-theme="${savedSettings.theme}"]`);
        if (activeTheme) {
            activeTheme.classList.add('active');
        }
    }
}

// Función para aplicar el tema seleccionado
function aplicarTema(theme) {
    const root = document.documentElement;
    
    switch(theme) {
        case 'default':
            root.style.setProperty('--color-primary', '#5b88b2');
            root.style.setProperty('--color-secondary', '#f9f9f9');
            root.style.setProperty('--color-text', '#333');
            root.style.setProperty('--color-text-secondary', '#666');
            break;
        case 'dark':
            root.style.setProperty('--color-primary', '#222');
            root.style.setProperty('--color-secondary', '#333');
            root.style.setProperty('--color-text', '#fff');
            root.style.setProperty('--color-text-secondary', '#fff');
            
            break;
        case 'green':
            root.style.setProperty('--color-primary', '#4CAF50');
            root.style.setProperty('--color-secondary', '#E8F5E9');
            root.style.setProperty('--color-text', '#333');
            root.style.setProperty('--color-text-secondary', '#666');

            break;
        case 'purple':
            root.style.setProperty('--color-primary', '#9C27B0');
            root.style.setProperty('--color-secondary', '#F3E5F5');
            root.style.setProperty('--color-text', '#333');
            root.style.setProperty('--color-text-secondary', '#666');
            break;
    }
}

// Función para inicializar los listeners de la página de configuración
function initConfiguracionPage() {
    // Solo ejecutar si estamos en la página de configuración
    if (!document.querySelector('.config-container')) return;
    
    // Cambiar tema
    const themeOptions = document.querySelectorAll('.theme-option');
    themeOptions.forEach(option => {
        option.addEventListener('click', function() {
            themeOptions.forEach(opt => opt.classList.remove('active'));
            this.classList.add('active');
            
            // Aplicar tema seleccionado
            const theme = this.getAttribute('data-theme');
            aplicarTema(theme);
        });
    });
    
    // Guardar configuración
    document.getElementById('saveSettings').addEventListener('click', function() {
        const settings = {
            emailNotif: document.getElementById('emailNotifications').checked,
            theme: document.querySelector('.theme-option.active').getAttribute('data-theme')
        };
        
        // Guardar en localStorage
        localStorage.setItem('appSettings', JSON.stringify(settings));
        
        // Mostrar mensaje de éxito
        alert('Configuración guardada correctamente');
        
        // Aplicar cambios inmediatos
        aplicarTema(settings.theme);
    });
}

// Escuchar cambios en otras pestañas
window.addEventListener('storage', function(e) {
    if (e.key === 'appSettings') {
        cargarConfiguracion();
    }
});

// Cargar configuración cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    cargarConfiguracion();
    initConfiguracionPage();
});