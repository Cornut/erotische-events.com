#!/usr/bin/env bash
#
# Post-deploy steps for staging. Run from the application root AFTER pulling
# code, `composer install --no-dev`, and building assets (npm run build).
#
# Order matters: seed BEFORE caching so env() resolves inside the seeders.
#
set -euo pipefail

php artisan down || true
trap 'php artisan up || true' EXIT

echo "→ migrate"
php artisan migrate --force

echo "→ seed (admin, categories, organizers, settings — idempotent)"
php artisan db:seed --force

echo "→ storage symlink"
php artisan storage:link || true

echo "→ rebuild caches"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan filament:cache-components
php artisan icons:cache

echo "→ reindex Meilisearch"
php artisan scout:import "App\\Models\\Event"

echo "✓ post-deploy done"
# Note: restart the queue worker so it picks up new code:
#   php artisan queue:restart
