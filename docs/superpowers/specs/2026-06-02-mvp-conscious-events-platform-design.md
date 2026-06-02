# Design: MVP – Conscious Events Platform (erotische-events.com)

Date: 2026-06-02
Status: Approved (brainstorming) — ready for implementation planning

## 1. Zweck & Abgrenzung

Aggregierte Discovery-Plattform für Tantra-, Sex-Positive-, Conscious-Relating-,
Retreat-, Festival- und Workshop-Events.

**Die Plattform ist ein Aggregator und eine Lead-Generation-Plattform.** Sie
verlinkt zur Event-Seite beim Veranstalter; diese ausgehenden Links werden
getrackt (Statistik: wie oft → welcher Veranstalter → welches Event).

**Die Plattform ist KEIN** Buchungs-, Ticketing- oder Zahlungssystem. Es gibt
**kein** On-Platform-Buchungssystem und **kein** `BookingRequest`-Konzept (das
aus der alten `archiv/CLAUDE.md` wird verworfen).

Dieser Widerspruch zwischen `archiv/prd.md` (Aggregator) und `archiv/CLAUDE.md`
(Buchungsanfragen) ist zugunsten des Aggregator-Modells aufgelöst.

## 2. MVP-Schnitt

MVP = **Fundament + Suche/Geosearch**.

Enthalten:
- Core-Katalog (Events, Organizer, Venues, Teachers, Kategorien, Tags, Preise)
- Auth & Rollen (Gast, registrierter Nutzer, Organizer, Admin)
- Organizer-Self-Service inkl. Verifizierung + Event-Lifecycle
- Admin-Pflege über Filament (Admin und Organizer-Self-Service parallel)
- Outbound-Link-Tracking mit Geo/Device (DSGVO-konform, ohne IP-Speicherung)
- Favoriten für registrierte End-Nutzer
- Volltext-/Filter-Suche + Geosearch (Meilisearch)
- UI-Lokalisierung DE/EN

Nicht im MVP (jeweils eigene, spätere Spec → Plan → Implementierung):
Scraper-/Import-Plattform · KI (Auto-Kategorisierung, Tags, Übersetzung) ·
Reviews · Newsletter · Premium/Alerts · Sponsored Listings ·
öffentliche REST-API (Sanctum) · Native App · automatische Inhalts-Übersetzung.

## 3. Technischer Stack

