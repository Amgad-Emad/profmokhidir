/*
 | The visitors chart on /admin/analytics (loaded on that page only). A calm
 | line in the theme's line colour for the period, a dashed brass line for the
 | comparison period. Colours are read from hidden samples styled with the
 | --chart-* tokens, and read again when the theme changes. The table under the
 | chart carries the same figures for screen readers and keyboard users.
 */
import { CategoryScale, Chart, LinearScale, LineController, LineElement, PointElement, Tooltip } from 'chart.js';

Chart.register(LineController, LineElement, PointElement, LinearScale, CategoryScale, Tooltip);

const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

function inks(root) {
    const ink = (name) => getComputedStyle(root.querySelector(`[data-ink="${name}"]`)).color;
    return { line: ink('line'), compare: ink('compare'), grid: ink('grid'), label: ink('label'), panel: ink('panel') };
}

function font() {
    return { family: getComputedStyle(document.body).fontFamily, size: 15, weight: 400 };
}

function options(colours, labels) {
    return {
        responsive: true,
        maintainAspectRatio: false,
        animation: reduceMotion.matches ? false : { duration: 1200, easing: 'easeOutQuart' },
        interaction: { mode: 'index', intersect: false },
        scales: {
            x: {
                grid: { display: false },
                border: { color: colours.grid },
                ticks: { color: colours.label, font: font(), maxRotation: 0, autoSkipPadding: 24 },
            },
            y: {
                beginAtZero: true,
                grid: { color: colours.grid, drawTicks: false },
                border: { display: false },
                ticks: { color: colours.label, font: font(), padding: 8, precision: 0 },
            },
        },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: colours.panel,
                titleColor: colours.line,
                bodyColor: colours.line,
                borderColor: colours.grid,
                borderWidth: 1,
                padding: 12,
                titleFont: { ...font(), weight: 500 },
                bodyFont: font(),
                callbacks: {
                    title: (items) => labels.current[items[0].dataIndex],
                    label: (item) => `${item.datasetIndex === 0 ? labels.currentName : labels.previousName}: ${item.formattedValue}`,
                },
            },
        },
    };
}

function datasets(series, colours) {
    return [
        {
            data: series.map((point) => point.visitors),
            borderColor: colours.line,
            backgroundColor: colours.line,
            borderWidth: 1.5,
            pointRadius: 0,
            pointHoverRadius: 3,
            tension: 0.35,
        },
        {
            data: series.map((point) => point.previous),
            borderColor: colours.compare,
            backgroundColor: colours.compare,
            borderWidth: 1,
            borderDash: [4, 4],
            pointRadius: 0,
            pointHoverRadius: 3,
            tension: 0.35,
        },
    ];
}

function mount(root) {
    const series = JSON.parse(root.dataset.series || '[]');
    const canvas = root.querySelector('canvas');
    if (!canvas || series.length === 0) return;

    const labels = {
        current: series.map((point) => point.label),
        currentName: root.dataset.currentName,
        previousName: root.dataset.previousName,
    };
    let colours = inks(root);
    const chart = new Chart(canvas, {
        type: 'line',
        data: { labels: labels.current, datasets: datasets(series, colours) },
        options: options(colours, labels),
    });

    const repaint = () => {
        colours = inks(root);
        chart.data.datasets = datasets(series, colours);
        chart.options = options(colours, labels);
        chart.update('none');
    };

    new MutationObserver(repaint).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme', 'class', 'style'] });
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', repaint);
}

document.querySelectorAll('[data-visitors-chart]').forEach(mount);
