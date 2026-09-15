FROM php:8.3-cli-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libpng-dev libjpeg62-turbo-dev libfreetype6-dev libzip-dev libsqlite3-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_sqlite gd zip bcmath \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY docker/entrypoint.sh /usr/local/bin/cms-entrypoint
RUN chmod +x /usr/local/bin/cms-entrypoint

EXPOSE 8000

ENTRYPOINT ["cms-entrypoint"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
