#!/usr/bin/env sh
set -eu

cd /var/www/novaretail-rag

if [ ! -f vendor/autoload.php ]; then
  composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
fi

# Railway: force secure APP_URL/ASSET_URL to avoid mixed-content blocked CSS/JS.
if [ -n "${RAILWAY_PUBLIC_DOMAIN:-}" ]; then
  export APP_URL="${APP_URL:-https://${RAILWAY_PUBLIC_DOMAIN}}"
fi

case "${APP_URL:-}" in
  http://*.railway.app*)
    export APP_URL="$(printf '%s' "$APP_URL" | sed 's#^http://#https://#')"
    ;;
esac

if [ -n "${APP_URL:-}" ] && [ -z "${ASSET_URL:-}" ]; then
  export ASSET_URL="$APP_URL"
fi

if [ -z "${APP_KEY:-}" ]; then
  export APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
  echo "APP_KEY not provided. Generated ephemeral APP_KEY for this deploy."
fi

php artisan storage:link || true
php artisan migrate --force

if [ "${RUN_DB_SEED:-false}" = "true" ]; then
  echo "RUN_DB_SEED=true: running php artisan db:seed before starting server..."
  php artisan db:seed
  echo "Seeding complete."
fi

php artisan optimize:clear
php artisan config:cache
php artisan route:cache || true
php artisan view:cache || true

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
