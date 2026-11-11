FROM php:8.2-apache

# Install system dependencies & PHP extensions required by Laravel, DomPDF, PhpSpreadsheet, PhpWord, PostgreSQL & MySQL
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpq-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql pdo_pgsql pgsql mbstring exif pcntl bcmath gd zip opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Point Apache DocumentRoot to Laravel's public directory
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && a2enmod rewrite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Optional GitHub token (Docker build-arg) to avoid GitHub codeload rate limits (HTTP 429)
ARG GITHUB_TOKEN=""

# Copy Composer manifests first for layer caching
COPY composer.json composer.lock ./

# Install production dependencies with retries and --prefer-source fallback on GitHub HTTP 429
RUN set -eux; \
    export COMPOSER_PROCESS_TIMEOUT=600; \
    if [ -n "${GITHUB_TOKEN}" ]; then \
        composer config --global github-oauth.github.com "${GITHUB_TOKEN}"; \
    fi; \
    install_ok=0; \
    for attempt in 1 2 3; do \
        if composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist; then \
            install_ok=1; \
            break; \
        fi; \
        echo "Composer dist install attempt ${attempt} failed (possible GitHub HTTP 429). Retrying..."; \
        sleep $((attempt * 10)); \
    done; \
    if [ "${install_ok}" -ne 1 ]; then \
        echo "Falling back to --prefer-source (git clone) to bypass codeload.github.com 429 rate limit..."; \
        rm -rf /var/www/html/vendor; \
        composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-source; \
        find /var/www/html/vendor -name ".git" -type d -prune -exec rm -rf {} +; \
    fi; \
    rm -f /root/.composer/auth.json /root/.config/composer/auth.json; \
    composer clear-cache; \
    rm -rf /root/.composer/cache /root/.cache/composer

# Copy all backend files (excluding vendor/.env via .dockerignore)
COPY . .

# Generate optimized autoloader and run Laravel package discovery
RUN composer dump-autoload --no-dev --optimize --no-interaction

# Directory permissions for Laravel storage & cache
RUN mkdir -p /var/www/html/storage/framework/cache \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/logs \
    /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Copy entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]

