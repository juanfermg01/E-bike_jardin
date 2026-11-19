/**
 * Gráficas del dashboard y de reportes.
 * Cada vista define window.CHART_DATA antes de cargar este archivo.
 */
document.addEventListener('DOMContentLoaded', function () {
    if (!window.CHART_DATA || typeof Chart === 'undefined') {
        return;
    }

    Chart.defaults.font.family = "'Segoe UI', system-ui, sans-serif";
    Chart.defaults.color = '#64776f';
    Chart.defaults.borderColor = 'rgba(16, 36, 28, .08)';

    const brand = '#0b6e4f';
    const brandLight = 'rgba(11, 110, 79, .18)';
    const lime = '#a7e75c';
    const palette = ['#0b6e4f', '#16a34a', '#84cc16', '#f59e0b', '#0ea5e9', '#ef4444', '#8b5cf6'];

    function makeChart(id, config) {
        const canvas = document.getElementById(id);
        if (!canvas) return;
        new Chart(canvas.getContext('2d'), config);
    }

    const data = window.CHART_DATA;

    if (data.ingresos) {
        makeChart('chartIngresos', {
            type: 'line',
            data: {
                labels: data.ingresos.labels,
                datasets: [{
                    label: data.ingresos.label || 'Ingresos (COP)',
                    data: data.ingresos.values,
                    borderColor: brand,
                    backgroundColor: brandLight,
                    fill: true,
                    tension: .38,
                    borderWidth: 3,
                    pointBackgroundColor: brand,
                    pointRadius: 4
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                return '$' + (value / 1000).toLocaleString('es-CO') + 'k';
                            }
                        }
                    }
                }
            }
        });
    }

    if (data.estados) {
        makeChart('chartEstados', {
            type: 'doughnut',
            data: {
                labels: data.estados.labels,
                datasets: [{
                    data: data.estados.values,
                    backgroundColor: ['#16a34a', '#f59e0b', '#0ea5e9', '#475569'],
                    borderWidth: 0,
                    hoverOffset: 6
                }]
            },
            options: {
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 18 } }
                }
            }
        });
    }

    if (data.estaciones) {
        makeChart('chartEstaciones', {
            type: 'bar',
            data: {
                labels: data.estaciones.labels,
                datasets: [{
                    label: 'Bicicletas disponibles',
                    data: data.estaciones.values,
                    backgroundColor: brand,
                    borderRadius: 8,
                    maxBarThickness: 38
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }

    if (data.energia) {
        makeChart('chartEnergia', {
            type: 'bar',
            data: {
                labels: data.energia.labels,
                datasets: [{
                    label: 'kWh consumidos',
                    data: data.energia.values,
                    backgroundColor: lime,
                    borderRadius: 8,
                    maxBarThickness: 34
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    if (data.alquileres) {
        makeChart('chartAlquileres', {
            type: 'bar',
            data: {
                labels: data.alquileres.labels,
                datasets: [{
                    label: data.alquileres.label || 'Alquileres',
                    data: data.alquileres.values,
                    backgroundColor: palette[2],
                    borderRadius: 8,
                    maxBarThickness: 30
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }

    if (data.tiposPago) {
        makeChart('chartTiposPago', {
            type: 'pie',
            data: {
                labels: data.tiposPago.labels,
                datasets: [{
                    data: data.tiposPago.values,
                    backgroundColor: palette,
                    borderWidth: 0
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } } }
            }
        });
    }
});
