console.log('🚀 player.js загружается...');

import WaveSurfer from 'wavesurfer.js';

console.log('✅ WaveSurfer импортирован:', typeof WaveSurfer);

// ==========================================
// 1. ФАСАД ПЛЕЕРА (единый источник правды)
// ==========================================

class PlayerFacade {
    constructor() {
        this.trackId = null;         // ID текущего трека (может быть uuid или id)
        this.isPlaying = false;
        this.currentTime = 0;
        this.duration = 0;
        this.observers = new Map();  // События и подписчики
        this.realDuration = 0;

        // Инициализируем волну (в футере)
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

        // Подписываемся на события WaveSurfer
        this.waveform.on('ready', () => {
            this.duration = this.waveform.getDuration();
            this.notify('ready', { duration: this.duration });
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
            this.currentTime = currentTime;

            // Используем реальную длительность из БД, если она есть
            const duration = this.realDuration || this.waveform.getDuration();

            const data = {
                currentTime: currentTime,
                duration: duration,
                progress: duration ? (currentTime / duration) * 100 : 0,
            };

            this.notify('timeupdate', data);
        });

        this.waveform.on('seeking', (progress) => {
            this.notify('seeking', progress);
        });

        // Клик по волне = перемотка
        this.waveform.on('click', () => {
            const progress = this.waveform.getCurrentTime() / this.waveform.getDuration();
            this.notify('seek', progress);
        });
    }

    // ----- Управление -----

    loadTrack(mixUuid, audioUrl, peaksUrl, title, artist, duration) {
        this.trackId = mixUuid;
        this.realDuration = duration; // ← сохраняем правильную длительность из БД

        // Обновляем UI
        const titleEl = document.getElementById('player-title');
        const artistEl = document.getElementById('player-artist');
        if (titleEl) titleEl.textContent = title || 'Без названия';
        if (artistEl) artistEl.textContent = artist || 'Неизвестный исполнитель';

        // Загружаем аудио
        if (peaksUrl) {
            fetch(peaksUrl)
                .then(r => r.json())
                .then(data => {
                    if (data.data && data.length) {
                        this.waveform.load(audioUrl, data.data, data.length);
                    } else {
                        this.waveform.load(audioUrl);
                    }
                })
                .catch(() => this.waveform.load(audioUrl));
        } else {
            this.waveform.load(audioUrl);
        }

        this.notify('load', { mixUuid, title, artist, duration });
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
        if (this.duration) {
            const targetTime = progress * this.duration;
            console.log(`🎯 Seek: progress=${progress}, targetTime=${targetTime}, duration=${this.duration}`);
            this.waveform.seekTo(progress);
        }
    }

    // ----- События (Observer) -----

    subscribe(event, callback) {
        if (!this.observers.has(event)) {
            this.observers.set(event, new Set());
        }
        this.observers.get(event).add(callback);
    }

    unsubscribe(event, callback) {
        if (this.observers.has(event)) {
            this.observers.get(event).delete(callback);
        }
    }

    notify(event, data = null) {
        if (this.observers.has(event)) {
            this.observers.get(event).forEach(cb => cb(data));
        }
    }

    // ----- Геттеры -----

    getCurrentTime() {
        return this.currentTime || 0;
    }

    getDuration() {
        return this.duration || 0;
    }

    getProgress() {
        return this.duration ? (this.currentTime / this.duration) * 100 : 0;
    }
}


// ==========================================
// 2. ИНИЦИАЛИЗАЦИЯ (с защитой)
// ==========================================

// Флаг, чтобы не инициализировать дважды
let isInitialized = false;

function initializePlayer() {
    if (isInitialized) {
        console.log('⚠️ Плеер уже инициализирован, пропускаем');
        return;
    }
    isInitialized = true;

    console.log('🎵 Инициализация плеера...');

    // Создаём глобальный экземпляр (если ещё не создан)
    if (!window.player) {
        console.log('🎵 Создаём глобальный плеер...');
        window.player = new PlayerFacade();
    }

    // ==========================================
    // 2.1 ФУТЕР (UI плеера)
    // ==========================================

    initFooterUI();
    initMixCards();
    initMixShow();

    console.log('✅ Плеер инициализирован');
}

// ==========================================
// 2.1 ФУТЕР (с защитой от дублирования)
// ==========================================

