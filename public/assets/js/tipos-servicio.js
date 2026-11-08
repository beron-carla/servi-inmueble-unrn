document.addEventListener('DOMContentLoaded', function () {
    const tipo = document.querySelector('[data-unidades-select]');
    const unidad = document.querySelector('[data-unidad-medida]');

    if (!(tipo instanceof HTMLSelectElement) || !(unidad instanceof HTMLSelectElement)) {
        return;
    }

    let unidadInicial = unidad.dataset.initialValue || '';

    function actualizarUnidades() {
        const opcion = tipo.selectedOptions[0];
        const unidadesPermitidas = opcion && opcion.value !== ''
            ? (opcion.dataset.unidades || '').split('|')
            : [];

        Array.from(unidad.options).forEach(function (opcionUnidad) {
            if (opcionUnidad.value === '') {
                opcionUnidad.hidden = false;
                opcionUnidad.disabled = false;
                return;
            }

            const permitida = unidadesPermitidas.includes(opcionUnidad.value);
            opcionUnidad.hidden = !permitida;
            opcionUnidad.disabled = !permitida;
        });

        if (!unidadesPermitidas.includes(unidad.value)) {
            unidad.value = unidadesPermitidas.includes(unidadInicial) ? unidadInicial : '';
        }

        unidadInicial = '';
    }

    tipo.addEventListener('change', actualizarUnidades);
    actualizarUnidades();
});
