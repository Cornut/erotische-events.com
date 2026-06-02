# Engineering Spec – Conscious Events Platform

Stand: 2026-06-02  
Status: Gültig für MVP (Phase 1)

---

## 1. Technischer Stack

### Backend

| Komponente | Version / Paket |
|---|---|
| PHP | 8.3 |
| Framework | Laravel 13 |
| HTTP-Layer | Inertia.js (Server-Side Adapter) |
| Admin | Filament (Version bei Bootstrap auf Laravel-13-Kompatibilität prüfen) |
| Suche | Laravel Scout + Meilisearch |
| Queue / Cache | Redis |
| Datenbank | MySQL |
| Storage | S3-kompatibler Objektspeicher |
| Geo-IP | Lokale GeoLite2-Datenbank (oder gleichwertiges Offline-DB-Format) |
| Entwicklungsumgebung | Docker / Laravel Sail |

> **Hinweis Filament-Version:** Die aktuelle Filament-Major-Version muss beim
> Projekt-Bootstrap explizit auf Laravel-13-Kompatibilität geprüft werden. Im
> Zweifel gilt: stabil > aktuell.

### Frontend

| Komponente | Technologie |
|---|---|
| Framework | Vue 3 (Composition API) |
| Typsystem | TypeScript |
| State Management | Pinia |
| Styling | TailwindCSS |
| Routing / SPA-Bridge | Inertia.js (Client-Side Adapter) |

---

## 2. Code-Struktur (Hybrid-Modell)

Standard-Laravel-Verzeichnislayout mit dünnen Controllern und einem expliziten
Service-Layer. Subsysteme werden in eigenen Namespaces gekapselt.

```
app/
├── Http/
│   └── Controllers/          # Dünn: nur Validierung, Autorisierung, Service-Aufruf
├── Models/                   # Relationen, Scopes, Accessors/Mutators – keine Workflows
├── Policies/                 # Autorisierungsregeln je Modell
├── Services/
│   ├── EventPublishingService.php
│   └── OrganizerApprovalService.php
├── Tracking/
│   ├── ClickTrackingService.php
│   └── GeoIpResolver.php     # Geo-IP-Resolver (IP wird nach Auflösung verworfen)
└── Search/
    └── SearchService.php     # Scout/Meilisearch + Geo-Query
```

### Drei Oberflächen

| Oberfläche | Technologie | Abhängigkeit |
|---|---|---|
| Öffentliche App | Inertia + Vue 3 | Kein Filament |
| Organizer-Dashboard | Inertia + Vue 3 | Kein Filament |
| Admin-Panel | Filament | Nur für Admins |

**Regel:** Öffentliche Funktionalität hängt **nie** an Filament. Filament ist
ausschließlich für Administratoren und Moderation vorgesehen.

---

## 3. Backend-Konventionen

### Controller (dünn)

Controller enthalten ausschließlich:

- **Request-Validierung** (Form Requests oder inline `validate()`)
- **Autorisierung** (Policy-Check via `$this->authorize()` oder Gate)
- **Service-Aufruf** (ein Service-Methodenaufruf, Rückgabe als Inertia-Response
  oder JSON)

**Verboten im Controller:**

- Business-Logik
- Direkte Datenbankabfragen (außer via Model-Scope als Vorbereitung für Service)
- Komplexe Datentransformationen

### Services

Business-Logik gehört ausschließlich in Services.

Definierte Services des MVP:

| Service | Namespace | Verantwortlichkeit |
|---|---|---|
| `EventPublishingService` | `App\Services` | Event-Lifecycle: einreichen, veröffentlichen, ablehnen, archivieren |
| `OrganizerApprovalService` | `App\Services` | Organizer-Verifizierung: freischalten, ablehnen, Benachrichtigungen |
| `ClickTrackingService` | `App\Tracking` | Outbound-Klick aufzeichnen, Geo-IP auflösen, Redirect vorbereiten |
| `GeoIpResolver` | `App\Tracking` | Besucherland aus IP ermitteln, IP danach sofort verwerfen (wird nie gespeichert) |
| `SearchService` | `App\Search` | Volltext- und Geo-Suche via Scout/Meilisearch |

> Diese fünf Klassen-Namen sind verbindlich. Abweichende Bezeichnungen sind nicht
> zulässig.

### Models

Models enthalten:

- Eloquent-Relationen (`hasMany`, `belongsTo`, etc.)
- Query-Scopes (z. B. `scopePublished`, `scopeByStatus`)
- Accessors und Mutators

**Verboten im Model:** Komplexe Business-Workflows, externe HTTP-Calls,
Queue-Dispatch.

### Policies

Jedes Modell mit Zugriffskontrolle erhält eine dedizierte Policy-Klasse.
Autorisierung erfolgt **immer** über Policies, nie über rohe Bedingungen im
Controller.

---

## 4. Frontend-Konventionen

### Vue-Komponenten

