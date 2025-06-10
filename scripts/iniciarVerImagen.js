function iniciarVerImagen(inputId, vistaId, iconoId) {
    const input = document.getElementById(inputId);
    const vista = document.getElementById(vistaId);
    const icono = document.getElementById(iconoId);

    if (input && vista && icono) {
        input.addEventListener('change', function() {
            const archivo = this.files[0];
            if (archivo) {
                const lector = new FileReader();

                lector.addEventListener('load', function() {
                    vista.src = this.result;
                    vista.style.display = 'block';
                    icono.style.display = 'none';
                });

                lector.readAsDataURL(archivo);
            } else {
                vista.src = '';
                vista.style.display = 'none';
                icono.style.display = 'block';
            }
        });
    }
}