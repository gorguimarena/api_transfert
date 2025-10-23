#!/bin/bash

# Run migrations
php artisan migrate --force

# Cache configurations for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start PHP-FPM in background
php-fpm &

# Start Nginx in foreground
nginx -g "daemon off;"