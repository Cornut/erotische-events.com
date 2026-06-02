# Implementierungs-Roadmap: MVP – Conscious Events Platform

Version: 1.0  
Datum: 2026-06-02  
Quelle: Spec `docs/superpowers/specs/2026-06-02-mvp-conscious-events-platform-design.md`  
Status: Freigegeben

---

## Überblick

Diese Roadmap sequenziert den Software-MVP-Build nach Abschluss der
Dokumentationsphase. Grundlage sind Spec §2 (MVP-Schnitt), §3 (Stack), §4
(Struktur) und §6–§7 (Schema + Flows). Sechs Sprints (0–5) bauen aufeinander
auf; jeder Sprint hat ein klares Ziel, konkrete Deliverables, explizite
Abhängigkeiten und ein messbares Abnahmekriterium.

**Plattformmodell:** Aggregator + Outbound-Click-Tracking — kein Buchungssystem.  
**Stack:** Laravel 13 / PHP 8.3+ (Sail-Image: 8.4) · Inertia.js + Vue 3 + TypeScript + Pinia +
TailwindCSS · Filament 5 (`^5.6`) · Scout + Meilisearch · MySQL · Redis · Docker/Sail.

---

## Sprint 0 – Bootstrap

### Ziel

Lauffähige, versionierte Entwicklungsumgebung mit geprüfter Toolchain-Kompatibilität
und funktionierender CI-Pipeline — bevor die erste Fachlogik geschrieben wird.

### Deliverables

1. **Docker/Sail-Setup** — `docker-compose.yml` mit Diensten: `app` (PHP 8.3),
   `mysql`, `redis`, `meilisearch`; `.env.example` vollständig dokumentiert.
2. **Laravel 13 + Filament** — `composer.json` mit gepinnten Versionen (Laravel
   13.x, Filament 5 (`^5.6`) — verifiziert kompatibel mit Laravel 13); `composer.lock`
   eingecheckt.
3. **Frontend-Stack** — Inertia.js + Vue 3 (Composition API) + TypeScript +
   Pinia + TailwindCSS installiert; `vite.config.ts` konfiguriert; `tsconfig.json`
   vorhanden.
4. **Laravel Scout + Meilisearch** — `laravel/scout` und `meilisearch/meilisearch-php`
   installiert; Verbindung zu Meilisearch-Docker-Dienst verifiziert.
5. **Codequalität** — Laravel Pint (Linter/Formatter) konfiguriert; Pest/PHPUnit
   eingerichtet; erster Smoke-Test grün.
6. **Healthcheck-Route** — `GET /healthcheck` gibt `{ "status": "ok" }` zurück;
   dient als CI-Artefakt.
7. **CI-Pipeline** — GitHub Actions (oder gleichwertig): `composer install`,
   `npm ci`, `npm run build`, `php artisan test`, Pint-Check; läuft bei jedem Push
   auf `main` und auf Pull Requests.

### Abhängigkeiten

Keine — Sprint 0 ist der Startpunkt aller weiteren Sprints.

### Fertig, wenn ...

- `sail up` startet alle Dienste ohne manuelle Eingriffe.
- `php artisan test` läuft grün (Smoke-Test + Healthcheck-Test).
- `GET /healthcheck` antwortet mit HTTP 200.
- CI-Pipeline schlägt fehl, wenn Pint oder Tests rot sind.
- `composer.lock` und `package-lock.json` sind eingecheckt; keine
  Float-Versionsangaben für Filament/Laravel.

---

## Sprint 1 – Auth & Rollen

### Ziel

Vollständiges Authentifizierungssystem mit Rollenverwaltung für alle vier
Nutzertypen (Gast, Registrierter Nutzer, Organizer, Admin); Filament-Admin-Login
getrennt von der öffentlichen App.

### Deliverables

1. **Migration `users`** — Tabelle gemäß `docs/03-database-schema.md`: `role
   ENUM(user/organizer/admin)`, `locale VARCHAR(5)`, Soft Deletes; vollständige
   Indizes.
2. **User-Model + Role-Enum** — `App\Enums\UserRole`; Accessors; Scopes
   (`isAdmin()`, `isOrganizer()`).
3. **Registrierung + Login (Inertia)** — öffentliche Inertia/Vue-Routen
   `/register`, `/login`, `/logout`; Validierung; E-Mail-Verifizierung aktiviert.
4. **Filament-Admin-Login** — Filament-Panel unter `/admin`; separater Guard;
   nur Nutzer mit `role = admin` haben Zugang.
