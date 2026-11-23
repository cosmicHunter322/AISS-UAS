FROM php:8.2-fpm-alpine
RUN apk add --no-cache libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    && echo "expose_php = Off" > /usr/local/etc/php/conf.d/security.ini
USER www-data