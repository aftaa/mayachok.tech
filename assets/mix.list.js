console.log('🎵 mix.list.js загружен');

// ==========================================
// 1. РЕНДЕРИНГ СТАТИЧНОЙ ВОЛНЫ
// ==========================================

function renderWaveform(canvas, peaksData) {
    const ctx = canvas.getContext('2d');
    const width = canvas.width;
    const height = canvas.height;

    ctx.clearRect(0, 0, width, height);

    if (!peaksData || !peaksData.data || peaksData.data.length === 0) {
        return;
    }

    const data = peaksData.data;
    const step = Math.max(1, Math.floor(data.length / width));
    const middle = height / 2;

    for (let i = 0; i < width; i++) {
        const index = i * step;
        if (index >= data.length) break;

        const value = Math.abs(data[index]) / 100;
        const barHeight = Math.min(value * height / 2, middle);

        if (barHeight > 0.5) {
            ctx.fillStyle = '#6c63ff';
            ctx.fillRect(i, middle - barHeight, 1.5, barHeight);
            ctx.fillRect(i, middle, 1.5, barHeight);
        }
    }
}

// ==========================================
// 2. ИНИЦИАЛИЗАЦИЯ ВОЛН
// ==========================================

async function initWaveforms() {
    const containers = document.querySelectorAll('.waveform-static:not([data-waveform-initialized])');
    console.log(`📦 Рендеринг волн: ${containers.length} шт.`);

    for (const container of containers) {
        const canvas = container.querySelector('.waveform-canvas');
        const peaksUrl = container.dataset.peaksUrl;

        if (!canvas || !peaksUrl) continue;

        container.dataset.waveformInitialized = 'true';

        let attempts = 0;
        let width = 0;

        while (attempts < 5 && width === 0) {
            await new Promise(resolve => setTimeout(resolve, 50 * (attempts + 1)));
            const rect = container.getBoundingClientRect();
            width = rect.width || container.clientWidth || container.offsetWidth || 400;
            attempts++;
        }

        if (width === 0) {
            console.warn('⚠️ Ширина контейнера 0, пропускаем');
            continue;
        }

        canvas.width = width;
        canvas.height = 32;
        canvas.style.width = width + 'px';
        canvas.style.height = '32px';

        try {
            const response = await fetch(peaksUrl);
            if (!response.ok) throw new Error('Failed to fetch peaks');
            const data = await response.json();
            renderWaveform(canvas, data);
        } catch (error) {
            console.warn('⚠️ Не удалось загрузить волну:', error);
            const ctx = canvas.getContext('2d');
            ctx.fillStyle = 'rgba(108, 99, 255, 0.08)';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
        }
    }
}

// ==========================================
// 3. СИНХРОНИЗАЦИЯ КАРТОЧЕК
// ==========================================

