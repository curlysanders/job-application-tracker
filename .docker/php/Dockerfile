# FrankenPHP base image met PHP 8.5
FROM dunglas/frankenphp:php8.5

# Install system dependencies & PHP extensions required for Symfony & MariaDB
# 1. Alles in één RUN-stap om lagen te beperken
# 2. 'docker-php-ext-configure' weggelaten (is overbodig)
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    && docker-php-ext-install -j$(nproc) pdo_mysql intl zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

COPY .docker/php/config/xdebug.ini /usr/local/etc/php/conf.d/99-xdebug.ini

# Install Composer from official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN mkdir -p /home/appuser/.cache/composer \
    && chown -R 1000:1000 /home/appuser

ENV HOME=/home/appuser
USER 1000:1000

WORKDIR /app

EXPOSE 80 443 443/udp
