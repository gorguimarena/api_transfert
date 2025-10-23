# Multi-stage build for production on Render

# Stage 1: Build dependencies
FROM composer:2 as vendor

WORKDIR /app

# Copy composer files
COPY composer.json composer.lock ./

# Install dependencies (without dev dependencies for production)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Stage 2: Build assets (if using frontend assets)
FROM node:18 as assets

WORKDIR /app

# Copy package files
COPY package.json package-lock.json ./

# Install dependencies and build assets
RUN npm ci && npm run build

# Stage 3: Production image
FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libpq-dev \
    libzip-dev \
    zip \
    nginx \
    supervisor \
    && docker-php-ext-install pdo pdo_pgsql zip opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Configure PHP for production
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.max_accelerated_files=7963" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.revalidate_freq=0" >> /usr/local/etc/php/conf.d/opcache.ini

# Copy application code
WORKDIR /var/www

COPY . .

# Copy dependencies from vendor stage
COPY --from=vendor /app/vendor ./vendor

# Copy built assets from assets stage (if applicable)
COPY --from=assets /app/public/build ./public/build

# Set permissions
RUN chown -R www-data:www-data /var/www && \
    chmod -R 755 /var/www/storage /var/www/bootstrap/cache

# Copy nginx configuration
COPY docker/deployment/nginx.conf /etc/nginx/sites-available/default

# Copy supervisor configuration
COPY docker/deployment/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Expose port 80 for nginx
EXPOSE 80

# Start supervisor to manage nginx and php-fpm
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]