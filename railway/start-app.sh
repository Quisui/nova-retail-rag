#!/usr/bin/env sh
set -eu

cd /var/www/novaretail-rag

if [ ! -f vendor/autoload.php ]; then
  composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
fi

if [ -z "${APP_KEY:-}" ]; then
  export APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
  echo "APP_KEY not provided. Generated ephemeral APP_KEY for this deploy."
fi

php artisan storage:link || true
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache || true
php artisan view:cache || true

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
