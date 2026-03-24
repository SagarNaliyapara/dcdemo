FROM php:8.4-cli

ENV COMPOSER_MEMORY_LIMIT=-1

RUN apt-get update && apt-get install -y \
    git curl unzip zip \
    libzip-dev libicu-dev libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        zip \
        intl \
        bcmath \
        gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# ✅ Copy FULL project first (IMPORTANT FIX)
COPY . .

# ✅ Now run composer
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist

# Laravel optimization (optional but safe now)
RUN php artisan config:cache || true
RUN php artisan route:cache || true

# Permissions
RUN chmod -R 775 storage bootstrap/cache

EXPOSE 8000

CMD php -S 0.0.0.0:8000 -t public
