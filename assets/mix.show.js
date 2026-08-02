// assets/mix.show.js

console.log('🎵 mix.show.js загружен');

import WaveSurfer from 'wavesurfer.js';

function formatTime(seconds) {
    if (!seconds || isNaN(seconds)) return '0:00';
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs.toString().padStart(2, '0')}`;
}

let isMixShowInitialized = false;

export function initMixShow() {
    const container = document.getElementById('mix-show');
    if (!container) {
        console.warn('⚠️ Контейнер #mix-show не найден');
        return;
    }

    // ✅ Защита от повторной инициализации
    if (isMixShowInitialized) {
        console.log('ℹ️ Страница микса уже инициализирована');
        //return;
    }

    const mixUuid = container.dataset.mixUuid;
    const audioUrl = container.dataset.audioUrl;
    const peaksUrl = container.dataset.peaksUrl;
    const title = container.dataset.title || 'Без названия';
    const artist = container.dataset.artist || 'Неизвестный исполнитель';
    const duration = parseInt(container.dataset.duration) || 0;

    console.log(`🎵 Инициализация страницы микса: ${mixUuid}`);

    if (!window.player) {
        console.error('❌ window.player не найден!');
        return;
    }

    // ✅ ВСЕГДА СОЗДАЁМ ВОЛНУ на странице микса
    const waveformContainer = document.getElementById('mix-show-waveform');
    if (!waveformContainer) {
        console.warn('⚠️ Контейнер #mix-show-waveform не найден');
        return;
    }

    // ✅ Очищаем контейнер, если там уже есть волна
    waveformContainer.innerHTML = '';

    // ✅ Создаём волну
    const ws = WaveSurfer.create({
        container: waveformContainer,
        waveColor: '#6c63ff',
        progressColor: '#4a3fbf',
        cursorColor: '#ff6b6b',
        barWidth: 3,
        barGap: 2,
        barRadius: 4,
        height: 150,
        normalize: true,
        backend: 'MediaElement',
        mediaControls: false,
        interact: false,
        dragToSeek: false,
    });

    // ✅ Загружаем аудио в волну
    if (peaksUrl) {
        fetch(peaksUrl)
            .then(r => r.json())
            .then(data => {
                if (data.data && data.length) {
                    ws.load(audioUrl, data.data, data.length);
                } else {
                    ws.load(audioUrl);
                }
            })
            .catch(() => ws.load(audioUrl));
    } else {
        ws.load(audioUrl);
    }

    // ✅ Сохраняем волну в глобальном плеере
    window.player._mixShowWaveform = ws;

    // ✅ Если это новый трек — загружаем его в плеер
    if (window.player.trackId !== mixUuid) {
        window.player.loadTrack(mixUuid, audioUrl, peaksUrl, title, artist, duration);
    }

    // ✅ Кнопка Play/Pause
    const playBtn = document.getElementById('mix-show-play');
    if (playBtn) {
        const newBtn = playBtn.cloneNode(true);
        playBtn.parentNode.replaceChild(newBtn, playBtn);

        newBtn.addEventListener('click', () => {
            if (window.player.trackId === mixUuid && window.player.isPlaying) {
                window.player.pause();
            } else {
                window.player.play();
            }
        });
    }

    // ✅ Подписки на события
    const progressBar = document.getElementById('mix-show-progress');
    const timeEl = document.getElementById('mix-show-time');

    // Удаляем старые подписки
    if (window.player._mixShowUnsubscribe) {
        window.player._mixShowUnsubscribe();
    }

    const subscribers = [];

    const timeupdateHandler = (data) => {
        if (window.player.trackId === mixUuid) {
            if (progressBar) {
                progressBar.style.width = (data.progress || 0) + '%';
            }
            if (timeEl) {
                timeEl.textContent = formatTime(data.currentTime) + ' / ' + formatTime(data.duration);
            }
            // ✅ Синхронизируем волну на странице с плеером
            if (window.player._mixShowWaveform) {
                const progress = data.progress || 0;
                window.player._mixShowWaveform.seekTo(progress / 100);
            }
        }
    };
    window.player.subscribe('timeupdate', timeupdateHandler);
    subscribers.push({ event: 'timeupdate', handler: timeupdateHandler });

    const playHandler = () => {
        if (window.player.trackId === mixUuid) {
            const btn = document.getElementById('mix-show-play');
            if (btn) {
                btn.innerHTML = '<i class="bi bi-pause-fill"></i>';
                btn.classList.add('playing');
            }
        }
    };
    window.player.subscribe('play', playHandler);
    subscribers.push({ event: 'play', handler: playHandler });

    const pauseHandler = () => {
        if (window.player.trackId === mixUuid) {
            const btn = document.getElementById('mix-show-play');
            if (btn) {
                btn.innerHTML = '<i class="bi bi-play-fill"></i>';
                btn.classList.remove('playing');
            }
        }
    };
    window.player.subscribe('pause', pauseHandler);
    subscribers.push({ event: 'pause', handler: pauseHandler });

    const seekHandler = (progress) => {
        if (window.player.trackId === mixUuid && window.player._mixShowWaveform) {
            window.player._mixShowWaveform.seekTo(progress);
        }
    };
    window.player.subscribe('seek', seekHandler);
    subscribers.push({ event: 'seek', handler: seekHandler });

    window.player._mixShowUnsubscribe = () => {
        subscribers.forEach(({ event, handler }) => {
            window.player.unsubscribe(event, handler);
        });
        window.player._mixShowUnsubscribe = null;
    };

    // ✅ Обработчик клика по волне
    waveformContainer.addEventListener('click', (e) => {
        if (window.player.trackId !== mixUuid) {
            window.player.loadTrack(mixUuid, audioUrl, peaksUrl, title, artist, duration);
            setTimeout(() => window.player.play(), 100);
            return;
        }

        const rect = waveformContainer.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const progress = Math.min(1, Math.max(0, x / rect.width));
        window.player.seek(progress);
    });

    // ✅ Если плеер уже играет этот трек — синхронизируем UI
    if (window.player.trackId === mixUuid && window.player.isPlaying) {
        const progress = window.player.getProgress();
        if (progressBar) {
            progressBar.style.width = progress + '%';
        }
        if (timeEl) {
            timeEl.textContent = formatTime(window.player.getCurrentTime()) + ' / ' + formatTime(window.player.getDuration());
        }
        // ✅ Синхронизируем волну
        if (window.player._mixShowWaveform) {
            window.player._mixShowWaveform.seekTo(progress / 100);
        }
        if (playBtn) {
            playBtn.innerHTML = '<i class="bi bi-pause-fill"></i>';
            playBtn.classList.add('playing');
        }
    }

    isMixShowInitialized = true;

    console.log('✅ Волна на странице микса инициализирована');
}
