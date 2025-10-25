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

# Start Supervisor to manage PHP-FPM and Nginx
echo "Starting Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf