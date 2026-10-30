#!/bin/bash
set -e

# Render passes dynamic $PORT
PORT="${PORT:-80}"
sed -i "s/80/$PORT/g" /etc/apache2/ports.conf /etc/apache2/sites-available/*.conf

# Automatically run database migrations & realistic seeders on Neon / PostgreSQL
php artisan migrate --force || true
php artisan db:seed --force || true

# Optimize configuration and routes
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

exec "$@"
