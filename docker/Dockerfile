FROM php:8.4-fpm-alpine

RUN apk add --no-cache icu-dev libzip-dev postgresql-dev oniguruma-dev libpng-dev \
    && docker-php-ext-install intl opcache pdo_pgsql zip mbstring gd

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
