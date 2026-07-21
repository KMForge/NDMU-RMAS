import Chart from 'chart.js/auto';

export function renderChart(element, configuration) {
    return new Chart(element, configuration);
}
