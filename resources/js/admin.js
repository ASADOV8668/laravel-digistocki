import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

document.addEventListener('DOMContentLoaded', () => {
    const dataElement = document.getElementById('listing-trend-data');
    const canvas = document.querySelector('[data-admin-chart="listing-trend"]');

    if (!dataElement || !canvas) return;

    const data = JSON.parse(dataElement.textContent);
    new Chart(canvas, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'آگهی‌های ثبت‌شده',
                data: data.values,
                borderColor: '#eb073f',
                backgroundColor: 'rgba(235, 7, 63, 0.12)',
                borderWidth: 3,
                pointRadius: 4,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#eb073f',
                pointBorderWidth: 2,
                tension: 0.4,
                fill: true,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            locale: 'fa-IR',
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: { display: false },
                tooltip: { rtl: true, textDirection: 'rtl', displayColors: false, padding: 12 },
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { family: 'Vazirmatn' } } },
                y: { beginAtZero: true, ticks: { precision: 0, color: '#94a3b8', font: { family: 'Vazirmatn' } }, grid: { color: '#f1f5f9' } },
            },
        },
    });
});

Alpine.start();
