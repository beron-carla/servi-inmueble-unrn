(function () {
    'use strict';

    const servicio = document.getElementById('servicio');
    const unidad = document.getElementById('unidad');
    if (servicio && unidad) {
        servicio.addEventListener('change', function () {
            unidad.value = '';
        });
    }

    const canvas = document.getElementById('grafico-consumo-interanual');
    if (!canvas || typeof window.Chart === 'undefined') {
        return;
    }

    const labels = JSON.parse(canvas.dataset.labels || '[]');
    const base = JSON.parse(canvas.dataset.base || '[]');
    const comparado = JSON.parse(canvas.dataset.comparado || '[]');
    const etiqueta = canvas.dataset.etiqueta || 'Consumo';

    new window.Chart(canvas, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: canvas.dataset.labelBase,
                    data: base,
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    tension: 0.2,
                },
                {
                    label: canvas.dataset.labelComparado,
                    data: comparado,
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.2,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: etiqueta,
                    },
                },
            },
        },
    });
}());
