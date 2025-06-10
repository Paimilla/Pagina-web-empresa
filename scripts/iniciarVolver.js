function iniciarVolver(botonId, url) {
    // Asegurarnos que la URL termine en .php
    url = url.replace('.html', '.php');
    
    const boton = document.getElementById(botonId);
    if (boton) {
        boton.addEventListener('click', function() {
            window.location.href = url;
        });
    }
}