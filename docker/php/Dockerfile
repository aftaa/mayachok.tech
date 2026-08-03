ARG APP_DIR=/var/www/tech

# ==========================================
# STAGE 1: AUDIOWAVEFORM BUILDER
# ==========================================
FROM php:8.5-fpm AS audiowaveform-builder

RUN apt-get update && apt-get install -y --no-install-recommends \
    git cmake make g++ gcc libmad0-dev libid3tag0-dev \
    libsndfile1-dev libgd-dev libboost-filesystem-dev \
    libboost-program-options-dev libboost-regex-dev \
    ca-certificates wget \
    && rm -rf /var/lib/apt/lists/*

RUN git clone https://github.com/bbc/audiowaveform /tmp/audiowaveform \
    && cd /tmp/audiowaveform \
    && mkdir build && cd build \
    && cmake -DCMAKE_BUILD_TYPE=Release -DENABLE_TESTS=0 .. \
    && make -j$(nproc)

# ==========================================
# STAGE 2: BASE (Общая база для DEV и PROD)
# ==========================================
FROM php:8.5-fpm AS base
ARG APP_DIR

RUN apt-get update && apt-get install -y --no-install-recommends \
    curl git mc unzip supervisor procps \
    ffmpeg sox libmad0-dev libid3tag0-dev \
    libsndfile1-dev libgd-dev libboost-filesystem-dev \
    libboost-program-options-dev libboost-regex-dev \
    libcurl4-openssl-dev libxml2-dev libonig-dev libicu-dev libssl-dev libzip-dev \
    && docker-php-ext-install -j$(nproc) intl pdo pdo_mysql zip opcache \
    && pecl install redis && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=audiowaveform-builder /tmp/audiowaveform/build/audiowaveform /usr/local/bin/audiowaveform
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

ARG PUID=1000
ARG PGID=1000
RUN groupadd -g ${PGID} appgroup && useradd -u ${PUID} -g appgroup -m appuser

WORKDIR ${APP_DIR}

RUN mkdir -p /var/log/supervisor /var/log/php-fpm /var/run \
    && touch /var/log/supervisor/supervisord.log \
             /var/log/supervisor/php-fpm.out.log \
             /var/log/supervisor/php-fpm.err.log \
             /var/log/supervisor/messenger.out.log \
             /var/log/supervisor/messenger.err.log \
    && touch /var/log/php-fpm/error.log /var/log/php-fpm/access.log \
    && chown -R appuser:appgroup /var/log/supervisor /var/log/php-fpm /var/run ${APP_DIR}

COPY docker/php/supervisor/supervisord.conf /etc/supervisor/supervisord.conf

# ==========================================
# STAGE 3: DEV
# ==========================================
FROM base AS dev
ARG APP_DIR

RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | bash -E \
    && apt-get install -y symfony-cli \
    && apt-get clean && rm -rf /var/lib/apt/lists/* \
    && ln -sf /usr/bin/symfony /usr/local/bin/symfony

RUN sed -i 's/#force_color_prompt=yes/force_color_prompt=yes/g' /etc/skel/.bashrc \
    && echo 'PS1="\[\e[36m\]\u\[\e[m\]@\[\e[32m\]docker-php\[\e[m\]:\[\e[34m\]\w\[\e[m\]\$ "' >> /etc/skel/.bashrc \
    && echo "alias ls='ls --color=auto'" >> /etc/skel/.bashrc \
    && echo "alias ll='ls -la'" >> /etc/skel/.bashrc \
    && cp /etc/skel/.bashrc /home/appuser/.bashrc \
    && chown appuser:appgroup /home/appuser/.bashrc \
    && cp /etc/skel/.bashrc /root/.bashrc

COPY docker/php/zz-docker.conf /usr/local/etc/php-fpm.d/zz-docker.conf

CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]

# ==========================================
# STAGE 4: BUILDER (Сборка зависимостей и ассетов)
# ==========================================
FROM base AS builder
ARG APP_DIR

# Переключаемся на пользователя для безопасной сборки
USER appuser

COPY --chown=appuser:appgroup composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --optimize-autoloader --no-scripts

COPY --chown=appuser:appgroup . .

# Собираем ассеты и прогреваем кэш Symfony
RUN php bin/console sass:build --env=prod \
    && php bin/console asset-map:compile --env=prod \
    && php bin/console cache:clear --env=prod \
    && php bin/console cache:warmup --env=prod

# ==========================================
# STAGE 5: PRODUCTION
# ==========================================
FROM base AS production
ARG APP_DIR

COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php/zz-docker.conf /usr/local/etc/php-fpm.d/zz-docker.conf

# Копируем полностью собранный проект с прогретым кэшем из builder
COPY --from=builder --chown=appuser:appgroup ${APP_DIR} ${APP_DIR}

USER appuser

CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]