function initMixCardsSync() {
    const cards = document.querySelectorAll('.mix-card:not([data-sync-initialized])');
    console.log(`📦 Синхронизация новых карточек: ${cards.length} шт.`);

    cards.forEach(card => {
        card.dataset.syncInitialized = 'true';

        const mixUuid = card.dataset.mixUuid;
        if (!mixUuid) return;

        const progressBar = card.querySelector('.mix-progress-bar');
        const progressOverlay = card.querySelector('.waveform-progress-overlay');
        const waveformContainer = card.querySelector('.waveform-static');
        const playBtn = card.querySelector('.play-btn');
        const clickOverlay = card.querySelector('.waveform-click-overlay');

        // --- Кнопка Play ---
        if (playBtn) {
            const newBtn = playBtn.cloneNode(true);
            playBtn.parentNode.replaceChild(newBtn, playBtn);

            newBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();

                if (window.player.trackId === mixUuid && window.player.isPlaying) {
                    window.player.pause();
                    return;
                }

                if (window.player.isPlaying) {
                    window.player.pause();
                }

                const title = newBtn.dataset.title || 'Без названия';
                const artist = newBtn.dataset.artist || 'Неизвестный исполнитель';
                const duration = parseInt(newBtn.dataset.duration) || 0;

                fetch(`/api/stream/${mixUuid}`)
                    .then(r => {
                        if (!r.ok) throw new Error('Failed to get stream URL');
                        return r.json();
                    })
                    .then(data => {
                        const peaksUrl = newBtn.dataset.peaksUrl || null;
                        window.player.loadTrack(mixUuid, data.url, peaksUrl, title, artist, duration);
                        setTimeout(() => window.player.play(), 100);
                    })
                    .catch(err => {
                        console.error('❌ Stream error:', err);
                        alert('Не удалось загрузить трек');
                    });
            });
        }

        // --- Клик по волне ---
        if (clickOverlay && waveformContainer) {
            clickOverlay.addEventListener('click', (e) => {
                if (!window.player) return;

                if (window.player.trackId !== mixUuid) {
                    const btn = card.querySelector('.play-btn');
                    if (btn) btn.click();
                    return;
                }

                const rect = waveformContainer.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const progress = Math.min(1, Math.max(0, x / rect.width));
                window.player.seek(progress);
            });

            clickOverlay.addEventListener('mouseenter', () => {
                if (waveformContainer) {
                    waveformContainer.style.background = 'rgba(108, 99, 255, 0.12)';
                }
            });
            clickOverlay.addEventListener('mouseleave', () => {
                if (waveformContainer) {
                    const isActive = window.player?.trackId === mixUuid;
                    if (!isActive) {
                        waveformContainer.style.background = 'rgba(108, 99, 255, 0.04)';
                    }
                }
            });
        }

        // --- Обновление прогресса ---
        const updateProgress = (data) => {
            const isCurrentTrack = window.player?.trackId === mixUuid;

            if (isCurrentTrack) {
                const progress = data?.progress || 0;

                if (progressBar) progressBar.style.width = progress + '%';
                if (progressOverlay) progressOverlay.style.width = progress + '%';
                if (waveformContainer) {
                    waveformContainer.style.background = 'rgba(108, 99, 255, 0.08)';
                }
            }
        };

        // --- Обновление состояния ---
        const updateCardState = () => {
            const isCurrentTrack = window.player?.trackId === mixUuid;
            const isPlaying = window.player?.isPlaying || false;

            card.classList.toggle('playing', isCurrentTrack && isPlaying);

            const btn = card.querySelector('.play-btn');
            if (btn) {
                if (isCurrentTrack && isPlaying) {
                    btn.innerHTML = '⏸';
                    btn.classList.add('active');
                } else {
                    btn.innerHTML = '▶';
                    btn.classList.remove('active');
                }
            }

            const indicator = card.querySelector('.mix-playing-indicator');
            if (indicator) {
                indicator.style.display = isCurrentTrack && isPlaying ? 'flex' : 'none';
            }

            if (!isCurrentTrack) {
                if (progressBar) progressBar.style.width = '0%';
                if (progressOverlay) progressOverlay.style.width = '0%';
                if (waveformContainer && !waveformContainer.matches(':hover')) {
                    waveformContainer.style.background = 'rgba(108, 99, 255, 0.04)';
                }
            }
        };

        // --- Подписки ---
        if (window.player) {
            window.player.subscribe('timeupdate', updateProgress);
            window.player.subscribe('play', updateCardState);
            window.player.subscribe('pause', updateCardState);
            window.player.subscribe('load', updateCardState);
            window.player.subscribe('finish', () => {
                if (window.player?.trackId === mixUuid) {
                    if (progressBar) progressBar.style.width = '0%';
                    if (progressOverlay) progressOverlay.style.width = '0%';
                    card.classList.remove('playing');
                    const btn = card.querySelector('.play-btn');
                    if (btn) {
                        btn.innerHTML = '▶';
                        btn.classList.remove('active');
                    }
                    const indicator = card.querySelector('.mix-playing-indicator');
                    if (indicator) indicator.style.display = 'none';
                    if (waveformContainer) {
                        waveformContainer.style.background = 'rgba(108, 99, 255, 0.04)';
                    }
                }
            });
        }

        setTimeout(updateCardState, 50);
    });
}

// ==========================================
// 4. ЭКСПОРТ
// ==========================================

export function initMixList() {
    console.log('🎵 Инициализация списка миксов');

    // Сбрасываем флаги для новых элементов
    document.querySelectorAll('.waveform-static[data-waveform-initialized]').forEach(el => {
        // Не удаляем старые, только новые инициализируем
    });

    setTimeout(() => {
        requestAnimationFrame(() => {
            initWaveforms().then(() => {
                console.log('✅ Волны отрендерены');
            });
            setTimeout(initMixCardsSync, 200);
        });
    }, 150);
}

console.log('✅ mix.list.js готов');
