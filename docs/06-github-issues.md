# GitHub Issues – MVP Backlog

Version: 1.0  
Datum: 2026-06-02  
Quelle: Spec `docs/superpowers/specs/2026-06-02-mvp-conscious-events-platform-design.md`  
Roadmap: `docs/07-implementation-roadmap.md`

> **Konventionen**
>
> - Jedes Issue entspricht einem Pull Request.
> - Labels: `area:backend` · `area:frontend` · `area:admin` · `area:search`
>   · `area:tracking` · `area:devops` · `area:i18n`
> - Akzeptanzkriterien (AK) sind maschinenlesbar formuliert: „grüne Tests",
>   „HTTP-Status X", „Filament zeigt Y" usw.
> - Entitäten und Services referenzieren ausschließlich Schema aus
>   `docs/03-database-schema.md` und Services aus Spec §4 / Roadmap
>   Service-Verzeichnis.

---

## Epic 0 – Bootstrap & Tooling (Sprint 0)

> Ziel: Lauffähige, versionierte Entwicklungsumgebung mit CI-Pipeline, bevor
> die erste Fachlogik geschrieben wird.

---

### #001 Docker/Sail-Setup mit `app`, `mysql`, `redis`, `meilisearch`

**Labels:** `area:devops`

**AK:** `sail up` startet alle vier Dienste ohne manuellen Eingriff;
`docker-compose.yml` und `.env.example` sind eingecheckt.

---

### #002 Laravel 13 + Filament 3 installieren und pinnen

**Labels:** `area:backend` · `area:devops`

**AK:** `composer.json` enthält gepinnte Versionen für Laravel 13.x und
Filament 3.x (auf Laravel-13-Kompatibilität geprüft); `composer.lock`
ist eingecheckt; keine Float-Versionsangaben.

---

### #003 Frontend-Stack: Inertia.js + Vue 3 + TypeScript + Pinia + TailwindCSS

**Labels:** `area:frontend` · `area:devops`

**AK:** `vite.config.ts` und `tsconfig.json` sind vorhanden; `npm run build`
läuft fehlerfrei durch; `package-lock.json` ist eingecheckt.

---

### #004 Laravel Scout + Meilisearch-PHP installieren und Verbindung verifizieren

**Labels:** `area:backend` · `area:search` · `area:devops`

**AK:** `laravel/scout` und `meilisearch/meilisearch-php` sind installiert;
ein Artisan-Smoke-Kommando (`scout:status` o. Ä.) oder ein Integrationstest
bestätigt die Verbindung zum Meilisearch-Docker-Dienst.

---

### #005 Laravel Pint + Pest/PHPUnit konfigurieren

**Labels:** `area:devops`

**AK:** `./vendor/bin/pint --test` läuft ohne Fehler; `php artisan test`
startet und ein erster Smoke-Test ist grün.

---

### #006 `GET /healthcheck`-Route

**Labels:** `area:backend` · `area:devops`

**AK:** `GET /healthcheck` antwortet mit HTTP 200 und Body
`{ "status": "ok" }`; ein Feature-Test deckt diesen Endpunkt ab.

---

### #007 CI-Pipeline (GitHub Actions)

**Labels:** `area:devops`

**AK:** Pipeline führt bei jedem Push auf `main` und bei Pull Requests
`composer install`, `npm ci`, `npm run build`, `php artisan test` und
Pint-Check aus; schlägt fehl, wenn Pint oder Tests rot sind.

---

## Epic 1 – Authentication & Roles (Sprint 1)

> Ziel: Vollständiges Auth-System mit Rollenverwaltung für alle vier
> Nutzertypen; Filament-Admin-Login getrennt von der öffentlichen App.

---

### #101 Migration + Model für `users` mit `role`-Enum und `locale`

**Labels:** `area:backend`

**AK:** Migration legt `users`-Tabelle gemäß `docs/03-database-schema.md`
an (`role ENUM(user/organizer/admin)`, `locale VARCHAR(5)`, Soft Delete,
alle Indizes); `App\Enums\UserRole` ist definiert; `User`-Model hat
Scopes `isAdmin()` und `isOrganizer()`; `php artisan migrate` läuft grün.

