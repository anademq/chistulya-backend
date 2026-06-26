FROM php:8.3-fpm-alpine AS builder

RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    postgresql-dev \
    oniguruma-dev \
    imagemagick-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
    pdo \
    pdo_mysql \
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
    && pecl install redis imagick \
    && docker-php-ext-enable redis imagick \
    && apk del .build-deps \
    && rm -rf /tmp/pear /var/cache/apk/*


FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    bash \
    postgresql-client \
    libpng \
    libjpeg-turbo \
    freetype \
    libzip \
    icu-libs \
    libpq \
    oniguruma \
    imagemagick \
    && rm -rf /var/cache/apk/*

COPY --from=builder /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=builder /usr/local/etc/php/conf.d/     /usr/local/etc/php/conf.d/

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

COPY docker/php/production.ini /usr/local/etc/php/conf.d/99-production.ini

RUN sed -i \
    -e 's/^pm = .*/pm = dynamic/' \
    -e 's/^pm\.max_children = .*/pm.max_children = 20/' \
    -e 's/^pm\.start_servers = .*/pm.start_servers = 4/' \
    -e 's/^pm\.min_spare_servers = .*/pm.min_spare_servers = 2/' \
    -e 's/^pm\.max_spare_servers = .*/pm.max_spare_servers = 10/' \
    /usr/local/etc/php-fpm.d/www.conf

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install \
    --no-scripts \
    --no-autoloader \
    --no-interaction \
    --prefer-dist

COPY . .
RUN composer dump-autoload --optimize \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 storage bootstrap/cache

COPY docker/php/entrypoint /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint"]
CMD ["php-fpm"]
