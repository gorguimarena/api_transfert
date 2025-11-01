#!/usr/bin/env bash
# Script alternatif pour les plateformes sans Supervisor (Render.com, etc.)
set -e

echo "🚀 Starting Ges-Comptes API with Queue Worker"

# Install dependencies if vendor directory doesn't exist
if [ ! -d "vendor" ]; then
    echo "Installing Composer dependencies..."
    composer install --no-dev --optimize-autoloader
fi

# Generate application key if not set
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
    echo "Generating application key..."
    php artisan key:generate
fi

# Install Passport keys if they don't exist
if [ ! -f "app/secrets/oauth/oauth-private.key" ]; then
    echo "Installing Passport keys..."
    php artisan passport:install --force
fi

# Set correct permissions for Passport keys
echo "Setting correct permissions for Passport keys..."
chmod 600 app/secrets/oauth/oauth-private.key
chmod 600 app/secrets/oauth/oauth-public.key

# Run migrations
echo "Running database migrations..."
php artisan migrate --force

# Run seeders
echo "Running database seeders..."
php artisan db:seed --force

# Run scheduled jobs
echo "Running scheduled jobs..."
php artisan jobs:run-scheduled

# Démarrer le worker de queue en arrière-plan
echo "📋 Starting queue worker..."
php artisan queue:work --verbose --tries=3 --timeout=90 --sleep=3 --max-jobs=1000 > storage/logs/worker.log 2>&1 &

# Attendre un moment pour s'assurer que le worker démarre
sleep 2

# --- Démarrer le serveur principal ---
echo "🌐 Starting main process..."
exec php artisan serve --host=0.0.0.0 --port=${PORT:-9000}