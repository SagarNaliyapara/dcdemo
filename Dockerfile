FROM php:7.2.34-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    libicu-dev \
    libxml2-dev \
    && docker-php-ext-configure gd \
        --with-freetype-dir=/usr/include/ \
        --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install \
        gd \
        mysqli \
        pdo \
        pdo_mysql \
        zip \
        intl \
        mbstring \
        xml \
    && apt-get clean

# Enable Apache rewrite
RUN a2enmod rewrite

WORKDIR /var/www/html

# Copy project
COPY . /var/www/html

# Permissions for CakePHP
RUN mkdir -p /var/www/html/app/tmp \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 777 /var/www/html/app/tmp

# Apache config for CakePHP
RUN echo '<VirtualHost *:${PORT}>
    DocumentRoot /var/www/html/app/webroot

    <Directory /var/www/html/app/webroot>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Make Apache listen on Render port
RUN sed -i 's/80/${PORT}/g' /etc/apache2/ports.conf

EXPOSE 10000

CMD ["sh", "-c", "apache2-foreground"]
