# FrankenPHP base image met PHP 8.5
FROM dunglas/frankenphp:1.12.7-builder-php8.5

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

# Install Composer from official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

ENV COMPOSER_ALLOW_SUPERUSER=1

EXPOSE 80 443 443/udp