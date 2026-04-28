#!/usr/bin/env sh
set -eu

cd /var/www/html

if [ ! -f .env ] && [ -f .env.example ]; then
  cp .env.example .env
fi

if [ -f artisan ] && [ -f .env ]; then
  if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force --no-interaction || true
  fi
fi

exec "$@"
