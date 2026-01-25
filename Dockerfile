# Railway PHP Deployment Dockerfile
# Using official PHP-FPM + Nginx for production

FROM php:8.3-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    git

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

# Configure Nginx
RUN rm -f /etc/nginx/http.d/default.conf
COPY docker/nginx.conf /etc/nginx/http.d/default.conf

# Configure PHP-FPM
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini

# Configure Supervisor
COPY docker/supervisord.conf /etc/supervisord.conf

# Expose port (Railway sets PORT env var)
EXPOSE 8080

# Start supervisor (manages nginx + php-fpm)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
