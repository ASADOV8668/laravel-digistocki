import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

window.imagePicker = (maxMb = 5, maxFiles = 8) => ({
    previews: [],
    invalid: false,
    message: '',
    select(event) {
        this.revokePreviews();
        const files = Array.from(event.target.files || []);
        const oversized = files.find((file) => file.size > maxMb * 1024 * 1024);
        this.invalid = files.length > maxFiles || Boolean(oversized);
        this.message = files.length > maxFiles ? `حداکثر ${maxFiles} تصویر قابل انتخاب است.` : oversized ? `حجم تصویر «${oversized.name}» بیشتر از ${maxMb} مگابایت است.` : '';
        this.previews = files.slice(0, maxFiles).map((file) => ({ name: file.name, url: URL.createObjectURL(file) }));
    },
    revokePreviews() {
        this.previews.forEach((preview) => URL.revokeObjectURL(preview.url));
        this.previews = [];
    },
});

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

    const statusElement = document.getElementById('listing-status-data');
    const statusCanvas = document.querySelector('[data-admin-chart="listing-status"]');
    if (!statusElement || !statusCanvas) return;
    const statusData = JSON.parse(statusElement.textContent);
    new Chart(statusCanvas, {
        type: 'doughnut',
        data: {
            labels: statusData.labels,
            datasets: [{ data: statusData.values, backgroundColor: ['#f59e0b', '#10b981', '#ef4444', '#0ea5e9', '#94a3b8'], borderWidth: 0, hoverOffset: 5 }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: { legend: { position: 'bottom', rtl: true, labels: { usePointStyle: true, padding: 16, font: { family: 'Vazirmatn' } } }, tooltip: { rtl: true, textDirection: 'rtl' } },
        },
    });
});

Alpine.start();
