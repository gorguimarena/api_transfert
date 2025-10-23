# Stage 1: Build dependencies
FROM composer:2 as vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Stage 2: Production image
FROM php:8.2-fpm

# System dependencies
RUN apt-get update && apt-get install -y \
    git unzip curl libpq-dev libzip-dev zip nginx supervisor \
    && docker-php-ext-install pdo pdo_pgsql zip opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# PHP production config
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.max_accelerated_files=7963" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.revalidate_freq=0" >> /usr/local/etc/php/conf.d/opcache.ini

# Application code
WORKDIR /var/www
COPY . .

# Copy dependencies from vendor stage
COPY --from=vendor /app/vendor ./vendor

# Set permissions
RUN chown -R www-data:www-data /var/www && \
    chmod -R 755 /var/www/storage /var/www/bootstrap/cache

# Nginx + Supervisor config
COPY docker/deployment/nginx.conf /etc/nginx/sites-available/default
COPY docker/deployment/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