5. **Policies-Scaffold** — leere Policies für `User`, bereit für Sprint 2–5;
   `AuthServiceProvider` registriert alle Policies.
6. **Feature-Tests** — Registrierung (happy path + Validierungsfehler); Login;
   Rollenprüfung (Admin-Guard schlägt für `user` fehl).

### Abhängigkeiten

- Sprint 0 muss abgeschlossen sein (Datenbankverbindung, Inertia, Filament
  installiert).

### Fertig, wenn ...

- Ein neuer Nutzer kann sich registrieren, einloggen und ausloggen.
- Ein Admin-Nutzer kann sich unter `/admin` einloggen; ein normaler Nutzer
  wird abgewiesen.
- Alle Auth-Feature-Tests grün.
- CI bleibt grün.

---

## Sprint 2 – Core-Katalog

### Ziel

Vollständiges Datenbankschema für den gesamten Eventbestand; Filament-Ressourcen
für die Admin-Kuration; Seeder für die Kategorie-Taxonomie und Vorseeded-Tags.

### Deliverables

1. **Migrationen** (Reihenfolge beachtet FKs):
   - `organizers`, `venues`, `teachers`
   - `categories` (mit Selbstreferenz `parent_id`), `tags`
   - `events`, `event_prices`
   - Pivot-Tabellen: `event_category`, `event_tag`, `event_teacher`
   - `favorites`
   - `event_clicks`
   Alle Felder, Typen, Indizes und Soft Deletes gemäß `docs/03-database-schema.md`.
2. **Eloquent-Models** — `Organizer`, `Venue`, `Teacher`, `Category`, `Tag`,
   `Event`, `EventPrice`, `Favorite`, `EventClick`; Relationen vollständig
   (`hasMany`, `belongsTo`, `belongsToMany` inkl. Pivot-Models wo nötig);
   Scopes (z. B. `Event::published()`); Accessors/Mutators für JSON-Felder
   (`audience`, `languages`, `social_links`).
3. **Status-Enums** — `App\Enums\EventStatus` (`draft/pending_review/published/
   rejected/archived`), `App\Enums\VerificationStatus` (`pending/approved/rejected`),
   `App\Enums\DeviceType`, `App\Enums\PriceType`, `App\Enums\AccommodationType`.
4. **Filament-Ressourcen** (Admin-Panel) — `OrganizerResource`,
   `VenueResource`, `TeacherResource`, `CategoryResource`, `TagResource`,
   `EventResource`, `EventPriceResource`; List-, Create-, Edit-Seiten; Filter
   nach Status/Verification-Status.
5. **Seeder: Taxonomie** — `CategorySeeder` legt alle 12 Top-Level-Kategorien
   + 2 Unter-Kategorien (`shibari`, `kink` unter `bdsm`) gemäß
   `docs/08-category-taxonomy.md` an (Reihenfolge: Top-Level zuerst, dann
   Kinder); `TagSeeder` legt 15 Vorseeded-Tags an.
6. **Seeder: `DatabaseSeeder`** — ruft alle Seeder in der richtigen Reihenfolge
   auf; `php artisan db:seed` lässt sich idempotent ausführen.
7. **Policies** — `EventPolicy`, `OrganizerPolicy`, `VenuePolicy` mit Methoden
   `viewAny`, `view`, `create`, `update`, `delete` je Rolle.
8. **Feature-Tests** — Migration läuft durch; Seeder legt Kategorien/Tags korrekt
   an (Anzahl + Parent-Referenz); Filament-Admin kann Events auflisten.

### Abhängigkeiten

- Sprint 1 (Users-Tabelle + Auth vorhanden; Filament-Login funktioniert).

### Fertig, wenn ...

- `php artisan migrate --seed` läuft ohne Fehler durch.
- Alle 14 Kategorien (12 Top-Level + 2 Unter-Kategorien) und 15 Tags sind in
  der Datenbank vorhanden.
- Filament-Admin zeigt alle Ressourcen; CRUD-Operationen für Events funktionieren.
- Alle neuen Tests grün; CI grün.

---

## Sprint 3 – Organizer-Self-Service + Event-Lifecycle

### Ziel

Organizer können sich registrieren, verifiziert werden und ihre Venues sowie
Events vollständig selbst verwalten. Der Event-Lifecycle (`draft → pending_review
→ published / rejected`) ist implementiert. Öffentliche Event- und
Organizer-Profilseiten sind abrufbar.

### Deliverables

