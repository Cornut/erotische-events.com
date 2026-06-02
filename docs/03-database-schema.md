# Datenbankschema

Stand: 2026-06-02 · Quelle: Spec §6 „Datenmodell"

> **Hinweis:** Die Plattform ist ein Aggregator — kein Buchungssystem. Es gibt
> keine `BookingRequest`-Tabelle. Outbound-Links werden über `event_clicks`
> getrackt; **IP-Adressen werden nie gespeichert.**

---

## Konventionen

- Primärschlüssel: `id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY` (alle Tabellen)
- Zeitstempel: `created_at TIMESTAMP` und `updated_at TIMESTAMP` (alle Tabellen,
  sofern nicht anders vermerkt)
- Soft Deletes: `deleted_at TIMESTAMP NULL` bei `users`, `organizers`, `venues`,
  `events`
- Pivot-Tabellen haben nur die zwei Fremdschlüssel als Spalten (kein `id`, keine
  Zeitstempel), sofern nicht anders angegeben
- Alle Migrationen werden code-seitig verwaltet; keine manuellen Schemaänderungen

---

## Tabellen

### `users`

| Spalte | Typ | Null | Hinweise |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK, Auto-Increment |
| `name` | VARCHAR(255) | NOT NULL | Anzeigename |
| `email` | VARCHAR(255) | NOT NULL | Unique |
| `email_verified_at` | TIMESTAMP | NULL | Laravel Standard |
| `password` | VARCHAR(255) | NOT NULL | Bcrypt-Hash |
| `remember_token` | VARCHAR(100) | NULL | Laravel Standard |
| `role` | ENUM | NOT NULL | Werte: `user` / `organizer` / `admin`; Default: `user` |
| `locale` | VARCHAR(5) | NOT NULL | Bevorzugte UI-Sprache, z. B. `de` / `en`; Default: `de` |
| `created_at` | TIMESTAMP | NULL | |
| `updated_at` | TIMESTAMP | NULL | |
| `deleted_at` | TIMESTAMP | NULL | Soft Delete |

**Indizes:** `UNIQUE(email)`

---

### `organizers`

| Spalte | Typ | Null | Hinweise |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK, Auto-Increment |
| `owner_user_id` | BIGINT UNSIGNED | NOT NULL | FK → `users.id` |
| `company_name` | VARCHAR(255) | NOT NULL | Offizieller Name des Veranstalters |
| `contact_name` | VARCHAR(255) | NOT NULL | Ansprechperson |
| `email` | VARCHAR(255) | NOT NULL | Kontakt-E-Mail |
| `phone` | VARCHAR(50) | NULL | |
| `website` | VARCHAR(255) | NULL | |
| `social_links` | JSON | NULL | z. B. `{"instagram": "…", "facebook": "…"}` |
| `description` | TEXT | NULL | Freitext-Beschreibung |
| `logo` | VARCHAR(255) | NULL | Pfad/URL zum Logo |
| `slug` | VARCHAR(255) | NOT NULL | URL-Slug, Unique |
| `verification_status` | ENUM | NOT NULL | Werte: `pending` / `approved` / `rejected`; Default: `pending` |
| `created_at` | TIMESTAMP | NULL | |
| `updated_at` | TIMESTAMP | NULL | |
| `deleted_at` | TIMESTAMP | NULL | Soft Delete |

**Fremdschlüssel:** `owner_user_id` → `users.id`

**Indizes:** `UNIQUE(slug)`, `INDEX(verification_status, slug)`

---

### `venues`

