FROM php:8.2-cli-alpine

RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    libxml2-dev \
    oniguruma-dev \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql zip mbstring dom xml fileinfo \
    && rm -rf /var/cache/apk/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .
RUN composer install --no-dev --optimize-autoloader --no-interaction

EXPOSE 8000

CMD php artisan migrate --force && \
    php artisan db:seed --force && \
    php artisan serve --host=0.0.0.0 --port=8000
