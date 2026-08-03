window.subscribeToProgress = function(mixId) {
    console.log('🔄 Подписываемся на прогресс микса #' + mixId);

    const progressLabel = document.getElementById('progress-label');
    const progressBar = document.getElementById('progress-bar');
    const progressPercent = document.getElementById('progress-percent');
    const resultMessage = document.getElementById('result-message');

    // ✅ Сохраняем ссылку на EventSource для закрытия
    let eventSource = new EventSource(`/.well-known/mercure?topic=mix/progress/${mixId}`);

    // ✅ Сохраняем таймаут для переподключения
    let reconnectTimeout = null;

    // ✅ Функция закрытия соединения
    function closeConnection() {
        if (reconnectTimeout) {
            clearTimeout(reconnectTimeout);
            reconnectTimeout = null;
        }
        if (eventSource) {
            eventSource.close();
            eventSource = null;
        }
    }

    eventSource.onopen = function() {
        console.log('✅ EventSource открыт для микса #' + mixId);
    };

    eventSource.onmessage = function (event) {
        console.log('📨 Получено сообщение:', event.data);

        try {
            const data = JSON.parse(event.data);
            console.log('📊 Данные прогресса:', data);

            if (data.error) {
                progressLabel.textContent = '❌ ' + data.status;
                progressBar.className = 'progress-bar bg-danger';
                progressBar.textContent = '0%';
                progressPercent.textContent = '0%';
                resultMessage.innerHTML = `<div class="alert alert-danger">${data.status}</div>`;
                closeConnection();
                // ✅ Разблокируем кнопку при ошибке
                const submitBtn = document.getElementById('uploadBtn');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Загрузить';
                }
                return;
            }

            const percent = data.progress || 0;
            progressBar.className = 'progress-bar progress-bar-striped progress-bar-animated bg-success';
            progressBar.style.width = percent + '%';
            progressBar.textContent = percent + '%';
            progressPercent.textContent = percent + '%';
            progressLabel.textContent = data.status || 'Обработка...';

            if (percent >= 100) {
                progressLabel.textContent = '✅ Готово!';
                resultMessage.innerHTML = '<div class="alert alert-success">Микс обработан!</div>';
                closeConnection();
                // ✅ Разблокируем кнопку
                const submitBtn = document.getElementById('uploadBtn');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Загрузить';

                    document.getElementById('mixFile').value = '';
                    document.getElementById('title').value = '';
                    document.getElementById('artist').value = '';
                    document.getElementById('isPrivate').checked = false;
                }
            }
        } catch (error) {
            console.error('❌ Ошибка парсинга сообщения:', error);
        }
    };

    eventSource.onerror = function (error) {
        console.error('❌ EventSource ошибка:', error);
        closeConnection();

        // ✅ Переподключаемся с новой ссылкой
        reconnectTimeout = setTimeout(() => {
            console.log('🔄 Переподключаемся...');
            window.subscribeToProgress(mixId);
        }, 3000);
    };
};

document.addEventListener('submit', function (e) {
    if (e.target && e.target.id === 'upload-form') {
        e.preventDefault();

        const submittedForm = e.target;
        const submitBtn = submittedForm.querySelector('#uploadBtn');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = '⏳ Загрузка...';
        }

        const form = e.target;
        const fileInput = document.getElementById('mixFile');
        const progressWrapper = document.getElementById('progress-wrapper');
        const progressBar = document.getElementById('progress-bar');
        const progressLabel = document.getElementById('progress-label');
        const progressPercent = document.getElementById('progress-percent');
        const resultMessage = document.getElementById('result-message');

        const file = fileInput.files[0];
        if (!file) {
            resultMessage.innerHTML = '<div class="alert alert-warning">Выберите файл</div>';
            // ✅ Разблокируем кнопку
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Загрузить';
            }
            return;
        }

        const formData = new FormData(form);
        progressWrapper.style.display = 'block';
        progressBar.className = 'progress-bar progress-bar-striped progress-bar-animated bg-primary';
        progressBar.style.width = '0%';
        progressBar.textContent = '0%';
        progressPercent.textContent = '0%';
        progressLabel.textContent = 'Загрузка...';
        resultMessage.innerHTML = '';

        const xhr = new XMLHttpRequest();

        xhr.upload.addEventListener('progress', function (event) {
            if (event.lengthComputable) {
                const percent = Math.round((event.loaded / event.total) * 100);
                progressBar.style.width = percent + '%';
                progressBar.textContent = percent + '%';
                progressPercent.textContent = percent + '%';
                progressLabel.textContent = 'Загрузка...';
            }
        });

        xhr.onload = function () {
            console.log('📨 Ответ от сервера:', xhr.status, xhr.responseText);

            // ✅ Разблокируем кнопку в любом случае
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Загрузить';
            }

            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    console.log('📦 Ответ:', response);

                    if (response.success && response.mixId) {
                        progressBar.className = 'progress-bar progress-bar-striped progress-bar-animated bg-success';
                        progressBar.style.width = '0%';
                        progressBar.textContent = '0%';
                        progressPercent.textContent = '0%';
                        progressLabel.textContent = '⏳ Обработка...';

                        resultMessage.innerHTML = '<div class="alert alert-info">Файл загружен, идёт обработка...</div>';

                        if (typeof window.subscribeToProgress === 'function') {
                            window.subscribeToProgress(response.mixId);
                        } else {
                            console.error('❌ Функция subscribeToProgress не найдена');
                            if (submitBtn) {
                                submitBtn.disabled = false;
                                submitBtn.textContent = 'Загрузить';
                            }
                        }
                    }
                } catch (err) {
                    console.error('❌ Ошибка парсинга ответа:', err);
                    progressBar.className = 'progress-bar bg-danger';
                    resultMessage.innerHTML = '<div class="alert alert-danger">Ошибка при обработке ответа</div>';
                }
            } else {
                let message = '';
                try {
                    const response = JSON.parse(xhr.responseText);
                    message = response.message || response.error || 'Ошибка загрузки';
                } catch (err) {
                    message = 'Ошибка загрузки: ' + xhr.status;
                }
                progressBar.className = 'progress-bar bg-danger';
                resultMessage.innerHTML = `<div class="alert alert-danger">${message}</div>`;
            }
        };

        xhr.onerror = function () {
            console.error('❌ Ошибка сети');
            progressBar.className = 'progress-bar bg-danger';
            resultMessage.innerHTML = '<div class="alert alert-danger">Ошибка сети</div>';

            // ✅ Разблокируем кнопку при ошибке сети
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Загрузить';
            }
        };

        xhr.open('POST', form.action);
        xhr.send(formData);
    }
});
