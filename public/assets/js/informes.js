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
    const promedioCanvas = document.getElementById('grafico-promedio-unidad');
    if (typeof window.Chart === 'undefined') {
        return;
    }

    if (canvas) {
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
    }

    if (promedioCanvas) {
        const unidades = JSON.parse(promedioCanvas.dataset.unidades || '[]');
        const promedios = JSON.parse(promedioCanvas.dataset.promedios || '[]');

        new window.Chart(promedioCanvas, {
            type: 'bar',
            data: {
                labels: JSON.parse(promedioCanvas.dataset.labels || '[]'),
                datasets: [
                    {
                        label: 'Promedio',
                        data: promedios,
                        backgroundColor: '#17a2b8',
                    },
                ],
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (valor) {
                                return new Intl.NumberFormat('es-AR', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2,
                                }).format(valor);
                            },
                        },
                    },
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function (contexto) {
                                const unidad = unidades[contexto.dataIndex];
                                const valor = contexto.parsed.y;
                                const importe = new Intl.NumberFormat('es-AR', {
                                    style: 'currency',
                                    currency: 'ARS',
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2,
                                }).format(valor);

                                return unidad === 'Moneda fija'
                                    ? 'Monto promedio: ' + importe
                                    : 'Promedio: ' + new Intl.NumberFormat('es-AR', {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2,
                                    }).format(valor);
                            },
                        },
                    },
                },
            },
        });
    }
}());