---

### #102 Registrierung und Login (Inertia/Vue)

**Labels:** `area:frontend` · `area:backend`

**AK:** Öffentliche Routen `/register`, `/login`, `/logout` funktionieren;
E-Mail-Verifizierung ist aktiviert; Validierungsfehler werden an die
Vue-Komponente zurückgegeben; Feature-Tests für Happy-Path und
Validierungsfehler sind grün.

---

### #103 Filament-Admin-Login unter `/admin` mit separatem Guard

**Labels:** `area:admin` · `area:backend`

**AK:** Filament-Panel ist unter `/admin` erreichbar; nur Nutzer mit
`role = admin` werden eingelassen; ein Nutzer mit `role = user` erhält
eine Zugriffsverweigerung; Feature-Test deckt beide Fälle ab.

---

### #104 Policies-Scaffold für `User`

**Labels:** `area:backend`

**AK:** Leere `UserPolicy` ist angelegt; `AuthServiceProvider` registriert
sie; `php artisan test` bleibt grün.

---

## Epic 2 – Core Catalog & Admin Resources (Sprint 2)

> Ziel: Vollständiges Datenbankschema für den Eventbestand; Filament-Ressourcen
> für die Admin-Kuration; Seeder für Taxonomie und Tags.

---

### #201 Migrationen für `organizers`, `venues`, `teachers`

**Labels:** `area:backend`

**AK:** Alle drei Tabellen werden gemäß `docs/03-database-schema.md` mit
korrekten Feldern, Typen, FKs und Soft Deletes angelegt; `php artisan migrate`
läuft ohne Fehler.

---

### #202 Migrationen für `categories` (mit `parent_id`) und `tags`

**Labels:** `area:backend`

**AK:** `categories` enthält Selbstreferenz `parent_id`; `tags` hat Unique
auf `slug` und `name`; alle Indizes vorhanden; Migration läuft durch.

---

### #203 Migrationen für `events`, `event_prices` und alle Pivot-Tabellen

**Labels:** `area:backend`

**AK:** `events`, `event_prices`, `event_category`, `event_tag`,
`event_teacher` und `favorites` werden korrekt angelegt inkl. Composite
Primary Keys bei Pivot-Tabellen, ON DELETE CASCADE, Soft Delete bei
`events`; alle Indizes aus `docs/03-database-schema.md` vorhanden;
Migration läuft durch.

---

### #204 Migration für `event_clicks`

**Labels:** `area:backend` · `area:tracking`

**AK:** `event_clicks`-Tabelle enthält kein IP-Feld; alle Spalten aus
`docs/03-database-schema.md` vorhanden (`event_id`, `organizer_id`,
`clicked_at`, `country`, `device_type`, `referrer`); kein `updated_at`,
kein Soft Delete; Index `(event_id, organizer_id, clicked_at)` vorhanden.

---

### #205 Eloquent-Models: `Organizer`, `Venue`, `Teacher`

**Labels:** `area:backend`

**AK:** Modelle haben alle Relationen (`Organizer hasMany Venue`,
`Organizer hasMany Event`, `Venue belongsTo Organizer`); `Organizer`
hat `VerificationStatus`-Enum-Cast; `social_links` wird als JSON-Accessor
gecasted; Feature-Test prüft Relation und Cast.

---

### #206 Eloquent-Models: `Category`, `Tag`

**Labels:** `area:backend`

**AK:** `Category` hat Selbstreferenz-Relationen `parent()` und
`children()`; `Tag` hat keine weiteren Abhängigkeiten; Feature-Test prüft
Parent-Kind-Beziehung bei `Category`.

---

### #207 Eloquent-Models: `Event`, `EventPrice`, `Favorite`, `EventClick`

**Labels:** `area:backend`