1. **`OrganizerApprovalService`** (`app/Services/OrganizerApprovalService.php`) —
   Methoden: `approve(Organizer $organizer): void`,
   `reject(Organizer $organizer, string $reason): void`;
   versendet Benachrichtigungs-Mail an den Organizer; aktualisiert
   `verification_status`; Admin wird bei neuer `pending`-Registrierung
   benachrichtigt.
2. **`EventPublishingService`** (`app/Services/EventPublishingService.php`) —
   Methoden: `submit(Event $event): void` (→ `pending_review`),
   `publish(Event $event): void` (→ `published`),
   `reject(Event $event, string $reason): void` (→ `rejected`),
   `archive(Event $event): void` (→ `archived`);
   prüft jeweils, ob der Organizer `approved` ist; wirft Exception bei
   ungültigem Zustandsübergang.
3. **Organizer-Registrierungsflow (Inertia)** — Formular `/organizer/register`;
   legt `Organizer`-Datensatz mit `verification_status = pending` an;
   informiert Admin.
4. **Organizer-Dashboard (Inertia)** — geschützter Bereich `/organizer/*`;
   Middleware prüft `role = organizer` + `verification_status = approved`;
   Seiten: Profil bearbeiten, Venues verwalten (CRUD), Events verwalten (CRUD +
   Lifecycle-Aktionen: Einreichen, Archivieren).
5. **Admin-Workflow in Filament** — Organizer genehmigen/ablehnen über
   `OrganizerApprovalService`; Events freigeben/ablehnen über
   `EventPublishingService`; Actions in den jeweiligen Filament-Ressourcen.
6. **Öffentliche Seiten (Inertia/Vue)** —
   `GET /events/{slug}` (Event-Detailseite; nur `published`);
   `GET /organizer/{slug}` (Organizer-Profilseite mit veröffentlichten Events).
7. **Policies aktualisiert** — Organizer darf nur eigene Venues/Events bearbeiten;
   nur Admin darf publizieren/ablehnen.
8. **Feature-Tests** — `OrganizerApprovalService` (approve + reject); `EventPublishingService`
   (alle Zustandsübergänge, inkl. ungültige); Organizer-Dashboard nur für
   `approved` Organizer zugänglich; öffentliche Eventseite gibt 404 für
   nicht-veröffentlichte Events.

### Abhängigkeiten

- Sprint 2 (alle Models, Migrationen und Filament-Ressourcen vorhanden).
- Sprint 1 (Auth + Rollen für Middleware-Prüfungen).

### Fertig, wenn ...

- Organizer-Registrierung, Admin-Genehmigung und Event-Einreichung/Freigabe
  sind als vollständiger Flow von Ende zu Ende testbar.
- Öffentliche Event-Detailseite zeigt nur `published`-Events.
- Alle Service- und Feature-Tests grün; CI grün.

---

## Sprint 4 – Suche & Geosearch

### Ziel

Volltext- und Filter-Suche über den Event-Katalog mit Radius-Geosearch auf
Basis von Meilisearch; Favoriten-Funktion für registrierte Nutzer; öffentliche
Such-UI.

### Deliverables

1. **Meilisearch-Index-Konfiguration** — `Event`-Model implementiert
   `Laravel\Scout\Searchable`; `toSearchableArray()` indexiert: Titel,
   Kurz-/Langbeschreibung, Organizer-Name, Teacher-Namen, Venue-Name,
   Kategorien, Tags, Status, Start-/Enddatum, Land, Stadt, Audience, Sprachen;
   Geo-Koordinaten als Meilisearch-`_geo`-Feld (aus verknüpftem Venue);
   nur `published`-Events werden indexiert.
2. **`SearchService`** (`app/Search/SearchService.php`) — öffentliche Methode
   `search(SearchQuery $dto): SearchResult`; Parameter: Volltext-Query,
   Kategorie-Filter, Tag-Filter, Land/Region/Stadt, Datum-Bereich, Audience,
   Sprache, Preis-Range, Radius in km (10/25/50/100/250/weltweit), Geo-Koordinaten
   (Mittelpunkt); Pagination; gibt geordnetes Ergebnis-Set zurück.
3. **Geo-Middleware** — Standortbestimmung: Browser-Geolocation (Frontend)
   und manuelle Eingabe; Koordinaten werden als Session-Wert oder
   Query-Parameter weitergegeben. (IP-basierter Fallback folgt in Sprint 5.)
4. **Such-UI (Inertia/Vue)** — öffentliche Seite `GET /events` mit:
   Volltext-Suchfeld; Kategorien-Filter (Checkbox-Baum); Radius-Selektor;
   Datum-Picker; Audience-Filter; Sprachen-Filter; Ergebnis-Liste mit Karten;
   Pagination; reaktiv (Pinia-Store für Filterstate).
