ARG FRANKENPHP_IMAGE=dunglas/frankenphp@sha256:8a7a935deb33959200628497b8201423c8b777d513a62ac1b69a91c624afdb3d
ARG COMPOSER_IMAGE=composer@sha256:af98f42dfff7c68ba8d53c2164fd9fde1087b7d449514baa38c418b1f6bc4bac

# FrankenPHP 1.13.2 with PHP 8.5.12.
FROM ${COMPOSER_IMAGE} AS composer-bin

FROM ${FRANKENPHP_IMAGE}

# Install system dependencies & PHP extensions required for Symfony & MariaDB
# 1. Alles in één RUN-stap om lagen te beperken
# 2. 'docker-php-ext-configure' weggelaten (is overbodig)
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    && docker-php-ext-install -j$(nproc) bcmath pdo_mysql intl zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

COPY .docker/php/config/xdebug.ini /usr/local/etc/php/conf.d/99-xdebug.ini

# Install Composer from the pinned official image.
COPY --from=composer-bin /usr/bin/composer /usr/bin/composer

RUN mkdir -p /home/appuser/.cache/composer /opt/phpstorm-coverage \
    && chown -R 1000:1000 /home/appuser /opt/phpstorm-coverage

ENV HOME=/home/appuser
USER 1000:1000

WORKDIR /app

EXPOSE 80 443 443/udp
