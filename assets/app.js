import '@hotwired/turbo';
import './stimulus_bootstrap.js';
import './styles/app.scss';

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
                // fallback: скрыть даже если ошибка
                banner.style.display = 'none';
            });
    }

    acceptBtn.addEventListener('click', acceptCookies);
    declineBtn.addEventListener('click', function () {
        banner.style.display = 'none';
        // Отказ не сохраняем — покажем снова при следующем визите
    });
});
