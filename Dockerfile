FROM php:7.2.34-apache

# Use archived Buster repos (fixed 404 issue)
RUN sed -i.bak 's|http://deb.debian.org/debian|http://archive.debian.org/debian|g' /etc/apt/sources.list \
    && sed -i.bak 's|http://security.debian.org/debian-security|http://archive.debian.org/debian-security|g' /etc/apt/sources.list \
    && echo 'Acquire::Check-Valid-Until "false";' > /etc/apt/apt.conf.d/99no-check-valid-until

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

# Apache config
RUN printf '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/app/webroot\n\
    <Directory /var/www/html/app/webroot>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
</VirtualHost>\n' > /etc/apache2/sites-available/000-default.conf

# Expose Render port
ENV PORT 10000
RUN sed -i 's/80/${PORT}/g' /etc/apache2/ports.conf

EXPOSE 10000

CMD ["sh", "-c", "apache2-foreground"]
