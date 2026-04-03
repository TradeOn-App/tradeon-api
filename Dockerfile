FROM php:8.4-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    libzip-dev \
    libxml2-dev \
    libonig-dev \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql zip mbstring dom xml fileinfo \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .
RUN composer install --no-dev --optimize-autoloader --no-interaction 2>&1

EXPOSE 8000

CMD php artisan migrate --force && \
    php artisan db:seed --force && \
    php artisan serve --host=0.0.0.0 --port=8000
