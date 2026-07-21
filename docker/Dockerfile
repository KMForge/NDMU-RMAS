FROM php:8.3-fpm-alpine

RUN apk add --no-cache icu-dev libzip-dev postgresql-dev \
    && docker-php-ext-install intl opcache pdo_pgsql zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
