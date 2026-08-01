console.log('🚀 player.js загружается...');

import WaveSurfer from 'wavesurfer.js';

console.log('✅ WaveSurfer импортирован:', typeof WaveSurfer);

class PlayerFacade {
    constructor() {
        this.trackId = null;
        this.audio = new Audio();
        this.observers = new Map();
        this.isPlaying = false;

        // Инициализируем волну
        this.waveform = WaveSurfer.create({
            container: '#player-waveform',
            waveColor: '#6c63ff',
            progressColor: '#4a3fbf',
            cursorColor: '#ff6b6b',
            barWidth: 2,
            barGap: 1,
            barRadius: 3,
            height: 40,
            normalize: true,
            backend: 'MediaElement',
            mediaControls: false,
            interact: true,
            dragToSeek: true,
        });

        // Настройки волны
        this.waveform.on('ready', () => {
            this.notify('ready', {
                duration: this.waveform.getDuration()
            });
        });

        this.waveform.on('play', () => {
            this.isPlaying = true;
            this.notify('play');
        });

        this.waveform.on('pause', () => {
            this.isPlaying = false;
            this.notify('pause');
        });

        this.waveform.on('finish', () => {
            this.isPlaying = false;
            this.notify('finish');
        });

        this.waveform.on('timeupdate', (currentTime) => {
            this.notify('timeupdate', {
                currentTime,
                duration: this.waveform.getDuration()
            });
        });

        this.waveform.on('seeking', (progress) => {
            this.notify('seeking', progress);
        });

        // Обработчик клика по волне для перемотки
        this.waveform.on('click', (e) => {
            const progress = this.waveform.getCurrentTime() / this.waveform.getDuration();
            this.seek(progress);
            this.notify('seek', progress);
        });
    }

    // Facade методы
    loadTrack(mixId, audioUrl, peaksUrl, title, artist) {
        this.trackId = mixId;

        // Обновляем UI
        document.getElementById('player-title').textContent = title || 'Без названия';
        document.getElementById('player-artist').textContent = artist || 'Неизвестный исполнитель';

        // Загружаем аудио в волну
        if (peaksUrl) {
            fetch(peaksUrl)
                .then(response => response.json())
                .then(data => {
                    if (data.data && data.length) {
                        this.waveform.load(audioUrl, data.data, data.length);
                    } else {
                        this.waveform.load(audioUrl);
                    }
                })
                .catch(() => {
                    this.waveform.load(audioUrl);
                });
        } else {
            this.waveform.load(audioUrl);
        }

        this.notify('load', {mixId, title, artist});
    }

    play() {
        this.waveform.play();
    }

    pause() {
        this.waveform.pause();
    }

    playPause() {
        if (this.isPlaying) {
            this.pause();
        } else {
            this.play();
        }
    }

    seek(progress) {
        const duration = this.waveform.getDuration();
        if (duration) {
            this.waveform.seekTo(progress);
        }
    }

    getCurrentTime() {
        return this.waveform.getCurrentTime() || 0;
    }

    getDuration() {
        return this.waveform.getDuration() || 0;
    }

    // Observer методы
    subscribe(event, callback) {
        if (!this.observers.has(event)) {
            this.observers.set(event, new Set());
        }
        this.observers.get(event).add(callback);
    }

    notify(event, data = null) {
        if (this.observers.has(event)) {
            this.observers.get(event).forEach(cb => cb(data));
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // Создаем глобальный экземпляр
    window.player = new PlayerFacade();

    const playBtn = document.getElementById('player-play');
    const timeDisplay = document.getElementById('player-time');
    const durationDisplay = document.getElementById('player-duration');
    const cover = document.getElementById('player-cover');

    if (playBtn) {
        playBtn.addEventListener('click', () => {
            window.player.playPause();
        });
    }

    // Обновляем время
    window.player.subscribe('timeupdate', ({currentTime, duration}) => {
        timeDisplay.textContent = formatTime(currentTime);
        durationDisplay.textContent = formatTime(duration);
    });

    window.player.subscribe('play', () => {
        playBtn.innerHTML = '<i class="bi bi-pause-fill"></i>';
        playBtn.classList.add('bg-primary');
    });

    window.player.subscribe('pause', () => {
        playBtn.innerHTML = '<i class="bi bi-play-fill"></i>';
    });

    window.player.subscribe('finish', () => {
        playBtn.innerHTML = '<i class="bi bi-play-fill"></i>';
        timeDisplay.textContent = '0:00';
    });

    // Обновляем обложку при загрузке
    window.player.subscribe('load', ({title, artist}) => {
        // Можно менять цвет обложки или показывать первую букву
        const icon = cover.querySelector('.player-cover-icon');
        if (icon) {
            icon.textContent = title ? title.charAt(0).toUpperCase() : '🎵';
        }
    });

    document.addEventListener('click', function (e) {
        const playBtn = e.target.closest('.play-btn');
        if (!playBtn) return;

        e.preventDefault();

        const mixId = playBtn.dataset.mixId;
        const audioUrl = playBtn.dataset.audioUrl;
        const peaksUrl = playBtn.dataset.peaksUrl || null;
        const title = playBtn.dataset.title || 'Без названия';
        const artist = playBtn.dataset.artist || 'Неизвестный исполнитель';

        // Загружаем трек
        window.player.loadTrack(mixId, audioUrl, peaksUrl, title, artist);

        // Воспроизводим
        setTimeout(() => window.player.play(), 100);
    });

    console.log('✅ window.player создан:', window.player);
    console.log('✅ Элемент turbo-player:', document.getElementById('turbo-player'));
});

// Форматирование времени
function formatTime(seconds) {
    if (!seconds || isNaN(seconds)) return '0:00';
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs.toString().padStart(2, '0')}`;
}