- Backend: Laravel 13 / PHP 8.3 (entspricht installiertem `root/composer.json`;
  weicht bewusst von der „Laravel 12 / PHP 8.4"-Angabe der alten Spec ab)
- Frontend: Inertia.js + Vue 3 (Composition API), TypeScript, Pinia, TailwindCSS
- Admin: Filament (Version beim Bootstrap auf Laravel-13-Kompatibilität prüfen)
- Suche: Laravel Scout + Meilisearch
- Daten/Infra: MySQL, Redis, S3-kompatibler Storage, Docker/Sail
- Geo-IP: lokale GeoLite2-DB (oder gleichwertig); IP wird nicht gespeichert

## 4. Code-Struktur (Hybrid)

Standard-Laravel-Layout mit dünnen Controllern + Service-Layer; Subsysteme
gekapselt:

- `app/Models` — Relationen, Scopes, Accessors/Mutators (keine Workflows)
- `app/Http/Controllers` — dünn: Validierung, Autorisierung, Service-Aufruf
- `app/Services` — `EventPublishingService`, `OrganizerApprovalService`
- `app/Tracking` — `ClickTrackingService`, Geo-IP-Resolver
- `app/Search` — `SearchService` (Scout/Meilisearch + Geo-Query)
- Policies/Permissions je Modell

Drei Oberflächen, klar getrennt: öffentliche Inertia/Vue-App, Organizer-Dashboard
(Inertia), Filament-Admin. **Öffentliche Funktionalität hängt nie an Filament.**

## 5. Rollen

| Rolle | Kann |
|---|---|
| Gast | Events/Organizer suchen & ansehen, Outbound-Links nutzen |
| Registrierter Nutzer | zusätzlich: Favoriten speichern |
| Organizer (verifiziert) | Profil/Venues verwalten, Events anlegen/bearbeiten, eigene Statistik |
| Admin | Moderation, Verifizierung, Nutzer-/Kategorien-Verwaltung, System |

## 6. Datenmodell

Überall Foreign Keys, Indizes, Soft Deletes wo sinnvoll. Migrationen-getrieben
(keine manuellen Schemaänderungen).

| Tabelle | Kernfelder |
|---|---|
| `users` | role enum(user/organizer/admin), locale, auth-Felder, soft deletes |
| `organizers` | owner_user_id→users, company_name, contact_name, email, phone, website, social_links(json), description, logo, slug, verification_status enum(pending/approved/rejected) |
| `venues` | organizer_id, name, slug, description, street, postal_code, city, region, country, latitude, longitude, images(json), contact_info |
| `teachers` | name, slug, bio, photo, links(json) — n:m zu events |
| `events` | organizer_id, venue_id?, title, slug, short_description, long_description, main_image, start_date, end_date, status enum(draft/pending_review/published/rejected/archived), audience(json: singles/couples/men/women/lgbtq/everyone), min_participants?, max_participants?, languages(json), accommodation enum(none/optional/mandatory/external), currency, booking_url, source_url? |
| `event_prices` | event_id, type enum(early_bird/regular/late_bird), amount, currency, valid_until? |
| `categories` | slug, name_de, name_en, parent_id? (Hierarchie), position |
| `tags` | name, slug |
| `event_category` | event_id, category_id (pivot) |
| `event_tag` | event_id, tag_id (pivot) |
| `event_teacher` | event_id, teacher_id (pivot) |
| `favorites` | user_id, event_id (unique zusammengesetzt) |
| `event_clicks` | event_id, organizer_id, clicked_at, country(char2)?, device_type enum(desktop/mobile/tablet/other), referrer? — **keine IP gespeichert** |

Indizes u. a.: `events`(status, start_date, slug), `event_clicks`(event_id,
organizer_id, clicked_at), `organizers`(verification_status, slug),
`favorites`(user_id, event_id unique).

## 7. Kern-Flows

### Outbound-Tracking (Kernfeature)
`GET /go/{event}` → `ClickTrackingService` ermittelt Land via Geo-IP (IP danach
verworfen) und Gerätetyp via User-Agent → schreibt `event_clicks` → **302
Redirect** auf `event.booking_url`. Funktioniert ohne JavaScript. Statistik je
Event/Organizer im Filament-Dashboard.

### Suche / Geosearch
Scout + Meilisearch indexiert Events über Titel, Beschreibung, Organizer,
Teacher, Venue, Kategorien, Tags. Geo über `_geo` aus Venue-Koordinaten;
Radius-Filter (10/25/50/100/250 km / weltweit). Filter: Land, Region, Stadt,
Datum, Kategorie, Organizer, Sprache, Audience, Preis. Standortquellen:
Browser-Geolocation, manuelle Eingabe, IP-Fallback.

### Event-Lifecycle
`draft` → (einreichen) `pending_review` → Admin: `published` / `rejected`.
Nur `published` ist öffentlich sichtbar. `archived` für ausgelaufene Events.

### Organizer-Verifizierung
Self-Registrierung → `pending` → Admin-Benachrichtigung → approve/reject. Nur
`approved` darf publizieren.

### i18n
UI lokalisiert DE/EN über Laravel-Lang-Dateien; Locale via Session/Route; Inertia
teilt Locale ans Frontend. Event-Inhalte in Eingabesprache (keine
Auto-Übersetzung im MVP).

## 8. Tests

Gemäß Projektkonventionen: Feature- + Validierungs-Tests je Feature;
Integrationstests für Tracking-Redirect und Suche; Regressionstests bei Bugfixes.

## 9. Deliverable dieser Iteration: Doku `docs/01`–`08`

Die aktuell als Stub vorliegenden Dateien werden zu vollwertigen Dokumenten
ausgearbeitet (Inhalte aus `docs/archiv/` übernommen, Widersprüche aufgelöst):

- `01-prd.md` — MVP-fokussiertes PRD (Aggregator-Modell)
- `02-engineering-spec.md` — Stack, Hybrid-Struktur, Konventionen
- `03-database-schema.md` — feldgenaues Schema inkl. Indizes/FKs (Abschnitt 6)
- `04-openapi.yaml` — auf MVP zugeschnitten (Tracking-Redirect + JSON-Endpunkte);
  öffentliche REST-API als Phase 2 markiert
- `05-claude.md` — Projekt-CLAUDE.md / Operating Procedure (aktualisiert)
- `06-github-issues.md` — Epics/Issues für MVP B
- `07-implementation-roadmap.md` — Sprintfolge MVP B
- `08-category-taxonomy.md` — definierter Kategorienbaum + Tags

## 10. Offene Punkte für spätere Specs

Scraper-Architektur, KI-Provider (OpenAI/DeepL) & Pipeline, Reviews-Moderation,
Newsletter-Provider, Premium-Billing & Alerts, Sponsored-Placement-Logik,
Public-API-Vertrag (Sanctum) — jeweils eigener Brainstorm → Spec → Plan.