function initFooterUI() {
    // Проверяем, не инициализирован ли уже футер
    if (document.querySelector('#player-play[data-initialized]')) {
        console.log('ℹ️ Футер уже инициализирован');
        return;
    }

    const playBtn = document.getElementById('player-play');
    const timeDisplay = document.getElementById('player-time');
    const durationDisplay = document.getElementById('player-duration');
    const cover = document.getElementById('player-cover');

    // Кнопка Play/Pause в футере
    if (playBtn) {
        // Ставим флаг, что кнопка уже инициализирована
        playBtn.dataset.initialized = 'true';

        // Удаляем старые обработчики
        const newBtn = playBtn.cloneNode(true);
        playBtn.parentNode.replaceChild(newBtn, playBtn);

        newBtn.addEventListener('click', () => {
            window.player.playPause();
        });
    }

    // Обновление времени
    // Удаляем старые подписки (если есть)
    window.player.subscribe('timeupdate', ({ currentTime, duration }) => {
        if (timeDisplay) timeDisplay.textContent = formatTime(currentTime);
        if (durationDisplay) durationDisplay.textContent = formatTime(duration);
    });

    window.player.subscribe('play', () => {
        const btn = document.getElementById('player-play');
        if (btn) {
            btn.innerHTML = '<i class="bi bi-pause-fill"></i>';
            btn.classList.add('bg-primary');
        }
    });

    window.player.subscribe('pause', () => {
        const btn = document.getElementById('player-play');
        if (btn) {
            btn.innerHTML = '<i class="bi bi-play-fill"></i>';
            btn.classList.remove('bg-primary');
        }
    });

    window.player.subscribe('finish', () => {
        const btn = document.getElementById('player-play');
        if (btn) {
            btn.innerHTML = '<i class="bi bi-play-fill"></i>';
            btn.classList.remove('bg-primary');
        }
        if (timeDisplay) timeDisplay.textContent = '0:00';
    });

    window.player.subscribe('load', ({ title }) => {
        const icon = cover?.querySelector('.player-cover-icon');
        if (icon) {
            icon.textContent = title ? title.charAt(0).toUpperCase() : '🎵';
        }
    });

    console.log('✅ Футер инициализирован');
}

// ==========================================
// 2.2 КАРТОЧКИ МИКСОВ (с защитой от дублирования)
// ==========================================

function initMixCards() {
    const cards = document.querySelectorAll('.mix-card:not([data-initialized])');
    console.log(`📦 Найдено новых карточек: ${cards.length}`);

    cards.forEach(card => {
        // Ставим флаг, что карточка уже инициализирована
        card.dataset.initialized = 'true';

        const mixUuid = card.dataset.mixUuid;
        if (!mixUuid) return;

        // --- Кнопка Play ---
        const playBtnCard = card.querySelector('.play-btn');
        if (playBtnCard) {
            const newBtn = playBtnCard.cloneNode(true);
            playBtnCard.parentNode.replaceChild(newBtn, playBtnCard);

            newBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();

                const title = newBtn.dataset.title || 'Без названия';
                const artist = newBtn.dataset.artist || 'Неизвестный исполнитель';

                if (window.player.trackId === mixUuid && window.player.isPlaying) {
                    window.player.pause();
                    return;
                }

                fetch(`/api/stream/${mixUuid}`)
                    .then(r => {
                        if (!r.ok) throw new Error('Failed to get stream URL');
                        return r.json();
                    })
                    .then(data => {
                        const peaksUrl = newBtn.dataset.peaksUrl || null;
                        window.player.loadTrack(mixUuid, data.url, peaksUrl, title, artist);
                        setTimeout(() => window.player.play(), 100);
                    })
                    .catch(err => {
                        console.error('❌ Stream error:', err);
                        alert('Не удалось загрузить трек');
                    });
            });
        }

        // --- Прогресс-бар ---
        const progressBar = card.querySelector('.mix-progress-bar');
        const progressText = card.querySelector('.mix-progress-text');

        // Функция обновления прогресса
        const updateProgress = (data) => {
            if (window.player.trackId === mixUuid) {
                const progress = data.progress || 0;
                if (progressBar) progressBar.style.width = progress + '%';
                if (progressText) progressText.textContent = Math.round(progress) + '%';
            }
        };

        // Подписываемся на события
        window.player.subscribe('timeupdate', updateProgress);

        // Обновление состояния карточки
        window.player.subscribe('play', () => {
            if (window.player.trackId === mixUuid) {
                card.classList.add('playing');
                const btn = card.querySelector('.play-btn');
                if (btn) btn.innerHTML = '⏸';
            }
        });

        window.player.subscribe('pause', () => {
            if (window.player.trackId === mixUuid) {
                card.classList.remove('playing');
                const btn = card.querySelector('.play-btn');
                if (btn) btn.innerHTML = '▶';
            }
        });

        window.player.subscribe('load', () => {
            if (window.player.trackId !== mixUuid) {
                if (progressBar) progressBar.style.width = '0%';
                if (progressText) progressText.textContent = '0%';
                card.classList.remove('playing');
                const btn = card.querySelector('.play-btn');
                if (btn) btn.innerHTML = '▶';
            }
        });

        window.player.subscribe('finish', () => {
            if (window.player.trackId === mixUuid) {
                if (progressBar) progressBar.style.width = '0%';
                if (progressText) progressText.textContent = '0%';
                card.classList.remove('playing');
                const btn = card.querySelector('.play-btn');
                if (btn) btn.innerHTML = '▶';
            }
        });
    });
}

