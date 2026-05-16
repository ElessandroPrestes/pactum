# syntax=docker/dockerfile:1

# ---- base: runtime PHP-FPM 8.3 com extensoes do projeto ----
FROM php:8.5-fpm-alpine AS base

RUN apk add --no-cache icu-libs \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev linux-headers openssl-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql bcmath intl opcache pcntl \
    && yes '' | pecl install -f redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps \
    && rm -rf /tmp/pear

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ---- vendor: dependencias de producao isoladas para cache de layer ----
FROM base AS vendor

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# ---- dev: imagem de desenvolvimento com xdebug e dev deps ----
FROM base AS dev

RUN apk add --no-cache --virtual .xdebug-deps $PHPIZE_DEPS linux-headers \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del .xdebug-deps \
    && rm -rf /tmp/pear

COPY . .
RUN composer install --prefer-dist --no-interaction

# ---- prod: imagem enxuta sem dev deps, com opcache preload ----
FROM base AS prod

COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/zz-opcache.ini
COPY --from=vendor --chown=www-data:www-data /var/www/html/vendor ./vendor
COPY --chown=www-data:www-data . .

USER www-data
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev --no-interaction
