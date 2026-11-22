import Chart from 'chart.js/auto';

window.Chart = Chart;

window.initResultsChart = function (canvasId, labels, preData, postData) {
  const canvas = document.getElementById(canvasId);
  if (!canvas) return;

  const ctx = canvas.getContext('2d');
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [
        {
          label: 'Pre-Test (Rata-rata % Benar)',
          data: preData,
          backgroundColor: 'rgba(239, 68, 68, 0.7)',
          borderColor: '#EF4444',
          borderWidth: 1.5,
          borderRadius: 6,
        },
        {
          label: 'Post-Test (Rata-rata % Benar)',
          data: postData,
          backgroundColor: 'rgba(37, 99, 235, 0.7)',
          borderColor: '#2563EB',
          borderWidth: 1.5,
          borderRadius: 6,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true,
          max: 100,
          ticks: {
            callback: function (value) {
              return value + '%';
            },
          },
          title: {
            display: true,
            text: 'Tingkat Pemahaman (%)',
          },
        },
        x: {
          title: {
            display: true,
            text: 'Tujuan Pembelajaran (TP)',
          },
        },
      },
      plugins: {
        legend: {
          position: 'top',
        },
        tooltip: {
          callbacks: {
            label: function (context) {
              return context.dataset.label + ': ' + context.parsed.y + '%';
            },
          },
        },
      },
    },
  });
};