**AK:** `Event` hat alle BelongsTo/HasMany/BelongsToMany-Relationen;
`Event::published()`-Scope gibt nur Events mit `status = published` zurück;
`audience`- und `languages`-Felder werden als JSON-Arrays gecasted;
`EventStatus`-Enum ist definiert; `App\Enums\PriceType`,
`App\Enums\DeviceType`, `App\Enums\AccommodationType` sind definiert;
Feature-Test deckt Scopes und Casts ab.

---

### #208 Filament-Ressource für `Organizer`

**Labels:** `area:admin`

**AK:** `OrganizerResource` hat List-, Create- und Edit-Seite; Filter nach
`verification_status`; Admin kann einen `Organizer`-Datensatz anlegen und
bearbeiten; Feature-Test bestätigt, dass Filament-Admin die Liste aufrufen
kann.

---

### #209 Filament-Ressourcen für `Venue`, `Teacher`

**Labels:** `area:admin`

**AK:** `VenueResource` und `TeacherResource` haben List-, Create- und
Edit-Seiten; Feature-Test bestätigt Aufruf durch Filament-Admin.

---

### #210 Filament-Ressourcen für `Category`, `Tag`

**Labels:** `area:admin`

**AK:** `CategoryResource` zeigt `parent_id` als Dropdown der bestehenden
Top-Level-Kategorien; `TagResource` hat List- und Create-Seite; Feature-Test
bestätigt Aufruf.

---

### #211 Filament-Ressourcen für `Event` und `EventPrice`

**Labels:** `area:admin`

**AK:** `EventResource` hat List-, Create- und Edit-Seite; Filter nach
`status` (alle `EventStatus`-Werte); `EventPriceResource` ist als Relation
in `EventResource` eingebettet (Repeater oder Relation Manager); Feature-Test
prüft, dass Filament-Admin Events nach Status filtern kann.

---

### #212 `CategorySeeder` (12 Top-Level + 2 Unter-Kategorien) und `TagSeeder` (15 Tags)

**Labels:** `area:backend`

**AK:** `php artisan db:seed` legt idempotent genau 14 Kategorien (12
Top-Level + `shibari` und `kink` unter `bdsm`) gemäß
`docs/08-category-taxonomy.md` und 15 Vorseeded-Tags an; Feature-Test prüft
Anzahl und Parent-Referenz.

---

### #213 Policies für `Event`, `Organizer`, `Venue`

**Labels:** `area:backend`

**AK:** `EventPolicy`, `OrganizerPolicy`, `VenuePolicy` implementieren
`viewAny`, `view`, `create`, `update`, `delete` je Rolle; in
`AuthServiceProvider` registriert; Pest-Tests prüfen Deny- und
Allow-Fälle für `user`- und `admin`-Rollen.

---

## Epic 3 – Organizer Self-Service & Event Lifecycle (Sprint 3)

> Ziel: Organizer können sich registrieren, verifiziert werden und ihre
> Venues und Events vollständig selbst verwalten; Event-Lifecycle implementiert;
> öffentliche Seiten abrufbar.

---

### #301 `OrganizerApprovalService`: `approve()` und `reject()`

**Labels:** `area:backend`

**AK:** `app/Services/OrganizerApprovalService.php` implementiert
`approve(Organizer $organizer): void` und
`reject(Organizer $organizer, string $reason): void`; beide Methoden
aktualisieren `verification_status` auf `organizers`; eine
Benachrichtigungs-Mail wird an den Organizer versendet; Feature-Test prüft
Status-Wechsel und Mailversand (Fake-Mailer).

---

### #302 Admin-Benachrichtigung bei neuer `pending`-Organizer-Registrierung

**Labels:** `area:backend` · `area:admin`

**AK:** Bei Anlage eines `Organizer`-Datensatzes mit
`verification_status = pending` wird eine Mail an die Admin-Adresse
versendet; Feature-Test mit Fake-Mailer bestätigt den Versand.

---

### #303 `EventPublishingService`: alle Zustandsübergänge

**Labels:** `area:backend`

