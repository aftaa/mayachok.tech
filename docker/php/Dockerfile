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
# STAGE 2: BASE
# ==========================================
FROM php:8.5-fpm AS base
ARG APP_DIR

RUN apt-get update
RUN apt-get install -y --no-install-recommends curl git mc unzip supervisor procps ffmpeg sox
RUN apt-get install -y libgd3 libmad0 libid3tag0 libboost-program-options1.83.0 libboost-filesystem1.83.0
RUN pecl install redis && docker-php-ext-enable redis

COPY --link --from=ghcr.io/symfony-cli/symfony-cli:latest /usr/local/bin/symfony /usr/local/bin/symfony
COPY --from=audiowaveform-builder /tmp/audiowaveform/build/audiowaveform /usr/local/bin/audiowaveform
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

COPY docker/php/supervisor/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/php/zz-docker.conf /usr/local/etc/php-fpm.d/zz-docker.conf

ARG PUID=1000
ARG PGID=1000
RUN groupadd -g ${PGID} appgroup && useradd -u ${PUID} -g appgroup -m appuser

RUN sed -i 's/#force_color_prompt=yes/force_color_prompt=yes/g' /etc/skel/.bashrc \
    && echo 'PS1="\[\e[36m\]\u\[\e[m\]@\[\e[32m\]docker-php\[\e[m\]:\[\e[34m\]\w\[\e[m\]\$ "' >> /etc/skel/.bashrc \
    && echo "alias ls='ls --color=auto'" >> /etc/skel/.bashrc \
    && echo "alias ll='ls -la'" >> /etc/skel/.bashrc \
    && cp /etc/skel/.bashrc /home/appuser/.bashrc \
    && chown appuser:appgroup /home/appuser/.bashrc \
    && cp /etc/skel/.bashrc /root/.bashrc

RUN mkdir -p /var/log/php-fpm && chown -R appuser:appgroup /var/log/php-fpm
WORKDIR ${APP_DIR}



# ==========================================
# STAGE 3: DEV
# ==========================================
FROM base AS dev
ARG APP_DIR
RUN apt-get install -y --no-install-recommends libicu-dev libzip-dev
RUN docker-php-ext-install -j$(nproc) intl pdo pdo_mysql zip
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]



# ==========================================
# STAGE 4: PROD BUILDER
# ==========================================
FROM base AS prod-builder
ARG APP_DIR

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

COPY . .
RUN composer run-script post-install-cmd --no-interaction || true \
    && php bin/console cache:clear --env=prod \
    && php bin/console cache:warmup --env=prod \
    && chown -R appuser:appgroup ${APP_DIR}



# ==========================================
# STAGE: PROD
# ==========================================
FROM base AS prod
ARG APP_DIR
RUN apt-get install -y --no-install-recommends libicu76 libzip5
RUN docker-php-ext-install -j$(nproc) intl pdo pdo_mysql zip
COPY --from=prod-builder --chown=appuser:appgroup ${APP_DIR} ${APP_DIR}
RUN rm -f /usr/local/bin/composer
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]