- Ausschließlich **Composition API** (`<script setup lang="ts">`)
- **Kein** Options API
- Zustand außerhalb von Komponenten ausschließlich via **Pinia**-Stores
- Kein globaler State außerhalb von Pinia

### Komponentenregeln

- **Single Responsibility:** Eine Komponente löst genau eine Aufgabe
- **Maximale Zeilenlänge:** 300 Zeilen (wo möglich; strukturell erzwungene
  Ausnahmen müssen kommentiert werden)
- **Wiederverwendbarkeit:** Generische UI-Elemente als eigenständige Komponenten
  extrahieren

### TypeScript

- Strikte Typisierung für Props, Emits und Store-State
- API-Response-Typen werden aus dem OpenAPI-Schema abgeleitet (Phase 2:
  automatisch generiert; MVP: manuell gepflegt) – Schema-Quelle: `docs/04-openapi.yaml`

---

## 5. API-Response-Envelope

Gilt für alle JSON-Endpunkte (z. B. AJAX-Calls, spätere REST-API).
Inertia-Responses folgen diesem Muster nicht direkt, aber Service-Rückgaben
orientieren sich daran.

**Erfolg:**

```json
{
  "success": true,
  "data": {},
  "meta": {}
}
```

**Fehler:**

```json
{
  "success": false,
  "message": "Validierung fehlgeschlagen",
  "errors": {}
}
```

> Die öffentliche REST-API (Sanctum) ist **Phase 2** und nicht im MVP enthalten.
> Das Envelope-Format wird ab Phase 2 vollständig durchgesetzt.

---

## 6. Sicherheitsregeln

**Immer:**

- Alle Eingaben validieren (server-seitig, auch wenn client-seitige Validierung
  vorhanden ist)
- Rich-Text-Inhalte (z. B. `long_description`) vor Ausgabe sanitisieren
- Datei-Uploads auf erlaubte MIME-Types und maximale Dateigröße prüfen; direkte
  Ausführung verhindern
- Autorisierung über Policies und Berechtigungen durchsetzen
- Rate Limiting auf öffentliche und schreibende Endpunkte anwenden

**Nie:**

- Client-seitiger Validierung vertrauen (nur als UX-Hilfe)
- Interne Systeminformationen (Stack-Traces, SQL-Fehler) nach außen exponieren
- Direkte Dateiausführung erlauben

**DSGVO / Tracking:**  
Beim Outbound-Tracking (`ClickTrackingService`) wird die IP-Adresse ausschließlich
zur Geo-IP-Auflösung verwendet und danach **sofort verworfen** – sie wird nie in
der Datenbank gespeichert.

---

## 7. Datenbankregeln

**Immer:**

- Foreign Keys für alle referenziellen Beziehungen definieren
- Indizes auf häufig gefilterte und sortierte Spalten setzen
- Soft Deletes (`deleted_at`) einsetzen, wo Daten auditierbar bleiben müssen
  (z. B. `users`, `organizers`, `events`)
- Schemaänderungen ausschließlich über **Migrationen** durchführen

**Nie:**

- Manuelle Schemaänderungen an der Datenbank vornehmen (kein direktes `ALTER
  TABLE` außerhalb von Migrationen)
- Migrationen überspringen oder rückwirkend ändern (stattdessen neue Migration
  erstellen)

---

## 8. Tests

### Teststrategie

| Testtyp | Wann |
|---|---|
| Feature-Tests | Für jedes Feature (HTTP-Request → Response) |
| Validierungs-Tests | Für jeden Endpunkt mit Eingabevalidierung |
| Integrationstests | Für Tracking-Redirect (`GET /go/{event}`) und Suche (Scout/Meilisearch) |
| Regressionstests | Bei jedem Bugfix – Test schlägt vor dem Fix fehl, besteht danach |

### Testvorgaben

- Jede neue Feature-Route erhält mindestens einen Feature-Test und einen
  Validierungs-Test.
- Der Outbound-Tracking-Flow (`ClickTrackingService` → `event_clicks` →
  302-Redirect) wird durch einen Integrationstest abgedeckt.
- Die Suche (Volltext + Geo-Filter) wird durch einen Integrationstest gegen
  Meilisearch oder einen dedizierten Test-Index abgedeckt.
- Bugfixes werden erst durch einen fehlschlagenden Regressionstest dokumentiert,
  dann behoben.

### Test-Umgebung

- PHPUnit / Pest (gemäß Laravel-Standard)
- Separate Test-Datenbank (SQLite in-memory oder dedizierte MySQL-Instanz)
- Meilisearch-Testindex für Suche-Integrationstests

---

## 9. Abgrenzung: Was diese Plattform nicht ist

Zur Klarstellung (vgl. Spec §1):

- **Kein** Buchungs-, Ticketing- oder Zahlungssystem
- **Kein** `BookingRequest`-Konzept (verworfen, stammt aus veralteter Archiv-Spec)
- Die Plattform verlinkt zur Event-Seite des Veranstalters; der Kauf/die Buchung
  findet extern statt
- Die öffentliche REST-API ist **Phase 2** (nicht im MVP)