**AK:** `app/Services/EventPublishingService.php` implementiert `submit()`,
`publish()`, `reject()`, `archive()`; jede Methode prüft den
`verification_status` des Organisators und wirft eine Exception bei
ungültigem Übergang; Feature-Tests decken alle gültigen Übergänge und
mindestens zwei ungültige Übergänge ab.

---

### #304 Organizer-Registrierungsformular (Inertia/Vue, Route `/organizer/register`)

**Labels:** `area:frontend` · `area:backend`

**AK:** Formular legt einen `Organizer`-Datensatz mit
`verification_status = pending` an und verknüpft ihn mit dem
eingeloggten `users`-Datensatz; Admin-Benachrichtigung wird ausgelöst
(#302); Feature-Test prüft Happy-Path und Validierungsfehler.

---

### #305 Organizer-Dashboard-Middleware und Basisstruktur (`/organizer/*`)

**Labels:** `area:frontend` · `area:backend`

**AK:** Middleware prüft `role = organizer` und `verification_status = approved`;
nicht verifizierte Organizer werden weitergeleitet; Feature-Test bestätigt,
dass ein `pending`-Organizer keinen Zugang erhält.

---

### #306 Organizer-Dashboard: Profil bearbeiten

**Labels:** `area:frontend` · `area:backend`

**AK:** Seite `/organizer/profile` erlaubt Bearbeitung von `Organizer`-Feldern
(`company_name`, `contact_name`, `email`, `phone`, `website`,
`social_links`, `description`, `logo`); nur eigener Datensatz; Policy
verhindert Zugriff auf fremde Organizer-Profile; Feature-Test prüft Update
und Policy-Deny.

---

### #307 Organizer-Dashboard: Venues verwalten (CRUD)

**Labels:** `area:frontend` · `area:backend`

**AK:** Seiten unter `/organizer/venues` erlauben Erstellen, Bearbeiten
und Löschen eigener `venues`-Datensätze; `VenuePolicy` verhindert Zugriff
auf fremde Venues; Feature-Test prüft alle CRUD-Operationen.

---

### #308 Organizer-Dashboard: Events verwalten (CRUD + Lifecycle-Aktionen)

**Labels:** `area:frontend` · `area:backend`

**AK:** Seiten unter `/organizer/events` erlauben Erstellen, Bearbeiten
und Löschen eigener `events`-Datensätze; Schaltflächen für „Einreichen"
(→ `EventPublishingService::submit()`) und „Archivieren" (→
`EventPublishingService::archive()`) sind vorhanden; `EventPolicy`
verhindert Zugriff auf fremde Events; Feature-Test prüft Einreichen-Flow.

---

### #309 Filament-Aktion: Organizer genehmigen/ablehnen via `OrganizerApprovalService`

**Labels:** `area:admin`

**AK:** `OrganizerResource` hat `ApproveAction` und `RejectAction`
(Filament-Actions), die `OrganizerApprovalService` aufrufen;
`verification_status` wird korrekt aktualisiert; Feature-Test prüft
den Aufruf des Services aus dem Admin-Panel.

---

### #310 Filament-Aktion: Events freigeben/ablehnen via `EventPublishingService`

**Labels:** `area:admin`

**AK:** `EventResource` hat `PublishAction` und `RejectAction`, die
`EventPublishingService` aufrufen; nur Events mit
`status = pending_review` können freigegeben/abgelehnt werden; Feature-Test
prüft den Aufruf.

---

### #311 Öffentliche Event-Detailseite `GET /events/{slug}`

**Labels:** `area:frontend` · `area:backend`

**AK:** Seite zeigt nur `published`-Events; nicht veröffentlichte Events
liefern HTTP 404; alle Felder aus `events`, `event_prices`, `venues`,
`organizers` und `teachers` werden korrekt dargestellt; Feature-Test prüft
200 für `published` und 404 für `draft`.

---

### #312 Öffentliche Organizer-Profilseite `GET /organizer/{slug}`

**Labels:** `area:frontend` · `area:backend`

