## EINMALIG
php artisan key:generate
Logo nach storage/app/public/logo.png
Meilisearch läuft (vor scout:import und vor jedem Event-Save)
queue:work --timeout=300
Cron: * * * * * php artisan schedule:run (täglicher Scrape, wöchentliche URL-Discovery)
Mail: echtes SMTP (sonst landet das Kontaktformular nur im Log)

Ohne ANTHROPIC_API_KEY läuft der Scraper KI-frei (JSON-LD + iCal) — funktioniert trotzdem.

php artisan events:scrape
php artisan venues:geocode
php artisan events:fetch-link-titles --queue
php artisan scout:import "App\Models\Event"

## Deploy on staging server
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache && route:cache && view:cache && event:cache
php artisan filament:cache-components && icons:cache
php artisan scout:import "App\Models\Event"


## Deploy on production server
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache && route:cache && view:cache && event:cache
php artisan filament:cache-components && icons:cache
php artisan scout:import "App\Models\Event"