// ==========================================
// 2.3 СТРАНИЦА МИКСА (с защитой)
// ==========================================

function initMixShow() {
    const mixShowContainer = document.getElementById('mix-show');
    if (!mixShowContainer) return;

    // Проверяем, не инициализирован ли уже
    if (mixShowContainer.dataset.initialized) {
        console.log('ℹ️ Страница микса уже инициализирована');
        return;
    }
    mixShowContainer.dataset.initialized = 'true';

    const mixUuid = mixShowContainer.dataset.mixUuid;
    if (!mixUuid) return;

    console.log(`🎵 Инициализация страницы микса: ${mixUuid}`);

    // --- Синхронизация с плеером ---
    if (window.player.trackId === mixUuid) {
        const progress = window.player.getProgress();
        const isPlaying = window.player.isPlaying;

        const progressBar = document.getElementById('mix-show-progress');
        if (progressBar) progressBar.style.width = progress + '%';

        const playBtn = document.getElementById('mix-show-play');
        if (playBtn) {
            playBtn.innerHTML = isPlaying ? '⏸' : '▶';
        }
    }

    // --- Кнопка Play ---
    const playBtn = document.getElementById('mix-show-play');
    if (playBtn) {
        const newBtn = playBtn.cloneNode(true);
        playBtn.parentNode.replaceChild(newBtn, playBtn);

        newBtn.addEventListener('click', () => {
            if (window.player.trackId === mixUuid && window.player.isPlaying) {
                window.player.pause();
                return;
            }

            const audioUrl = mixShowContainer.dataset.audioUrl;
            const peaksUrl = mixShowContainer.dataset.peaksUrl || null;
            const title = mixShowContainer.dataset.title || 'Без названия';
            const artist = mixShowContainer.dataset.artist || 'Неизвестный исполнитель';

            if (window.player.trackId === mixUuid) {
                window.player.play();
            } else {
                window.player.loadTrack(mixUuid, audioUrl, peaksUrl, title, artist);
                setTimeout(() => window.player.play(), 100);
            }
        });
    }

    // Подписываемся на события
    window.player.subscribe('timeupdate', (data) => {
        if (window.player.trackId === mixUuid) {
            const progressBar = document.getElementById('mix-show-progress');
            if (progressBar) progressBar.style.width = (data.progress || 0) + '%';

            const timeEl = document.getElementById('mix-show-time');
            if (timeEl) {
                timeEl.textContent = formatTime(data.currentTime) + ' / ' + formatTime(data.duration);
            }
        }
    });

    window.player.subscribe('play', () => {
        if (window.player.trackId === mixUuid) {
            const btn = document.getElementById('mix-show-play');
            if (btn) btn.innerHTML = '⏸';
        }
    });

    window.player.subscribe('pause', () => {
        if (window.player.trackId === mixUuid) {
            const btn = document.getElementById('mix-show-play');
            if (btn) btn.innerHTML = '▶';
        }
    });

    window.player.subscribe('load', () => {
        if (window.player.trackId !== mixUuid) {
            const progressBar = document.getElementById('mix-show-progress');
            if (progressBar) progressBar.style.width = '0%';
            const btn = document.getElementById('mix-show-play');
            if (btn) btn.innerHTML = '▶';

            const timeEl = document.getElementById('mix-show-time');
            if (timeEl) {
                timeEl.textContent = '0:00 / ' + formatTime(window.player.getDuration());
            }
        }
    });
}

// ==========================================
// 3. ЗАПУСК
// ==========================================

// Для обычной загрузки (не Turbo)
document.addEventListener('DOMContentLoaded', () => {
    console.log('📄 DOMContentLoaded');
    initializePlayer();
});

// Для Turbo-переходов
document.addEventListener('turbo:load', () => {
    console.log('🔄 turbo:load');
    // Не пересоздаём, только инициализируем новые элементы
    // (флаг isInitialized не даёт инициализировать заново)
    initMixCards();
    initMixShow();
});

// Для безопасности — если страница загружена не через Turbo
if (document.readyState === 'complete' || document.readyState === 'interactive') {
    console.log('⚡ Страница уже загружена');
    setTimeout(initializePlayer, 50);
}

// ==========================================
// 4. УТИЛИТЫ
// ==========================================

function formatTime(seconds) {
    if (!seconds || isNaN(seconds)) return '0:00';
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs.toString().padStart(2, '0')}`;
}