**AK:** Seite zeigt den `Organizer`-Datensatz mit allen zugehörigen
`published`-Events; nicht vorhandene Slugs liefern HTTP 404; Feature-Test
bestätigt Anzeige und 404-Verhalten.

---

## Epic 4 – Search & Geosearch + Favorites (Sprint 4)

> Ziel: Volltext- und Filter-Suche über den Event-Katalog mit Radius-Geosearch
> auf Basis von Meilisearch; Favoriten-Funktion für registrierte Nutzer.

---

### #401 Meilisearch-Index-Konfiguration für `Event` (Scout `toSearchableArray`)

**Labels:** `area:backend` · `area:search`

**AK:** `Event`-Model implementiert `Laravel\Scout\Searchable`;
`toSearchableArray()` indexiert Titel, Beschreibungen, Organizer-Name,
Teacher-Namen, Venue-Name, Kategorien, Tags, Status, Start-/Enddatum,
Land, Stadt, Audience, Sprachen; Geo-Koordinaten werden als Meilisearch-`_geo`-Feld
aus dem verknüpften `venues`-Datensatz befüllt; nur `published`-Events
werden indexiert; `php artisan scout:import "App\Models\Event"` läuft
ohne Fehler.

---

### #402 Scout-Observer: Index-Synchronisation bei Status-Änderungen

**Labels:** `area:backend` · `area:search`

**AK:** `saved`- und `deleted`-Hooks auf `Event` aktualisieren den
Meilisearch-Index; bei Übergang in `published` wird der Datensatz
indexiert, bei `archived` oder `rejected` wird er aus dem Index entfernt;
Feature-Test mit Meilisearch-Fake bestätigt das Verhalten.

---

### #403 `SearchService`: Volltext- und Filter-Query

**Labels:** `area:backend` · `area:search`

**AK:** `app/Search/SearchService.php` mit Methode
`search(SearchQuery $dto): SearchResult`; unterstützt: Volltext-Query,
Kategorie-Filter, Tag-Filter, Land/Region/Stadt, Datum-Bereich,
Audience, Sprache, Preis-Range; gibt paginiertes Ergebnis-Set zurück;
Unit-Test mit Scout-Fake prüft Filterparameter.

---

### #404 `SearchService`: Radius-Geosearch via Meilisearch-`_geo`

**Labels:** `area:backend` · `area:search`

**AK:** `SearchService` akzeptiert `radius_km` (10/25/50/100/250 oder
`null` für weltweit) und `geo_coordinates` (Lat/Lng-Mittelpunkt);
Meilisearch-Geo-Filter wird korrekt gebaut; Integrationstest mit
aktivem Meilisearch-Docker-Container prüft, dass Events außerhalb des
Radius nicht zurückgegeben werden.

---

### #405 Geo-Middleware: Browser-Geolocation und manuelle Eingabe

**Labels:** `area:frontend` · `area:backend`

**AK:** Frontend kann Browser-Koordinaten per `navigator.geolocation`
ermitteln; manuelle Eingabe eines Ortsnamens ist möglich; Koordinaten werden
als Session-Wert oder Query-Parameter an `SearchService` weitergegeben;
Feature-Test prüft beide Eingabepfade. (IP-basierter Fallback via GeoLite2
wird in Sprint 5 / Epic 5 ergänzt.)

---

### #406 Such-UI (Inertia/Vue, `GET /events`)

**Labels:** `area:frontend`

**AK:** Seite enthält: Volltext-Suchfeld, Kategorien-Filter als
Checkbox-Baum, Radius-Selektor, Datum-Picker, Audience-Filter,
Sprachen-Filter; Ergebnisliste mit Event-Karten und Pagination; Pinia-Store
verwaltet den Filterstatus reaktiv; `GET /events` antwortet mit HTTP 200;
Feature-Test prüft den HTTP-Status.

---

### #407 Favoriten: `FavoriteController` und Routen

**Labels:** `area:backend`