| Spalte | Typ | Null | Hinweise |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK, Auto-Increment |
| `organizer_id` | BIGINT UNSIGNED | NOT NULL | FK → `organizers.id` |
| `name` | VARCHAR(255) | NOT NULL | |
| `slug` | VARCHAR(255) | NOT NULL | URL-Slug, Unique |
| `description` | TEXT | NULL | |
| `street` | VARCHAR(255) | NULL | Straße und Hausnummer |
| `postal_code` | VARCHAR(20) | NULL | |
| `city` | VARCHAR(100) | NULL | |
| `region` | VARCHAR(100) | NULL | Bundesland / Kanton / Oblast |
| `country` | CHAR(2) | NOT NULL | ISO-3166-1-Alpha-2, z. B. `DE` |
| `latitude` | DECIMAL(10,7) | NULL | Für Geo-Suche |
| `longitude` | DECIMAL(10,7) | NULL | Für Geo-Suche |
| `images` | JSON | NULL | Array von Bild-Pfaden/-URLs |
| `contact_info` | TEXT | NULL | Freitext-Kontaktangaben |
| `created_at` | TIMESTAMP | NULL | |
| `updated_at` | TIMESTAMP | NULL | |
| `deleted_at` | TIMESTAMP | NULL | Soft Delete |

**Fremdschlüssel:** `organizer_id` → `organizers.id`

**Indizes:** `UNIQUE(slug)`, `INDEX(organizer_id)`, `INDEX(latitude, longitude)`

---

### `teachers`

| Spalte | Typ | Null | Hinweise |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK, Auto-Increment |
| `name` | VARCHAR(255) | NOT NULL | |
| `slug` | VARCHAR(255) | NOT NULL | URL-Slug, Unique |
| `bio` | TEXT | NULL | Freitext-Biografie |
| `photo` | VARCHAR(255) | NULL | Pfad/URL zum Foto |
| `links` | JSON | NULL | z. B. `{"website": "…", "instagram": "…"}` |
| `created_at` | TIMESTAMP | NULL | |
| `updated_at` | TIMESTAMP | NULL | |

**Indizes:** `UNIQUE(slug)`

*Keine Soft Deletes — Teachers sind Stammdaten ohne direkten User-Account.*

---

### `events`

| Spalte | Typ | Null | Hinweise |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK, Auto-Increment |
| `organizer_id` | BIGINT UNSIGNED | NOT NULL | FK → `organizers.id` |
| `venue_id` | BIGINT UNSIGNED | NULL | FK → `venues.id`; NULL = Online-Event |
| `title` | VARCHAR(255) | NOT NULL | |
| `slug` | VARCHAR(255) | NOT NULL | URL-Slug, Unique |
| `short_description` | VARCHAR(500) | NULL | Teaser-Text |
| `long_description` | TEXT | NULL | Volltext, HTML erlaubt |
| `main_image` | VARCHAR(255) | NULL | Pfad/URL zum Hauptbild |
| `start_date` | DATETIME | NOT NULL | |
| `end_date` | DATETIME | NULL | NULL = eintägiges Event ohne festes Ende |
| `status` | ENUM | NOT NULL | Werte: `draft` / `pending_review` / `published` / `rejected` / `archived`; Default: `draft` |
| `audience` | JSON | NULL | Array, mögliche Werte: `singles`, `couples`, `men`, `women`, `lgbtq`, `everyone` |
| `min_participants` | SMALLINT UNSIGNED | NULL | Mindest-Teilnehmerzahl |
| `max_participants` | SMALLINT UNSIGNED | NULL | Maximale Teilnehmerzahl |
| `languages` | JSON | NULL | Array von ISO-639-1-Sprachcodes, z. B. `["de","en"]` |
| `accommodation` | ENUM | NOT NULL | Werte: `none` / `optional` / `mandatory` / `external`; Default: `none` |
| `currency` | CHAR(3) | NOT NULL | ISO-4217, z. B. `EUR`; Default: `EUR` |
| `booking_url` | VARCHAR(2048) | NOT NULL | Outbound-URL beim Veranstalter (Ziel des Trackings) |
| `source_url` | VARCHAR(2048) | NULL | Ursprungs-URL, falls Event importiert wurde |
| `created_at` | TIMESTAMP | NULL | |
| `updated_at` | TIMESTAMP | NULL | |
| `deleted_at` | TIMESTAMP | NULL | Soft Delete |

**Fremdschlüssel:** `organizer_id` → `organizers.id`, `venue_id` → `venues.id`

