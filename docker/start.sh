#!/bin/sh

set -e

if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY is not set." >&2
    exit 1
fi

# Azure App Service exposes the public host name as WEBSITE_HOSTNAME.
if [ -z "${APP_URL:-}" ] && [ -n "${WEBSITE_HOSTNAME:-}" ]; then
    APP_URL="https://${WEBSITE_HOSTNAME}"
fi
export APP_URL="${APP_URL:-http://localhost:8080}"

php artisan package:discover --ansi
php artisan migrate --force
# Safe on every boot: accounts and master data are upserted, demo data
# (trees, photos, harvests, ...) is only created when no trees exist yet.
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link --force

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
