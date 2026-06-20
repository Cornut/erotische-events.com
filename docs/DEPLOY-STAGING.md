# Staging Deployment

Laravel 13 · Inertia/Vue · Filament 5 · MySQL · Meilisearch · DB queue.

## 0. Prerequisites on the staging host
- PHP 8.4 (ext: pdo_mysql, mbstring, intl, curl, gd/zip), Composer
- MySQL 8, Meilisearch, (Redis optional)
- A way to build frontend assets — **Node 20+** on a platform matching the
  runtime, **or** build in CI and ship `public/build` (see step 2).
- Web server (nginx) → `public/`, PHP-FPM.

## 1. Code + environment (one-time)
```bash
git clone … && cd app
cp .env.staging.example .env          # then fill in real values
php artisan key:generate              # sets APP_KEY
composer install --no-dev --optimize-autoloader --no-interaction
```

## 2. Build frontend assets
> ⚠️ The Vite bundler (rolldown) ships a **native binary per platform**. Build
> on the same OS/arch as the runtime (or in CI), **not** inside a foreign-arch
> container with mounted `node_modules`.
```bash
npm ci
npm run build                         # writes public/build/  (runs vue-tsc + vite)
```
Commit/ship `public/build/` if you build in CI.

## 3. Post-deploy steps (run on EVERY deploy)  ← "was muss nachträglich durchgeführt werden"
Run in this order (seed BEFORE caching so env() resolves in seeders):
```bash
php artisan migrate --force                       # 1. schema
php artisan db:seed --force                        # 2. admin, categories, organizers, settings
php artisan storage:link                           # 3. /storage symlink (logo, uploads)
# 4. caches (after seeding):
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan filament:cache-components
php artisan icons:cache
# 5. search index:
php artisan scout:import "App\Models\Event"        # (re)index published events in Meilisearch
```
On config changes you must re-run `config:cache` (or `optimize:clear`).

## 4. One-time assets / data
- **Logo:** place the header logo at `storage/app/public/logo.png`
  (served as `/storage/logo.png`). Without it the header image 404s.
- Meilisearch must be **running** before `scout:import` and before any event save
  (saving an event syncs to the index synchronously).

## 5. Background services (must run continuously)
```bash
# Queue worker — processes FetchEventLinkTitle + any future jobs:
php artisan queue:work --timeout=300 --tries=1     # via supervisor/systemd
```
**Scheduler** (cron) — runs the daily scrape + weekly URL discovery:
```cron
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

## 6. Populate the catalog on staging (data is NOT seeded — it is scraped)
The seeders create **organizers** (with venues/logos); events come from scraping:
```bash
php artisan organizers:discover-urls          # (optional) find .ics / listing feeds
php artisan events:scrape                      # import events for all organizers
php artisan venues:geocode                     # GPS coords per venue (OSM Nominatim, ~1 req/s)
php artisan events:fetch-link-titles --queue   # booking-link titles (async via worker)
php artisan scout:import "App\Models\Event"    # re-index after the import
```
New events scraped later auto-queue their booking title and auto-sync to Meilisearch.

## 7. Smoke test
- `https://staging…/` → events list loads (header logo visible).
- `/admin` → log in with `ADMIN_EMAIL` / `ADMIN_PASSWORD`.
- Admin → **Allgemein** → toggle "Nur mit Login zu nutzen" works.
- Search a term (Meilisearch), open an event, "Zur WebSeite" link → `/go/…`.
- Contact form → mail is actually delivered (real SMTP, not `log`).

## Notes
- **Login gate:** default off (public). Toggle in admin → Allgemein. When on,
  every page redirects guests to `/login` (auth pages + `/admin` stay reachable).
- **No ANTHROPIC_API_KEY** → scraper runs AI-free (JSON-LD + iCal) and still works.
- Re-seeding is **idempotent** (updateOrCreate / firstOrCreate); it won't clobber
  the login-gate toggle or duplicate organizers.
