#!/bin/bash
set -e

# Render passes dynamic $PORT
PORT="${PORT:-80}"
sed -i "s/80/$PORT/g" /etc/apache2/ports.conf /etc/apache2/sites-available/*.conf

# Run schema migrations before starting Apache. Seeders are opt-in because some
# production seeders are large and must not delay the web server startup.
php artisan migrate --force

if [ "${RUN_SEEDERS:-false}" = "true" ]; then
    php artisan db:seed --force
fi

# Optimize configuration and routes
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

exec "$@"