5. **Favoriten** — `POST /favorites/{event}` / `DELETE /favorites/{event}` (nur
   für `role = user` und `role = organizer`); `FavoriteController` schreibt in
   `favorites`-Tabelle; Unique-Constraint verhindert Duplikate; Herz-Icon in
   der Such-UI und auf Event-Detailseite.
6. **Scout-Index-Synchronisation** — `php artisan scout:import` als optionaler
   Artisan-Befehl; Event-Model-Observer oder `saved`/`deleted`-Hooks aktualisieren
   den Index bei Statusänderungen.
7. **Feature- und Integrationstests** — `SearchService` mit Filtern; Geosearch
   liefert Events im Radius, nicht Events außerhalb; Favoriten-Toggle (add +
   remove + Duplikat-Prüfung); Such-UI-Route antwortet mit HTTP 200.

### Abhängigkeiten

- Sprint 2 (Events-Model, Kategorien, Tags, Venues mit Koordinaten im Seed).
- Sprint 3 (nur `published`-Events werden indexiert; Lifecycle-Service setzt
  den Status).
- Sprint 0 (Meilisearch-Docker-Dienst läuft).

### Fertig, wenn ...

- `php artisan scout:import "App\Models\Event"` indexiert alle
  `published`-Events ohne Fehler.
- Suche nach Titel-Keyword liefert korrekte Events.
- Radius-Filter schließt Events außerhalb des gewählten Radius aus.
- Registrierter Nutzer kann ein Event als Favorit speichern und wieder entfernen.
- Alle Tests grün; CI grün.

---

## Sprint 5 – Outbound-Tracking + i18n + Finalisierung

### Ziel

Outbound-Click-Tracking als Kernfeature vollständig implementiert (DSGVO-konform,
ohne IP-Speicherung); UI-Lokalisierung DE/EN; Filament-Statistik-Dashboard;
Testabdeckung auf Gesamtprojektniveau geprüft und Lücken geschlossen.

### Deliverables

1. **`ClickTrackingService`** (`app/Tracking/ClickTrackingService.php`) — Methode
   `track(Event $event, Request $request): void`; ermittelt Gerätekategorie via
   User-Agent (`desktop/mobile/tablet/other`); ermittelt Land via GeoLite2-Lookup
   auf die Request-IP — IP wird danach **nicht** gespeichert; schreibt einen
   `EventClick`-Datensatz (`event_id`, `organizer_id`, `clicked_at`,
   `country`, `device_type`, `referrer`); Fehler im Tracking dürfen den Redirect
   nicht blockieren.
2. **`GeoIpResolver`** (`app/Tracking/GeoIpResolver.php`) — kapselt GeoLite2-DB
   (oder gleichwertiges lokales Lookup); gibt ISO-3166-1-Alpha-2-Ländercode zurück
   oder `null` bei unbekannter IP (privat/intern); IP wird nur im Speicher
   verwendet, nie persistiert. Wird von zwei Stellen genutzt: (a) von
   `ClickTrackingService` zur Länder-Ermittlung beim Outbound-Tracking und
   (b) als IP-basierter Standort-Fallback in der Geo-Middleware (ergänzt die
   Browser-Geolocation + manuelle Eingabe aus Sprint 4).
2a. **IP-basierter Standort-Fallback (Geo-Middleware-Erweiterung)** — erweitert
   die in Sprint 4 gelieferte Geo-Middleware um einen dritten Fallback-Pfad:
   Steht weder Browser-Geolocation noch manuelle Eingabe zur Verfügung, wird
   `GeoIpResolver` für einen ungefähren Standort-Hinweis genutzt; IP wird
   danach verworfen.
3. **Tracking-Route** — `GET /go/{event:slug}` → `ClickController@redirect`;
   ruft `ClickTrackingService::track()` auf; antwortet mit HTTP 302 auf
   `event.booking_url`; funktioniert ohne JavaScript; Rate-Limiting per
   Laravel-Throttle-Middleware, um Crawlerflut zu dämpfen.
4. **Filament-Statistik-Dashboard** — eigenes Filament-Dashboard (oder
   Widgets in `EventResource`) mit:
   - Klicks je Event (letzter 7/30/90 Tage);
   - Klicks je Organizer;
   - Aufschlüsselung nach Land und Gerätetyp;
   - Organizer-eigene Statistik im Organizer-Dashboard (nur eigene Events).
