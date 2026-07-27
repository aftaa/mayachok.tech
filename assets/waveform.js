import WaveSurfer from 'wavesurfer.js';

export function initWaveform(containerId, audioUrl, peaksUrl, options = {}) {
    const container = document.getElementById(containerId);
    if (!container) return;

    const ws = WaveSurfer.create({
        container: `#${containerId}`,
        waveColor: '#6c63ff',
        progressColor: '#4a3fbf',
        cursorColor: '#ff6b6b',
        barWidth: 3,              // Ширина каждой палочки волны
        barGap: 2,                // Расстояние между палочками
        barRadius: 4,             // Закругление палочек
        barHeight: 1.2,           // Высота палочек (1 = полная)
        height: 120,              // Высота волны
        normalize: true,          // Нормализация громкости
        backend: 'MediaElement',
        mediaControls: true,      // Показать нативные контролы
        interact: true,           // Возможность кликать по волне
        dragToSeek: true,         // Возможность перетаскивать
        ...options
    });

    // Загружаем пики
    if (peaksUrl) {
        fetch(peaksUrl)
            .then(response => response.json())
            .then(peaksData => {
                // Используем данные из peaks.json
                if (peaksData.data && peaksData.length) {
                    ws.load(audioUrl, peaksData.data, peaksData.length);
                } else {
                    ws.load(audioUrl);
                }
            })
            .catch(() => {
                ws.load(audioUrl);
            });
    } else {
        ws.load(audioUrl);
    }

    return ws;
}
