# PRD – Conscious Events Platform (erotische-events.com)

Version: 2.0 (MVP-Fokus, Aggregator-Modell)
Datum: 2026-06-02
Status: Freigegeben

---

## 1. Produktvision

Die Conscious Events Platform ist eine aggregierte Event-Discovery-Plattform für folgende Nischen:

- Tantra
- Conscious Relating
- Sacred Sexuality
- Sex Positive Communities
- Retreats
- Festivals
- Workshops
- Bodywork
- Men's Work / Women's Work
- LGBTQ+
- Shibari
- Kink

**Die Plattform ist ein Aggregator und eine Lead-Generation-Plattform.** Sie sammelt Event-Informationen von Veranstaltern und stellt sie Nutzern über eine zentrale Suche und Discovery-Oberfläche zur Verfügung. Alle Event-Links führen zur Seite des jeweiligen Veranstalters; diese ausgehenden Links werden getrackt (Statistik: wie oft → welcher Veranstalter → welches Event).

Langfristiges Ziel: Die größte globale Datenbank und Suchmaschine für Conscious-, Tantra- und Sex-Positive-Events werden.

### 1.1 Non-Goals (Abgrenzung)

Die Plattform ist ausdrücklich **kein** Buchungs-, Ticketing- oder Zahlungssystem:

- Kein On-Platform-Buchungssystem
- Kein `BookingRequest`-Konzept
- Kein Ticketverkauf
- Keine Zahlungsabwicklung
- Kein Ersatz für die Veranstalter-Website

---

## 2. Business-Ziele

### Primärziele

- Größte Event-Datenbank in dieser Nische aufbauen
- Organischen Traffic über SEO gewinnen
- Lead-Generation für Veranstalter durch getrackte Outbound-Links
- Wiederkehrende Erlöse durch Premium-Mitgliedschaften (Phase 2)
- Werbeeinnahmen durch Sponsored Listings (Phase 2)

### Erfolgskennzahlen (Jahr 1)

- 10.000 indexierte Events
- 1.000 Veranstalter
- 25.000 registrierte Nutzer
- 5.000 Newsletter-Abonnenten

### Erfolgskennzahlen (Jahr 2)

- 100.000 indexierte Events
- 10.000 Veranstalter
- 250.000 Nutzer

---

## 3. Nutzertypen

### 3.1 Gast

Ziele:
- Events entdecken
- Suche nach Ort und Kategorie
- Veranstalter erkunden

Kann:
- Events suchen und filtern
- Event-Details ansehen
- Organizer-Profile ansehen
- Ausgehende Event-Links (Tracking-Redirect) nutzen

### 3.2 Registrierter Nutzer

Zusätzliche Funktionen (MVP):
- Favoriten speichern

Zusätzliche Funktionen (Phase 2):
- Bewertungen schreiben
- Newsletter abonnieren
- Benachrichtigungen erhalten
- Personalisierte Empfehlungen

### 3.3 Organizer (verifiziert)

Ziele:
- Events bekannt machen
- Event-Portfolio verwalten
- Sichtbarkeit erhöhen

Kann:
- Organizer-Profil verwalten
- Venues verwalten
- Events anlegen, bearbeiten und einreichen
- Event-Lifecycle steuern (Draft → Einreichen → Veröffentlicht)
- Eigene Outbound-Link-Statistiken einsehen

Phase 2:
- Sponsored Placements buchen

### 3.4 Admin

Zuständig für:
- Nutzerverwaltung
- Organizer-Verifizierung und -Moderation
- Event-Moderation
- Kategorien- und Tag-Verwaltung
- System-Konfiguration
- Statistik-Übersicht

### 3.5 Premium-Mitglied (Phase 2)

- Personalisierte Empfehlungen
- Erweiterte Suchfilter
- Location- und Kategorie-Alerts
- Watchlists

---

## 4. Kern-Features

### 4.1 Event Discovery

Nutzer können:
- Events suchen (Volltext)
- Events filtern (Land, Region, Stadt, Datum, Kategorie, Organizer, Sprache, Audience, Preis)
- Kategorien durchstöbern
- Events in der Nähe entdecken (Geosearch)

Suchbare Felder:
- Event-Titel, Beschreibung
- Organizer-Name
- Teacher-Name
- Venue-Name, Ort
- Kategorien, Tags

### 4.2 Event-Details

Eine Event-Seite enthält:
- Titel, Kurzbeschreibung, ausführliche Beschreibung
- Hauptbild
- Organizer, Venue, Teachers
- Datum (Start/Ende)
- Preisstruktur (Early Bird / Regular / Late Bird)
- Sprachen
- Zielgruppe (Audience)
- Unterkunft
- Getrackte Outbound-Link-Schaltfläche → Weiterleitung zur Veranstalter-Seite

### 4.3 Organizer-Profile

Jeder Organizer hat:
- Öffentliches Profil (Name, Beschreibung, Logo)
- Kontaktinformationen und Website
- Social Links
- Öffentliche Event-Liste

### 4.4 Venue-Verwaltung

Organizer können mehrere Venues verwalten.

Ein Venue enthält:
- Name, Beschreibung
- Adresse (Straße, PLZ, Stadt, Region, Land)
- Koordinaten (Latitude/Longitude)
- Bilder
- Kontaktinformationen

### 4.5 Outbound-Link-Tracking & Statistiken

**Kernfeature der Plattform.**

