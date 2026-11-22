#!/usr/bin/env bash
# Forest OS container entrypoint: waits for the database, bootstraps once, starts Apache.
set -e

cd /var/www/html

echo "[forest] waiting for the database..."
for i in $(seq 1 30); do
  if php -r '
      $h = getenv("DB_HOST") ?: "db";
      $n = getenv("DB_NAME") ?: "forestos";
      $u = getenv("DB_USER") ?: "forest";
      $p = getenv("DB_PASSWORD") ?: "";
      try { new PDO("mysql:host={$h};dbname={$n};charset=utf8mb4", $u, $p); echo "ok"; }
      catch (Throwable $e) { exit(1); }
    ' >/dev/null 2>&1; then
    echo "[forest] database is up"
    break
  fi
  sleep 2
done

if [ "${FOREST_SKIP_BOOTSTRAP:-0}" != "1" ]; then
  echo "[forest] running bootstrap..."
  php bin/forest-bootstrap.php || echo "[forest] bootstrap failed, check DB settings in .env"
fi

exec "$@"