**AK:** `POST /favorites/{event}` legt einen `favorites`-Datensatz an;
`DELETE /favorites/{event}` entfernt ihn; Unique-Constraint verhindert
Duplikate; nur Nutzer mit `role = user` oder `role = organizer` dürfen
Favoriten setzen; Feature-Test prüft Add, Remove und Duplikat-Prüfung.

---

### #408 Favoriten-Herz-Icon in Such-UI und Event-Detailseite

**Labels:** `area:frontend`

**AK:** Herz-Icon ist in der Such-Ergebnisliste und auf der
Event-Detailseite vorhanden; Toggle ruft `POST/DELETE /favorites/{event}`
auf; Zustand wird im Pinia-Store gehalten; nicht eingeloggte Nutzer
werden zur Login-Seite weitergeleitet; Feature-Test prüft den
Controller-Response.

---

## Epic 5 – Outbound Tracking & i18n (Sprint 5)

> Ziel: Outbound-Click-Tracking DSGVO-konform implementiert; UI-Lokalisierung
> DE/EN; Filament-Statistik-Dashboard; Testabdeckung auf Gesamtprojektniveau
> geprüft und Lücken geschlossen.

---

### #501 `GeoIpResolver`: GeoLite2-Lookup ohne IP-Persistierung

**Labels:** `area:backend` · `area:tracking`

**AK:** `app/Tracking/GeoIpResolver.php` kapselt GeoLite2-DB-Lookup;
gibt ISO-3166-1-Alpha-2-Ländercode oder `null` zurück; IP wird nur im
Speicher verwendet, nie persistiert; Unit-Test prüft `null`-Rückgabe
für Loopback-IP (127.0.0.1).

---

### #502 IP-basierter Standort-Fallback in der Geo-Middleware

**Labels:** `area:search` · `area:tracking`