**Indizes:** `UNIQUE(slug)`, `INDEX(organizer_id)`, `INDEX(venue_id)`,
`INDEX(status, start_date, slug)`

---

### `event_prices`

| Spalte | Typ | Null | Hinweise |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK, Auto-Increment |
| `event_id` | BIGINT UNSIGNED | NOT NULL | FK → `events.id` |
| `type` | ENUM | NOT NULL | Werte: `early_bird` / `regular` / `late_bird` |
| `amount` | DECIMAL(10,2) | NOT NULL | Betrag in der angegebenen Währung |
| `currency` | CHAR(3) | NOT NULL | ISO-4217, z. B. `EUR`; erbt typischerweise von `events.currency` |
| `valid_until` | DATE | NULL | Gültigkeitsdatum, insbesondere für `early_bird` |
| `created_at` | TIMESTAMP | NULL | |
| `updated_at` | TIMESTAMP | NULL | |

**Fremdschlüssel:** `event_id` → `events.id` (ON DELETE CASCADE)

**Indizes:** `INDEX(event_id)`

---

### `categories`

| Spalte | Typ | Null | Hinweise |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK, Auto-Increment |
| `parent_id` | BIGINT UNSIGNED | NULL | FK → `categories.id` (Selbstreferenz für Hierarchie) |
| `slug` | VARCHAR(255) | NOT NULL | URL-Slug, Unique |
| `name_de` | VARCHAR(255) | NOT NULL | Bezeichnung auf Deutsch |
| `name_en` | VARCHAR(255) | NOT NULL | Bezeichnung auf Englisch |
| `position` | SMALLINT UNSIGNED | NOT NULL | Sortierreihenfolge; Default: `0` |
| `created_at` | TIMESTAMP | NULL | |
| `updated_at` | TIMESTAMP | NULL | |

**Fremdschlüssel:** `parent_id` → `categories.id` (ON DELETE SET NULL)

**Indizes:** `UNIQUE(slug)`, `INDEX(parent_id)`

---

### `tags`

| Spalte | Typ | Null | Hinweise |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK, Auto-Increment |
| `name` | VARCHAR(100) | NOT NULL | Anzeigename |
| `slug` | VARCHAR(100) | NOT NULL | URL-Slug, Unique |
| `created_at` | TIMESTAMP | NULL | |
| `updated_at` | TIMESTAMP | NULL | |

**Indizes:** `UNIQUE(slug)`, `UNIQUE(name)`

---

### `event_category` (Pivot)

| Spalte | Typ | Null | Hinweise |
|---|---|---|---|
| `event_id` | BIGINT UNSIGNED | NOT NULL | FK → `events.id` |
| `category_id` | BIGINT UNSIGNED | NOT NULL | FK → `categories.id` |

**Fremdschlüssel:** `event_id` → `events.id` (ON DELETE CASCADE),
`category_id` → `categories.id` (ON DELETE CASCADE)

**Indizes:** `PRIMARY KEY(event_id, category_id)`

---

### `event_tag` (Pivot)

| Spalte | Typ | Null | Hinweise |
|---|---|---|---|
| `event_id` | BIGINT UNSIGNED | NOT NULL | FK → `events.id` |
| `tag_id` | BIGINT UNSIGNED | NOT NULL | FK → `tags.id` |

**Fremdschlüssel:** `event_id` → `events.id` (ON DELETE CASCADE),
`tag_id` → `tags.id` (ON DELETE CASCADE)

**Indizes:** `PRIMARY KEY(event_id, tag_id)`

---

### `event_teacher` (Pivot)

| Spalte | Typ | Null | Hinweise |
|---|---|---|---|
| `event_id` | BIGINT UNSIGNED | NOT NULL | FK → `events.id` |
| `teacher_id` | BIGINT UNSIGNED | NOT NULL | FK → `teachers.id` |

**Fremdschlüssel:** `event_id` → `events.id` (ON DELETE CASCADE),
`teacher_id` → `teachers.id` (ON DELETE CASCADE)

