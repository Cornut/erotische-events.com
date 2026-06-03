# Sprint 5 — Outbound Tracking & i18n Implementation Plan

> Executed inline (TDD). sqlite :memory:. 95 tests pass on `main`.

**Goal:** The platform's core feature — clicking through to the organizer is tracked. `GET /go/{event}` records a click (event, organizer, timestamp, country via GeoLite2, device type; **no IP stored**) and 302-redirects to `event.booking_url`. Admins see click counts in Filament. UI is switchable DE/EN.

**Tasks:**
1. **Tracking data** — `event_clicks` table (event_id, organizer_id, clicked_at, country char(2) nullable, device_type, referrer nullable; indexes; NO IP). `DeviceType` enum (desktop/mobile/tablet/other). `EventClick` model + `Event::clicks()`.
2. **GeoIpResolver** (`app/Tracking`) — `countryFor(?string $ip): ?string`; uses a GeoLite2 mmdb when configured (`config('services.geoip.database')`), returns null otherwise (so it is safe with no DB / in tests). Install `geoip2/geoip2`.
3. **ClickTrackingService** (`app/Tracking`) — `record(Event $event, ?string $ip, ?string $userAgent, ?string $referrer)`: resolves country (GeoIpResolver), device type (UA heuristic), writes an `EventClick`. IP is used only transiently, never stored.
4. **GoController + route** — `GET /go/{event}` (published only → else 404): record click, then `redirect()->away($event->booking_url)` (302). Tests: records a row with device_type + null country + no IP column; returns 302 to booking_url; 404 for unpublished.
5. **Filament stats** — `clicks_count` column on the Events table (`->counts('clicks')`).
6. **i18n** — `SetLocale` middleware (session('locale') ∈ {de,en}, default de) registered in the web group; `GET /locale/{locale}` switch route; `lang/de.json` + `lang/en.json`; share `locale` to Inertia via `HandleInertiaRequests`. Tests: default locale de; switching to en persists in session and sets `app()->getLocale()`.

**Conventions:** branch `feat/sprint-5-tracking-i18n`; per-unit commits; after each: `php artisan test` green + `vendor/bin/pint --test` clean (+ `npm run build` for Vue/Inertia share changes).
