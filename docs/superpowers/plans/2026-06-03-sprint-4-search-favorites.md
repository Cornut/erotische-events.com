# Sprint 4 — Search/Geosearch & Favorites Implementation Plan

> Executed inline (TDD). Tests on sqlite :memory:. 83 tests pass on `main`.

**Goal:** Registered users can favorite events; the public catalog supports full-text-ish search + structured filters + radius (bounding-box) geosearch. Events are made Scout-`Searchable` so a Meilisearch index can be built in production; the MVP `SearchService` query path is Eloquent-based (reliable + testable on sqlite), with text matching via DB LIKE (swappable for Scout/Meilisearch later).

**Tasks:**
1. **Favorites** — `favorites` table (user_id, event_id, unique), `User::favorites()` / `Event::favoritedBy()` belongsToMany, toggle endpoint + favorites list (Inertia), tests (favorite/unfavorite, guest redirect, list shows only own).
2. **Event Searchable** — add `Laravel\Scout\Searchable` to `Event` with `toSearchableArray()` (title, short/long description, organizer name, venue city/country, category/tag/teacher names) and `_geo` from venue lat/lng; `shouldBeSearchable()` = published only. Unit test on the array shape.
3. **SearchService** — `App\Search\SearchService::search(array $filters)` returns a paginator of `Event::published()` constrained by: `q` (LIKE on title/short/long), `category` (whereHas slug), `country`/`city` (whereHas venue), `date_from`/`date_to` (start_date), `price_min`/`price_max` (whereHas prices amount), `near` (lat,lng) + `radius_km` (bounding-box whereBetween on venue lat/lng). Tests per filter on sqlite.
4. **Public search UI** — `Public\EventController@index` delegates to `SearchService` using request query params; `Public/Events/Index.vue` gets a search form (q, category, city, date) that GETs with query string. Test: filtered index returns only matching published events.

**Conventions:** branch `feat/sprint-4-search-favorites`; per-unit commits; after each: `php artisan test` green + `vendor/bin/pint --test` clean (+ `npm run build` for Vue changes).
