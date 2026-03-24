FROM php:8.3-cli

# Fix Composer memory issues
ENV COMPOSER_MEMORY_LIMIT=-1

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl unzip zip \
    libzip-dev libonig-dev libxml2-dev \
    libicu-dev libpng-dev libjpeg-dev libfreetype6-dev \
    libcurl4-openssl-dev pkg-config libssl-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mbstring \
        zip \
        exif \
        pcntl \
        bcmath \
        intl \
        gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy composer files first
COPY composer.json composer.lock ./

# 👇 KEY FIX: allow superuser + verbose output
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    -vvv

# Copy full project
COPY . .

# Laravel setup
RUN php artisan package:discover || true

RUN chmod -R 775 storage bootstrap/cache

EXPOSE 8000

CMD php -S 0.0.0.0:8000 -t public
