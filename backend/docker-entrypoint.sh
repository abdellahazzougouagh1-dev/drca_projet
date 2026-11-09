#!/bin/bash
set -e

# Render passes dynamic $PORT
PORT="${PORT:-80}"
sed -i "s/80/$PORT/g" /etc/apache2/ports.conf /etc/apache2/sites-available/*.conf

# Run Artisan schema commands (migrate / seed) against Neon's direct endpoint
# when a PgBouncer "-pooler" endpoint is configured, preventing SQLSTATE[25P02]
# aborted-transaction errors during PostgreSQL DDL migrations.
run_direct_artisan() {
    (
        local direct_url="${DIRECT_DB_URL:-${DATABASE_URL_UNPOOLED:-${DIRECT_URL:-}}}"

        if [ -n "$direct_url" ]; then
            export DB_URL="$direct_url"
            export DATABASE_URL="$direct_url"
        else
            if [[ "${DB_URL:-}" == *"-pooler."*".neon.tech"* ]]; then
                DB_URL="$(printf '%s' "$DB_URL" | sed -E 's/-pooler\.([^/:]*\.neon\.tech)/.\1/')"
                export DB_URL
            fi
            if [[ "${DATABASE_URL:-}" == *"-pooler."*".neon.tech"* ]]; then
                DATABASE_URL="$(printf '%s' "$DATABASE_URL" | sed -E 's/-pooler\.([^/:]*\.neon\.tech)/.\1/')"
                export DATABASE_URL
            fi
            if [[ "${DB_HOST:-}" == *"-pooler."*".neon.tech"* ]]; then
                DB_HOST="$(printf '%s' "$DB_HOST" | sed -E 's/-pooler\.([^/:]*\.neon\.tech)/.\1/')"
                export DB_HOST
            fi
        fi

        php artisan "$@"
    )
}

# Run schema migrations before starting Apache. Seeders are opt-in because some
# production seeders are large and must not delay the web server startup.
run_direct_artisan migrate --force

if [ "${RUN_SEEDERS:-false}" = "true" ]; then
    run_direct_artisan db:seed --force
fi

# Optimize configuration and routes
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

exec "$@"
