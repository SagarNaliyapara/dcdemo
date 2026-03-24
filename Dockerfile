FROM php:8.3-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl unzip zip \
    libzip-dev libonig-dev libxml2-dev \
    libicu-dev libpng-dev libjpeg-dev libfreetype6-dev \
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

# Copy only composer files first (IMPORTANT for caching & fewer errors)
COPY composer.json composer.lock ./

# Install dependencies (no scripts to avoid Laravel boot issues)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Copy rest of project
COPY . .

# Now run Laravel commands
RUN php artisan package:discover || true

# Permissions
RUN chmod -R 775 storage bootstrap/cache

EXPOSE 8000

CMD php -S 0.0.0.0:8000 -t public
