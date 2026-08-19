# syntax=docker/dockerfile:1.7

# ──────────────────────────────────────────────────────────────────────────────
# Stage 1 — PHP with every extension the application needs.
# ──────────────────────────────────────────────────────────────────────────────
FROM php:8.3-fpm-alpine AS base

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions \
    pdo_pgsql \
    pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    opcache \
    && rm -f /usr/local/bin/install-php-extensions \
    && rm -rf /tmp/* /var/cache/apk/*

# ──────────────────────────────────────────────────────────────────────────────
# Stage 2 — resolve the Composer dependency tree.
# ──────────────────────────────────────────────────────────────────────────────
FROM base AS vendor

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /app

COPY composer.json composer.lock ./

RUN --mount=type=cache,target=/tmp/composer-cache \
    COMPOSER_CACHE_DIR=/tmp/composer-cache composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --no-interaction \
    --no-progress \
    --prefer-dist

# ──────────────────────────────────────────────────────────────────────────────
# Stage 3 — runtime.
# ──────────────────────────────────────────────────────────────────────────────
FROM base AS runtime

ARG APP_VERSION="dev"
ARG BUILD_DATE=""
ARG VCS_REF=""

LABEL org.opencontainers.image.title="Chistulya API" \
    org.opencontainers.image.source="https://github.com/anademq/chistulya-backend" \
    org.opencontainers.image.version="${APP_VERSION}" \
    org.opencontainers.image.created="${BUILD_DATE}" \
    org.opencontainers.image.revision="${VCS_REF}"

ENV APP_VERSION="${APP_VERSION}"

RUN apk add --no-cache \
    bash \
    su-exec \
    fcgi \
    postgresql-client \
    && rm -rf /var/cache/apk/*

COPY docker/php/production.ini /usr/local/etc/php/conf.d/99-production.ini
# zzz- so it sorts after the image own zz-docker.conf and wins the merge.
COPY docker/php/php-fpm.conf   /usr/local/etc/php-fpm.d/zzz-app.conf

WORKDIR /var/www/html

COPY --from=vendor --chown=www-data:www-data /app/vendor ./vendor
COPY --chown=www-data:www-data . .

RUN --mount=type=bind,from=composer:2,source=/usr/bin/composer,target=/usr/bin/composer \
    --mount=type=cache,target=/tmp/composer-cache \
    COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_CACHE_DIR=/tmp/composer-cache \
    composer dump-autoload --no-dev --optimize --classmap-authoritative --no-interaction

RUN mkdir -p storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker/php/entrypoint   /usr/local/bin/entrypoint
COPY docker/php/healthcheck  /usr/local/bin/healthcheck
RUN chmod +x /usr/local/bin/entrypoint /usr/local/bin/healthcheck

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint"]
CMD ["php-fpm", "--nodaemonize"]