**Indizes:** `PRIMARY KEY(event_id, teacher_id)`

---

### `favorites`

| Spalte | Typ | Null | Hinweise |
|---|---|---|---|
| `user_id` | BIGINT UNSIGNED | NOT NULL | FK → `users.id` |
| `event_id` | BIGINT UNSIGNED | NOT NULL | FK → `events.id` |
| `created_at` | TIMESTAMP | NULL | Zeitpunkt des Speicherns |

**Fremdschlüssel:** `user_id` → `users.id` (ON DELETE CASCADE),
`event_id` → `events.id` (ON DELETE CASCADE)

**Indizes:** `UNIQUE(user_id, event_id)` — verhindert doppelte Favoriten;
dient gleichzeitig als kombinierter Abfrage-Index

---

### `event_clicks`

> **Datenschutz:** IP-Adressen werden **nie gespeichert**. Das Land wird
> einmalig per Geo-IP-Lookup (GeoLite2, lokal) ermittelt und danach die IP
> verworfen. Diese Tabelle ist DSGVO-konform.

| Spalte | Typ | Null | Hinweise |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | NOT NULL | PK, Auto-Increment |
| `event_id` | BIGINT UNSIGNED | NOT NULL | FK → `events.id` |
| `organizer_id` | BIGINT UNSIGNED | NOT NULL | FK → `organizers.id`; denormalisiert für schnelle Statistikabfragen |
| `clicked_at` | TIMESTAMP | NOT NULL | Zeitpunkt des Klicks; Default: CURRENT_TIMESTAMP |
| `country` | CHAR(2) | NULL | ISO-3166-1-Alpha-2; per Geo-IP ermittelt — **keine IP gespeichert** |
| `device_type` | ENUM | NOT NULL | Werte: `desktop` / `mobile` / `tablet` / `other` |
| `referrer` | VARCHAR(2048) | NULL | HTTP-Referer-Header (kann leer sein) |

**Fremdschlüssel:** `event_id` → `events.id`, `organizer_id` → `organizers.id`

**Indizes:** `INDEX(event_id, organizer_id, clicked_at)`

*Kein `updated_at`, kein Soft Delete — Click-Records sind immutable.*

---

## Indexübersicht (Zusammenfassung)

| Tabelle | Index | Typ |
|---|---|---|
| `users` | `email` | UNIQUE |
| `organizers` | `slug` | UNIQUE |
| `organizers` | `verification_status, slug` | INDEX |
| `venues` | `slug` | UNIQUE |
| `venues` | `organizer_id` | INDEX |
| `venues` | `latitude, longitude` | INDEX |
| `teachers` | `slug` | UNIQUE |
| `events` | `slug` | UNIQUE |
| `events` | `organizer_id` | INDEX |
| `events` | `venue_id` | INDEX |
| `events` | `status, start_date, slug` | INDEX |
| `event_prices` | `event_id` | INDEX |
| `categories` | `slug` | UNIQUE |
| `categories` | `parent_id` | INDEX |
| `tags` | `slug` | UNIQUE |
| `tags` | `name` | UNIQUE |
| `event_category` | `event_id, category_id` | PRIMARY KEY |
| `event_tag` | `event_id, tag_id` | PRIMARY KEY |
| `event_teacher` | `event_id, teacher_id` | PRIMARY KEY |
| `favorites` | `user_id, event_id` | UNIQUE |
| `event_clicks` | `event_id, organizer_id, clicked_at` | INDEX |

---

## Beziehungsübersicht

```
users ──< organizers (owner_user_id)
organizers ──< venues (organizer_id)
organizers ──< events (organizer_id)
venues ──< events (venue_id, nullable)
events ──< event_prices (event_id)
events >──< categories via event_category
events >──< tags via event_tag
events >──< teachers via event_teacher
users >──< events via favorites (user_id + event_id)
events ──< event_clicks (event_id)
organizers ──< event_clicks (organizer_id)
categories ──< categories (parent_id, Selbstreferenz)
```
