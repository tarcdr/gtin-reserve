#!/usr/bin/env sh
set -e

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www:www storage bootstrap/cache

if [ "${APP_ENV:-}" = "production" ]; then
  php artisan config:cache --no-interaction || true
  php artisan route:cache --no-interaction || true
  php artisan view:cache --no-interaction || true
fi

exec "$@"
