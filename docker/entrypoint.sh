#!/bin/sh
set -e

cd /app

if [ -z "$APP_KEY" ]; then
    echo "APP_KEY is not set. Generate one with: php artisan key:generate --show" >&2
    exit 1
fi

# storage/framework is a tmpfs in docker-compose.yml, so recreate its tree.
mkdir -p storage/demo/sandboxes \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# The scheduler container shares the template and skips the build.
if [ "${SKIP_TEMPLATE_BUILD:-0}" != "1" ]; then
    php artisan demo:build-template --no-interaction
fi

exec "$@"
