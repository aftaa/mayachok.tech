import '@hotwired/turbo';
import './stimulus_bootstrap.js';
import './styles/app.scss';
import 'player';
import 'upload';

// ✅ Импортируем инициализаторы
import { initMixList } from 'mix.list';
import { initMixShow } from 'mix.show';

document.getElementById('theme-toggle')?.addEventListener('click', () => {
    const html = document.documentElement;
    const current = html.getAttribute('data-bs-theme');
    html.setAttribute('data-bs-theme', current === 'dark' ? 'light' : 'dark');
});

document.addEventListener('DOMContentLoaded', function () {
    const banner = document.getElementById('cookie-banner');
    const acceptBtn = document.getElementById('cookie-accept');
    const declineBtn = document.getElementById('cookie-decline');

    if (!banner) return;

    function acceptCookies() {
        fetch('/api/cookies/accept', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        })
            .then(response => response.json())
            .then(() => {
                banner.style.display = 'none';
            })
            .catch(() => {
                banner.style.display = 'none';
            });
    }

    acceptBtn.addEventListener('click', acceptCookies);
    declineBtn.addEventListener('click', function () {
        banner.style.display = 'none';
    });
});

// ==========================================
// ИНИЦИАЛИЗАЦИЯ СТРАНИЦ
// ==========================================

function initPage() {
    console.log('🔄 Инициализация страницы...');

    // Проверяем, какая страница загружена
    if (document.getElementById('mix-show')) {
        initMixShow();
    } else if (document.querySelector('.mix-card')) {
        initMixList();
    }
}

// ✅ Обычная загрузка
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(initPage, 100);
});

// ✅ Turbo-переходы
document.addEventListener('turbo:load', () => {
    console.log('🔄 turbo:load');
    setTimeout(initPage, 150);
});

// ✅ Если страница уже загружена
if (document.readyState === 'complete' || document.readyState === 'interactive') {
    setTimeout(initPage, 200);
}

console.log('✅ app.js готов');
