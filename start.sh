#!/bin/bash

# Check if migrations need to be run
if php artisan migrate:status | grep -q "Pending"; then
    echo "Running pending migrations..."
    php artisan migrate --force
else
    echo "No pending migrations found."
fi

# Check if database is empty and run seeders if needed
if php artisan tinker --execute="echo App\Models\User::count() . PHP_EOL;" | grep -q "^0$"; then
    echo "Database appears empty, running seeders..."
    php artisan db:seed --force
else
    echo "Database already contains data, skipping seeders."
fi

# Generate Swagger documentation
echo "Generating Swagger documentation..."
php artisan l5-swagger:generate

# Cache configurations for production
echo "Caching configurations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start PHP-FPM in foreground
echo "Starting PHP-FPM..."
php-fpm