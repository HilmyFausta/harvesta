#!/bin/sh

set -e

if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY is not set." >&2
    exit 1
fi

export APP_URL="${APP_URL:-${RENDER_EXTERNAL_URL:-http://localhost:8080}}"

php artisan package:discover --ansi
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link --force

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
