#!/usr/bin/env sh
set -eu

cd /var/www/novaretail-rag

if [ ! -f vendor/autoload.php ]; then
  composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
fi

if [ -z "${APP_KEY:-}" ]; then
  export APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
  echo "APP_KEY not provided. Generated ephemeral APP_KEY for this worker."
fi

php artisan optimize:clear

exec php artisan queue:work database --sleep=2 --tries=3 --max-time=3600 --timeout=120 --verbose