- Jeder Klick auf „Zum Event" läuft über `GET /go/{event}` (Tracking-Redirect)
- `ClickTrackingService` ermittelt Land via Geo-IP (IP wird danach **nicht gespeichert**, DSGVO-konform) und Gerätetyp via User-Agent
- Eintrag in `event_clicks` (Event, Organizer, Zeitstempel, Land, Gerätetyp, Referrer)
- Anschließend 302-Redirect auf `event.booking_url` beim Veranstalter
- Funktioniert ohne JavaScript
- Statistiken je Event und Organizer im Dashboard

### 4.6 Suche & Geosearch

- Volltext- und Filtersuche über Laravel Scout + Meilisearch
- Geo-Suche mit Radius-Filtern: 10 / 25 / 50 / 100 / 250 km / weltweit
- Standortquellen: Browser-Geolocation, manuelle Eingabe, IP-Fallback
- Filter: Land, Region, Stadt, Datum, Kategorie, Organizer, Sprache, Audience, Preis

### 4.7 Favoriten

Registrierte Nutzer können Events als Favorit markieren und ihre Favoritenliste einsehen.

---

## 5. Event-Datenmodell

### Kerndaten

- Titel, Slug
- Kurzbeschreibung, ausführliche Beschreibung
- Hauptbild

### Zeitplanung

- Startdatum, Enddatum

### Relationen

- Organizer (Pflicht)
- Venue (optional)
- Teachers (n:m)

### Zielgruppe (Audience)

- Singles, Paare, Männer, Frauen, LGBTQ+, Alle

### Kapazität

- Mindest- und Höchstteilnehmerzahl (optional)

### Sprachen

- Deutsch, Englisch, Spanisch, Französisch, weitere

### Unterkunft

- Keine / Optional / Inklusive / Extern

### Preisstruktur

- Early Bird, Regular, Late Bird
- Betrag, Währung, Gültig-bis (optional)

### Status-Lifecycle

```
draft → pending_review → published / rejected
                                ↓
                            archived
```

Nur `published`-Events sind öffentlich sichtbar.

### Metadaten

- Kategorien (hierarchisch), Tags
- `booking_url` (Outbound-Link zum Veranstalter)
- `source_url` (optional, für zukünftige Scraper-Importe)

---

## 6. MVP-Umfang

### 6.1 Enthalten im MVP

- **Core-Katalog**: Events, Organizer, Venues, Teachers, Kategorien, Tags, Preise
- **Auth & Rollen**: Gast, registrierter Nutzer, Organizer, Admin
- **Organizer-Self-Service**: Verifizierung + vollständiger Event-Lifecycle
- **Admin-Pflege** über Filament (Admin und Organizer-Self-Service parallel)
- **Outbound-Link-Tracking** mit Geo/Device (DSGVO-konform, ohne IP-Speicherung)
- **Favoriten** für registrierte End-Nutzer
- **Volltext-/Filter-Suche + Geosearch** (Meilisearch)
- **UI-Lokalisierung** DE/EN

### 6.2 Nicht im MVP

Folgende Features werden jeweils in einer eigenen späteren Spec spezifiziert:

- Scraper-/Import-Plattform
- KI (Auto-Kategorisierung, Tag-Generierung, Übersetzung)
- Reviews und Bewertungen
- Newsletter
- Premium-Mitgliedschaften und Alerts
- Sponsored Listings
- Öffentliche REST-API (Laravel Sanctum)
- Native Mobile App
- Automatische Inhalts-Übersetzung
- PWA

---

## 7. Mehrsprachigkeit

Phase 1 (MVP):
- Deutsch und Englisch
- Vollständig lokalisierte Benutzeroberfläche (Laravel Lang-Dateien)
- Locale über Session/Route; Inertia teilt Locale ans Frontend
- Event-Inhalte in Eingabesprache (keine Auto-Übersetzung im MVP)

Phase 2:
- Automatische Übersetzung (OpenAI oder DeepL)

---

## 8. Technischer Stack

| Bereich | Technologie |
|---|---|
| Backend | Laravel 13 / PHP 8.3 |
| Frontend | Inertia.js + Vue 3 (Composition API), TypeScript, Pinia, TailwindCSS |
| Admin | Filament (Laravel-13-kompatible Version) |
| Suche | Laravel Scout + Meilisearch |
| Datenbank | MySQL |
| Cache / Queue | Redis |
| Storage | S3-kompatibler Storage |
| Geo-IP | Lokale GeoLite2-DB (IP wird nicht gespeichert) |
| Entwicklung | Docker / Laravel Sail |

Drei klar getrennte Oberflächen:
1. Öffentliche Inertia/Vue-App
2. Organizer-Dashboard (Inertia)
3. Filament-Admin

**Öffentliche Funktionalität hängt nie an Filament.**

---

## 9. Sicherheit und Datenschutz

- Alle Eingaben werden serverseitig validiert
- Rich-Text-Inhalte werden sanitisiert
- Upload-Schutz (nur erlaubte Dateitypen)
- Policies und Permissions je Modell
- Rate Limiting auf kritischen Endpunkten
- **DSGVO**: Bei Outbound-Tracking wird die IP-Adresse nach der Geo-IP-Ermittlung sofort verworfen und nicht gespeichert

---

## 10. Teststrategie

- Feature-Tests und Validierungstests je Feature
- Integrationstests für Tracking-Redirect und Suche
- Regressionstests bei Bugfixes

---

## 11. Offene Punkte (Phase 2)

- Scraper-Architektur und Import-Pipeline
- KI-Provider (OpenAI / DeepL) und Kategorisierungs-Pipeline
- Reviews-Moderation
- Newsletter-Provider
- Premium-Billing und Alerts
- Sponsored-Placement-Logik
- Öffentlicher API-Vertrag (Sanctum)