**AK:** Die in Sprint 4 (#405) gelieferte Geo-Middleware wird um einen dritten
Fallback-Pfad erweitert: Steht weder Browser-Geolocation noch manuelle Eingabe
zur Verfügung, ermittelt `GeoIpResolver` (#501) einen ungefähren Standort aus
der Request-IP; die IP wird danach verworfen und nicht persistiert; Feature-Test
prüft den Fallback-Pfad mit einer privaten IP (erwartet: kein Standort) und
einer öffentlichen IP (erwartet: Ländercode).

---

### #503 `ClickTrackingService`: `track(Event, Request)`

**Labels:** `area:backend` · `area:tracking`

**AK:** `app/Tracking/ClickTrackingService.php` ermittelt `device_type`
via User-Agent und `country` via `GeoIpResolver`; schreibt einen
`event_clicks`-Datensatz mit `event_id`, `organizer_id`, `clicked_at`,
`country`, `device_type`, `referrer`; IP wird nicht in die Datenbank
geschrieben; Fehler im Tracking blockieren den Redirect nicht; Unit-Test
prüft Datensatz-Inhalt und Fehler-Isolation.

---

### #504 Tracking-Route `GET /go/{event:slug}` mit Rate-Limiting

**Labels:** `area:backend` · `area:tracking`

**AK:** `ClickController@redirect` ruft `ClickTrackingService::track()`
auf und antwortet mit HTTP 302 auf `event.booking_url`; funktioniert ohne
JavaScript; Laravel-Throttle-Middleware begrenzt Aufrufe je IP;
Integrationstest prüft: HTTP 302 auf korrekte URL, `event_clicks`-Datensatz
vorhanden, kein IP-Feld im Datensatz.

---

### #505 Filament-Statistik-Dashboard: Klicks je Event und Organizer

**Labels:** `area:admin` · `area:tracking`

**AK:** Eigenes Filament-Dashboard (oder Widgets in `EventResource`)
zeigt Klicks je Event für die letzten 7/30/90 Tage und Klicks je
Organizer; Admin kann auf alle Daten in `event_clicks` zugreifen;
Feature-Test prüft, dass die Dashboard-Route HTTP 200 zurückgibt.

---

### #506 Organizer-eigene Klick-Statistik im Organizer-Dashboard

**Labels:** `area:frontend` · `area:tracking`

**AK:** Seite unter `/organizer/statistics` zeigt Klicks ausschließlich
für eigene Events (gefiltert über `organizer_id` in `event_clicks`);
Aufschlüsselung nach Land und Gerätekategorie vorhanden; Feature-Test
prüft, dass ein Organizer keine Daten anderer Organizer sieht.

---

### #507 Filament-Statistik: Aufschlüsselung nach Land und Gerätetyp

**Labels:** `area:admin` · `area:tracking`

**AK:** Filament-Dashboard zeigt die Klick-Verteilung nach `country` und
`device_type` aus `event_clicks`; Zeitraumauswahl (7/30/90 Tage) ist
implementiert; Feature-Test prüft HTTP 200 für das Dashboard.

---

### #508 Laravel-Lang-Dateien für DE und EN (`resources/lang/de`, `resources/lang/en`)

**Labels:** `area:i18n`

**AK:** Alle UI-Strings sind in `resources/lang/de` und
`resources/lang/en` externalisiert; kein hartcodierter UI-Text in
Vue-Komponenten oder Blade-Views; Locale-Auswahl ist über Session und
optionalem URL-Prefix möglich; Feature-Test prüft, dass die App bei
`locale = en` englische Strings zurückgibt.

---

### #509 Inertia teilt `locale` und Übersetzungs-Strings ans Vue-Frontend

**Labels:** `area:frontend` · `area:i18n`

**AK:** `usePage().props.locale` gibt die aktive Locale zurück;
Sprachmenü in der Navigation wechselt zwischen `de` und `en`;
Übersetzungs-Strings werden über Inertia-Props ans Frontend übergeben;
Feature-Test prüft die Locale-Prop im Inertia-Response.

---

### #510 Integrations- und End-to-End-Tests: vollständiger Organizer-Event-Lifecycle

**Labels:** `area:backend`

**AK:** Ein Feature-Test deckt den vollständigen Flow von Organizer-
Registrierung → Admin-Genehmigung via `OrganizerApprovalService` →
Event-Erstellung → Einreichen via `EventPublishingService::submit()` →
Freigabe via `EventPublishingService::publish()` → öffentliche
Event-Detailseite zeigt Event ab; alle Schritte in einem Test.

---

### #511 Integrations- und End-to-End-Tests: Such-Integration mit Meilisearch

**Labels:** `area:search`

**AK:** Integrationstest mit aktivem Meilisearch-Docker-Container prüft:
`scout:import` indexiert alle `published`-Events; Volltext-Suche nach
Titel-Keyword liefert korrekte Events; Radius-Filter schließt Events
außerhalb des Radius aus.

---

### #512 Code-Qualität & Abschluss: Pint, PHPStan Level 6, TODO-Bereinigung

**Labels:** `area:devops`

**AK:** `./vendor/bin/pint --test` ist grün; PHPStan Level 6 meldet keine
Fehler; alle `TODO`/`FIXME`-Kommentare sind aufgelöst oder in Issues
überführt; `README.md` enthält Setup-Anleitung (`sail up`, `db:seed`,
`scout:import`); CI bleibt grün.

---

## Übersicht: Issues je Epic

| Epic | Sprint | Issues |
|---|---|---|
| Epic 0 – Bootstrap & Tooling | Sprint 0 | #001–#007 (7 Issues) |
| Epic 1 – Authentication & Roles | Sprint 1 | #101–#104 (4 Issues) |
| Epic 2 – Core Catalog & Admin Resources | Sprint 2 | #201–#213 (13 Issues) |
| Epic 3 – Organizer Self-Service & Event Lifecycle | Sprint 3 | #301–#312 (12 Issues) |
| Epic 4 – Search & Geosearch + Favorites | Sprint 4 | #401–#408 (8 Issues) |
| Epic 5 – Outbound Tracking & i18n | Sprint 5 | #501–#512 (12 Issues) |
| **Gesamt** | | **56 Issues** |
