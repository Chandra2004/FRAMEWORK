# Railway PHP Deployment - Simplified
# Using PHP built-in server

FROM php:8.3-cli-alpine

# Install system dependencies
RUN apk add --no-cache \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    bash

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Create storage directories with proper permissions
RUN mkdir -p storage/session storage/logs storage/framework/views storage/framework/cache storage/app/public \
    && chmod -R 777 storage

# Create private-uploads directory
RUN mkdir -p private-uploads && chmod -R 777 private-uploads

# Copy custom php.ini
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini

# Create startup script that properly expands $PORT
RUN echo '#!/bin/bash' > /start.sh && \
    echo 'echo "Starting PHP server on port ${PORT:-8080}..."' >> /start.sh && \
    echo 'php -S 0.0.0.0:${PORT:-8080} -t .' >> /start.sh && \
    chmod +x /start.sh

# Expose port
EXPOSE 8080

# Use shell form so environment variables get expanded
CMD ["/bin/bash", "/start.sh"]