5. **i18n DE/EN** — Laravel-Lang-Dateien für alle UI-Strings (`resources/lang/de`
   + `resources/lang/en`); Locale-Auswahl via Session und optionalem URL-Prefix;
   Inertia teilt `locale` und Übersetzungs-Strings ans Vue-Frontend; `usePage()
   .props.locale` steuert das Sprachmenü; Event-Inhalte bleiben in ihrer
   Eingabesprache (keine Auto-Übersetzung im MVP).
6. **Testabdeckung — Schlusskontrolle** — alle Feature-Tests aus Sprint 1–4
   bestehen; zusätzliche Integrationstests:
   - Tracking-Redirect: `GET /go/{slug}` → 302 auf korrekte URL; `event_clicks`-
     Datensatz enthält kein IP-Feld;
   - GeoLite2-Resolver gibt `null` für Loopback-IP zurück;
   - Vollständiger Organizer-Event-Lifecycle von Registrierung bis Published
     (End-to-End-Feature-Test);
   - Such-Integration mit aktivem Meilisearch-Docker-Container.
7. **Code-Qualität & Abschluss** — Pint läuft grün; PHPStan (Level 6 oder
   Projektstandard) ohne Fehler; alle `TODO`/`FIXME`-Kommentare aufgelöst oder
   in Issues überführt; `README.md` mit Setup-Anleitung (`sail up`, `db:seed`,
   `scout:import`) aktualisiert.

### Abhängigkeiten

- Sprint 4 (Search, Favorites, Events öffentlich abrufbar).
- Sprint 3 (Event-Lifecycle und öffentliche Eventseite für den Redirect).
- Sprint 2 (`event_clicks`-Tabelle und `EventClick`-Model vorhanden).
- Sprint 0 (GeoLite2-Datei in Docker-Volume bereitgestellt).

### Fertig, wenn ...

- `GET /go/{slug}` eines `published`-Events liefert HTTP 302 auf die korrekte
  `booking_url` und erzeugt einen `EventClick`-Datensatz ohne IP-Feld.
- Filament zeigt Klick-Statistiken je Event und Organizer.
- UI wechselt korrekt zwischen Deutsch und Englisch.
- Alle Feature- und Integrationstests grün; CI grün.
- Kein MVP-Scope-Punkt aus Spec §2 ist unimplementiert.

---

## Abhängigkeitsgraph (Übersicht)

```
Sprint 0 (Bootstrap)
  └── Sprint 1 (Auth & Rollen)
        └── Sprint 2 (Core-Katalog)
              └── Sprint 3 (Organizer + Lifecycle)
              │     └── Sprint 4 (Suche + Geosearch)
              │           └── Sprint 5 (Tracking + i18n + Abschluss)
              └── Sprint 5 (event_clicks-Migration)
```

Sprint 5 benötigt direkt Sprint 2 (Datenbankschema für `event_clicks`) und
Sprint 0 (GeoLite2-Setup), zusätzlich zu Sprint 4 für den vollständigen
integrierten Flow.

---

## MVP-Scope-Mapping (Spec §2 → Sprint)

| MVP-Scope-Punkt (Spec §2) | Sprint |
|---|---|
| Core-Katalog (Events, Organizer, Venues, Teachers, Kategorien, Tags, Preise) | 2 |
| Auth & Rollen (Gast, Registrierter Nutzer, Organizer, Admin) | 1 |
| Organizer-Self-Service inkl. Verifizierung + Event-Lifecycle | 3 |
| Admin-Pflege über Filament | 2, 3 |
| Outbound-Link-Tracking mit Geo/Device (DSGVO-konform, ohne IP-Speicherung) | 5 |
| Favoriten für registrierte End-Nutzer | 4 |
| Volltext-/Filter-Suche + Geosearch (Meilisearch) | 4 |
| UI-Lokalisierung DE/EN | 5 |

Alle acht Scope-Punkte aus Spec §2 sind abgedeckt. Nicht-MVP-Punkte (Scraper,
KI, Reviews, Newsletter, Premium, Sponsored, öffentliche REST-API, Native App,
Auto-Übersetzung) sind in keinem Sprint enthalten.

---

## Service-Verzeichnis

| Service / Klasse | Namespace | Sprint |
|---|---|---|
| `OrganizerApprovalService` | `app/Services` | 3 |
| `EventPublishingService` | `app/Services` | 3 |
| `SearchService` | `app/Search` | 4 |
| `ClickTrackingService` | `app/Tracking` | 5 |
| `GeoIpResolver` | `app/Tracking` | 5 |
