# Dockerfile for Laravel 12 (PHP 8.3 FPM on Alpine)
FROM php:8.4-fpm-alpine AS base

# Arguments defined in docker-compose.yml
ARG user
ARG uid

# Install system dependencies & PHP build deps
RUN apk update \
    && apk add --no-cache \
        bash \
        git \
        curl \
        libpng-dev \
        oniguruma-dev \
        libxml2-dev \
        zip \
        unzip \
        libzip-dev \
        mariadb-client \
        imagemagick-dev \
        imagemagick \
        shadow \
        $PHPIZE_DEPS \
    # Install PHP extensions
    && pecl install imagick \
    && docker-php-ext-enable imagick \
    && docker-php-ext-install \
        zip \
        mysqli \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
    && docker-php-ext-enable mysqli \
    # Cleanup build deps & caches
    && apk del $PHPIZE_DEPS \
    && rm -rf /var/cache/apk/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create system user to run Composer and Artisan commands
RUN useradd -u ${uid} -d /home/${user} -s /bin/bash ${user} \
    && mkdir -p /home/${user}/.composer \
    && chown -R ${user}:${user} /home/${user}

# Set working directory
WORKDIR /var/www

# make sure we’re root when we change ownership
USER root
RUN chown -R ${user}:${user} /var/www

# Switch to non-root user
USER ${user}


