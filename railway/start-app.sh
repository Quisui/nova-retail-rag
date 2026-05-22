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

echo "Waiting for database connection..."
DB_WAIT_MAX_ATTEMPTS="${DB_WAIT_MAX_ATTEMPTS:-30}"
DB_WAIT_SLEEP_SECONDS="${DB_WAIT_SLEEP_SECONDS:-2}"
attempt=1
while [ "$attempt" -le "$DB_WAIT_MAX_ATTEMPTS" ]; do
  if php -r '
    $host = getenv("DB_HOST") ?: "127.0.0.1";
    $port = getenv("DB_PORT") ?: "5432";
    $db = getenv("DB_DATABASE") ?: "forge";
    $user = getenv("DB_USERNAME") ?: "forge";
    $pass = getenv("DB_PASSWORD") ?: "";
    $dsn = "pgsql:host={$host};port={$port};dbname={$db}";
    try {
      new PDO($dsn, $user, $pass, [PDO::ATTR_TIMEOUT => 5]);
      exit(0);
    } catch (Throwable $e) {
      exit(1);
    }
  ' >/dev/null 2>&1; then
    echo "Database is reachable."
    break
  fi

  if [ "$attempt" -eq "$DB_WAIT_MAX_ATTEMPTS" ]; then
    echo "Database is not reachable after ${DB_WAIT_MAX_ATTEMPTS} attempts."
    exit 1
  fi

  echo "DB not ready yet (${attempt}/${DB_WAIT_MAX_ATTEMPTS}), retrying in ${DB_WAIT_SLEEP_SECONDS}s..."
  sleep "$DB_WAIT_SLEEP_SECONDS"
  attempt=$((attempt + 1))
done

php artisan storage:link || true
php artisan migrate --force

if [ "${RUN_DB_SEED:-false}" = "true" ]; then
  echo "RUN_DB_SEED=true -> executing database seeder."
  php artisan db:seed --force
fi

php artisan optimize:clear
php artisan config:cache
php artisan route:cache || true
php artisan view:cache || true

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
