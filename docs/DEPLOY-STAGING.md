# Staging Deployment

Laravel 13 · Inertia/Vue · Filament 5 · MySQL · Meilisearch · DB queue.

## 0. Prerequisites on the staging host
- PHP 8.4 (ext: pdo_mysql, mbstring, intl, curl, gd/zip), Composer
- MySQL 8, Meilisearch, (Redis optional)
- A way to build frontend assets — **Node 20+** on a platform matching the
  runtime, **or** build in CI and ship `public/build` (see step 2).
- Web server document root **must point at `public/`**, PHP-FPM/mod_php.

> ### ⚠️ Two deploy traps that produce exactly these errors
> 1. **Document root = project root** → `/` returns **403** and `/public/`
>    reaches the app. It also exposes `artisan`, `composer.json`, etc. over HTTP.
>    Fix: set the docroot to the `public/` sub-folder (see "Shared hosting /
>    SiteGround" below).
> 2. **`public/build/` is in `.gitignore`** → a `git clone`/`pull` deploy ships
>    **no compiled assets**, so `@vite` throws *"Vite manifest not found"* and
>    **every page 500s** (while `/up` still returns 200). Fix: upload
>    `public/build/` out-of-band (SFTP/rsync/CI artifact) — git will not carry it.

Was das für Laravel bedeutet
Statt public_html umzubenennen (geht nicht) oder Laravels public umzubenennen (fummelig), ist der saubere Standard für Shared Hosting genau umgekehrt: Du legst das Laravel-Projekt außerhalb von public_html ab und befüllst public_html nur mit dem Inhalt von Laravels public-Ordner.
So sieht das konkret aus. SiteGround-Struktur ist typischerweise:
/home/customer/www/deine-domain.com/
├── public_html/        ← Web-Root (fix)
└── laravel-app/        ← hier kommt das Projekt rein (NEBEN public_html)
Schritte:

Lade das komplette Laravel-Projekt nach laravel-app/ (neben public_html, nicht hinein).
Verschiebe den Inhalt von laravel-app/public/* nach public_html/ (also index.php, .htaccess, build/, favicon.ico usw.).
Passe in public_html/index.php die zwei Pfade an, damit sie auf dein Projekt zeigen:

<php 
require __DIR__.'/../laravel-app/vendor/autoload.php';

$app = require_once __DIR__.'/../laravel-app/bootstrap/app.php';
(Den relativen Pfad ../laravel-app/ ggf. an deine tatsächliche Ordnertiefe anpassen.)
Das ist robust, sicher (Code, .env, vendor liegen außerhalb des Web-Roots und sind nicht direkt aus dem Web erreichbar) und du musst weder bei SiteGround noch in Laravel irgendetwas umbenennen.
Alternative (wenn du alles in einem Ordner halten willst): Ganzes Projekt nach public_html/laravel-app/ und in public_html/.htaccess per Rewrite auf laravel-app/public/ zeigen. Funktioniert, ist aber weniger sauber, weil dann der App-Code unterhalb des Web-Roots liegt.
Da du dein Repo erotische-events.com per Git deployst: Willst du, dass ich dir das Deploy-Script von vorhin auf genau dieses Layout anpasse (Projekt nach laravel-app/, public/-Inhalt nach public_html/)?


### Shared hosting / SiteGround (no nginx vhost to edit)
The app is served behind nginx→Apache (mod_php). Point the domain at `public/`:
- **Preferred:** Site Tools → *Domain* → change the document root to
  `…/<app>/public` (works for sub-/add-on domains and, via support, the main
  domain).
- **Fallback (can't change docroot):** keep the app one level above the web root
  and make the web root hold *only* the contents of Laravel's `public/`, with
  `index.php` pointing up to `../<app>/vendor/autoload.php` and
  `../<app>/bootstrap/app.php`. Never leave the project root as the web root —
  that's what exposes `artisan`/`composer.json`.

## 1. Code + environment (one-time)
```bash
git clone … && cd app
cp .env.staging.example .env          # then fill in real values
php artisan key:generate              # sets APP_KEY
composer install --no-dev --optimize-autoloader --no-interaction
```

## 2. Build frontend assets
> ⚠️ **Do NOT build on SiteGround (shared hosting).** Node is memory-capped there;
> `npm run build` dies with *"JavaScript heap out of memory"* (the heap is killed
> at ~16 MB). Build on your machine or in CI and **upload** `public/build/`.
> Also: the Vite bundler (rolldown) ships a **native binary per platform** — build
> on the same OS/arch as the runtime (or in CI), not in a foreign-arch container.
```bash
npm ci
npm run build                         # writes public/build/  (vue-tsc + vite)
```
**`public/build/` is git-ignored** (see `.gitignore`). It will NOT travel with a
git deploy — upload it separately every time the frontend changes:
```bash
# from your build machine, after `npm run build` (SiteGround SSH port = 18765):
rsync -avz -e "ssh -p 18765" public/build/ \
  USER@corneld1.sg-host.com:/path/to/app/public/build/
```
A missing/stale `public/build/manifest.json` on the server = HTTP 500 on every
page ("Vite manifest not found"), even though `/up` health-checks pass.

**If you must build on the server** (last resort), skip the heavy type-check —
`vue-tsc` is the memory hog — and raise Node's heap:
```bash
NODE_OPTIONS=--max-old-space-size=2048 npm run build:only   # vite only, no vue-tsc
```
This may still OOM if the plan's hard memory cap is below what rolldown needs;
uploading a locally-built `public/build/` is the reliable path.

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

ln -s ../laravel-app/storage/app/public storage

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
- `https://corneld1.sg-host.com/` → events list loads (header logo visible).
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


