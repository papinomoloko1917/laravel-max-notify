# syntax=docker/dockerfile:1

FROM php:8.5-fpm-bookworm AS php-base

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        libicu-dev \
        libpq-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-install -j1 bcmath intl pcntl pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/*

COPY docker/8.5/certificates/*.crt /usr/local/share/ca-certificates/
RUN update-ca-certificates

COPY docker/production/php.ini /usr/local/etc/php/conf.d/99-max-notify.ini
COPY docker/production/php-fpm.conf /usr/local/etc/php-fpm.d/zz-max-notify.conf

WORKDIR /var/www/html

FROM php-base AS vendor

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

FROM node:24-bookworm-slim AS frontend

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts
COPY --from=vendor /var/www/html/vendor ./vendor
COPY resources ./resources
COPY vite.config.js ./
RUN npm run build

FROM php-base AS app

COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /var/www/html/vendor ./vendor
COPY --from=frontend --chown=www-data:www-data /app/public/build ./public/build

RUN mkdir -p \
        storage/app/private \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/testing \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && php artisan package:discover --ansi

USER www-data

EXPOSE 9000
CMD ["php-fpm", "-F"]

FROM caddy:2.10-alpine AS web

COPY docker/production/Caddyfile /etc/caddy/Caddyfile
COPY --from=app /var/www/html/public /var/www/html/public

