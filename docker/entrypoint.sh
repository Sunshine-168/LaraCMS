#!/bin/sh
set -e
cd /var/www/html

if [ ! -f .env ]; then
    cp .env.example .env
fi

composer install --no-interaction --prefer-dist --no-progress

if ! grep -Eq '^APP_KEY=base64:' .env; then
    php artisan key:generate --no-interaction
fi

mkdir -p database storage/app storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
touch database/database.sqlite
chmod -R ug+rwx storage bootstrap/cache database || true

php artisan migrate --force

if [ ! -f storage/app/install.lock ]; then
    php artisan db:seed --force
    printf 'installed_at=%s\nseed_demo=1\n' "$(date -Iseconds)" > storage/app/install.lock
fi

php artisan storage:link --force >/dev/null 2>&1 || true

exec "$@"
