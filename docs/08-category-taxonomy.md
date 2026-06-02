# Kategorie-Taxonomie

Version: 1.0  
Datum: 2026-06-02  
Status: Freigegeben

---

## 1. Überblick

Die Plattform verwendet ein **festes, zweistufiges Kategoriensystem** (top-level +
optionale Unter-Kategorien). Kategorien werden ausschließlich von Admins verwaltet;
Organizer wählen beim Event-Anlegen aus dieser festen Liste.

Das Datenbankschema der Tabelle `categories` lautet:

```
categories
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  slug          VARCHAR(64)  UNIQUE NOT NULL   -- stabiler Bezeichner (kebab-case)
  name_de       VARCHAR(128) NOT NULL          -- deutsche Bezeichnung
  name_en       VARCHAR(128) NOT NULL          -- englische Bezeichnung
  parent_id     BIGINT UNSIGNED NULL           -- FK → categories.id (NULL = Top-Level)
  position      TINYINT UNSIGNED NOT NULL      -- Sortierreihenfolge innerhalb der Ebene
```

Slugs sind **stabil** (nie umbenennen, sobald produktiv gesetzt) und
**kebab-case** (nur Kleinbuchstaben, Ziffern, Bindestriche).

---

## 2. Kategorienliste

### 2.1 Top-Level-Kategorien

| Position | slug | name_de | name_en | parent |
|---:|---|---|---|---|
| 1 | `tantra` | Tantra | Tantra | (keine) |
| 2 | `conscious-relating` | Conscious Relating | Conscious Relating | (keine) |
| 3 | `sacred-sexuality` | Sakrale Sexualität | Sacred Sexuality | (keine) |
| 4 | `sex-positive` | Sex Positive | Sex Positive | (keine) |
| 5 | `retreat` | Retreat | Retreat | (keine) |
| 6 | `festival` | Festival | Festival | (keine) |
| 7 | `workshop` | Workshop | Workshop | (keine) |
| 8 | `bodywork` | Körperarbeit | Bodywork | (keine) |
| 9 | `mens-work` | Männerarbeit | Men's Work | (keine) |
| 10 | `womens-work` | Frauenarbeit | Women's Work | (keine) |
| 11 | `lgbtq` | LGBTQ+ | LGBTQ+ | (keine) |
| 12 | `bdsm` | BDSM | BDSM | (keine) |

### 2.2 Unter-Kategorien

Die Kategorie **`bdsm`** dient als Elternkategorie für verwandte Nischen:

| Position | slug | name_de | name_en | parent |
|---:|---|---|---|---|
| 1 | `shibari` | Shibari | Shibari | `bdsm` |
| 2 | `kink` | Kink | Kink | `bdsm` |

`shibari` und `kink` sind also **Kinder von `bdsm`** (`parent_id` zeigt auf die ID
des `bdsm`-Eintrags). Alle übrigen oben genannten Kategorien sind Top-Level-Einträge
mit `parent_id = NULL`.

### 2.3 Vollständiger Baum (Übersicht)

```
tantra
conscious-relating
sacred-sexuality
sex-positive
retreat
festival
workshop
bodywork
mens-work
womens-work
lgbtq
bdsm
  └── shibari
  └── kink
```

---

## 3. Hinweise zur Verwendung

- **Mehrfachzuordnung**: Ein Event kann mehreren Kategorien zugeordnet werden
  (Pivot-Tabelle `event_category`).
- **Vererbung**: Unter-Kategorien sind eigenständige Einträge; eine Zuordnung zu
  `shibari` impliziert **keine** automatische Zuordnung zu `bdsm`. Filter-Logik
  kann bei Bedarf alle Kinder einer Elternkategorie einschließen.
- **Erweiterbarkeit**: Neue Kategorien werden via Admin-Panel und Datenbank-Migration
  hinzugefügt. Slugs bestehender Einträge dürfen nach dem Produktivgang nicht
  verändert werden (SEO, URL-Stabilität, Meilisearch-Indizes).
- **`position`**: Bestimmt die Anzeigereihenfolge in Filtern und der
  Navigationsleiste, jeweils innerhalb einer Ebene (top-level vs. Kinder einer
  Elternkategorie).

---

## 4. Tags

Tags sind **frei wählbar** und ergänzen das feste Kategoriensystem. Im Gegensatz
zu Kategorien können Organizer beliebige Tags vergeben; Admins können die
Tag-Liste bereinigen. Tags werden in der Tabelle `tags` (Felder: `id`, `name`,
`slug`) gespeichert; die Verknüpfung erfolgt über `event_tag`.

### 4.1 Vorseeded Tags (Auswahl)

Folgende Tags werden beim Seed mit angelegt, um eine konsistente Ausgangsbasis zu
schaffen:

| slug | name |
|---|---|
| `beginner-friendly` | Anfängerfreundlich |
| `couples` | Für Paare |
| `clothing-optional` | Clothing Optional |
| `english-spoken` | Englisch gesprochen |
| `german-spoken` | Deutsch gesprochen |
| `women-only` | Nur für Frauen |
| `men-only` | Nur für Männer |
| `lgbtq-friendly` | LGBTQ+-freundlich |
| `outdoor` | Outdoor |
| `residential` | Residential (Übernachtung) |
| `day-event` | Tagesveranstaltung |
| `online` | Online |
| `somatic` | Somatisch |
| `trauma-informed` | Trauma-informiert |
| `consent-focused` | Consent-fokussiert |

Diese Liste ist nicht abschließend. Neue Tags können jederzeit von Organizern
angelegt oder von Admins ergänzt werden.

---

## 5. Seed-Reihenfolge

Bei der Datenbankbefüllung muss `bdsm` vor `shibari` und `kink` angelegt werden,
damit die Fremdschlüssel-Referenz (`parent_id`) aufgelöst werden kann.

Empfohlene Reihenfolge im Seeder: alle Top-Level-Einträge zuerst (in
`position`-Reihenfolge), danach die Unter-Kategorien.
