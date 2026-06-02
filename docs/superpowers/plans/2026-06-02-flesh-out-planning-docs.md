# Planning Docs (01–08) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Turn the stub files `docs/01`–`08` into complete, internally consistent planning documents for the MVP, resolving the aggregator-vs-booking and stack contradictions from `docs/archiv/`.

**Architecture:** This is a documentation deliverable, not code. Each document is an independent task. Source of truth is the approved spec `docs/superpowers/specs/2026-06-02-mvp-conscious-events-platform-design.md` (referred to below as **the Spec**) plus the detailed material in `docs/archiv/prd.md` and `docs/archiv/CLAUDE.md`. Because these are documents, each task's "verification" is a review checklist (content present, matches the Spec, no placeholders, cross-references valid) rather than an automated test.

**Tech Stack documented:** Laravel 13 / PHP 8.3, Inertia.js + Vue 3 (TS, Pinia, Tailwind), Filament, Laravel Scout + Meilisearch, MySQL, Redis, Docker/Sail.

**Conventions for every task:**
- Write all docs in German (matching the existing stubs and Spec), except `04-openapi.yaml` and `05-claude.md` which stay in their conventional form (OpenAPI is structural; CLAUDE.md may be English to match `archiv/CLAUDE.md`).
- All paths are relative to `root/` (the git repo). Commands run from `root/`.
- Resolve every contradiction in favour of the Spec: **aggregator + outbound-link tracking, NO booking system, NO `BookingRequest`.**
- After each task, run the review checklist, then commit.
- Order matters: later docs reference earlier ones. Do tasks in sequence.

---

### Task 1: PRD (`01-prd.md`)

**Files:**
- Modify: `docs/01-prd.md` (currently a stub)
- Read for source: `docs/archiv/prd.md`, the Spec

- [ ] **Step 1: Draft the PRD from the archive, scoped to the MVP**

Rewrite `docs/01-prd.md` as a full PRD. Base the structure on `docs/archiv/prd.md` but apply these changes:
- Section "Product Vision": keep the niche topic list (Tantra, Conscious Relating, Sacred Sexuality, Sex Positive, Retreats, Festivals, Workshops, Bodywork, Men's/Women's Work, LGBTQ+, Shibari, Kink). State clearly: the platform is an **aggregator / lead-generation platform** that links out to organizer event pages and **tracks those outbound clicks**.
- Add an explicit "Non-Goals" subsection copied from the Spec §1: **no booking, no ticketing, no payments, no `BookingRequest`.**
- "User Types": Guest, Registered User (favorites only in MVP), Organizer (verified, self-service), Admin. Mark Premium as Phase 2.
- "Core Features": Event Discovery, Event Details, Organizer Profiles, Venue Management, **Outbound-Link Tracking & Statistics** (replaces the old "Booking Workflow"), Search + Geosearch, Favorites.
- "MVP Scope": copy the Spec §2 "Enthalten" list verbatim as the MVP scope.
- "Out of scope": copy the Spec §2 "Nicht im MVP" list.
- "Technical Stack": use the Spec §3 stack (Laravel 13 / PHP 8.3), NOT the archive's "Laravel 12 / PHP 8.4".

- [ ] **Step 2: Review checklist**

Confirm, by re-reading the file:
- No mention of bookings/ticketing/payments as a feature (only as Non-Goals).
- Stack says Laravel 13 / PHP 8.3.
- MVP scope matches Spec §2 exactly.
- No "TBD"/"TODO"/placeholder text.

- [ ] **Step 3: Commit**

```bash
git add docs/01-prd.md
git commit -m "docs(prd): write full MVP PRD (aggregator model)"
```

---

### Task 2: Engineering Spec (`02-engineering-spec.md`)

**Files:**
- Modify: `docs/02-engineering-spec.md` (currently a stub)
- Read for source: the Spec §3, §4, §8; `docs/archiv/CLAUDE.md` (Laravel Architecture, Frontend Standards, Security, Testing sections)

- [ ] **Step 1: Draft the engineering spec**

Write `docs/02-engineering-spec.md` covering:
- **Stack** (Spec §3): Laravel 13 / PHP 8.3, Inertia + Vue 3 (Composition API, TypeScript, Pinia, Tailwind), Filament (note: verify Filament version against Laravel 13 at bootstrap), Scout + Meilisearch, MySQL, Redis, S3-compatible storage, Docker/Sail, GeoLite2 for geo-IP.
- **Code structure (Hybrid, Spec §4):** `app/Models`, thin `app/Http/Controllers`, `app/Services` (`EventPublishingService`, `OrganizerApprovalService`), `app/Tracking` (`ClickTrackingService`, geo-IP resolver), `app/Search` (`SearchService`). Three surfaces: public Inertia app, organizer dashboard (Inertia), Filament admin — public never depends on Filament.
- **Backend conventions** (from `archiv/CLAUDE.md`): thin controllers (validation/authz/service-call only, no business logic or queries), business logic in Services, models hold relations/scopes/accessors only, policies for authorization.
- **Frontend conventions:** Composition API + TypeScript + Pinia; no Options API; components single-responsibility, ≤300 lines.
- **API response/error envelope** (from `archiv/CLAUDE.md`): success `{success, data, meta}`, error `{success:false, message, errors}` — note this applies to JSON endpoints; the public REST API itself is Phase 2.
- **Security:** validate all input, sanitize rich text, protect uploads, policies + rate limiting; never trust client validation or expose internals.
- **Database rules:** always FKs, indexes, soft deletes where appropriate; migrations only.
- **Testing:** feature + validation tests per feature; integration tests for tracking redirect and search; regression tests for bugfixes.

- [ ] **Step 2: Review checklist**
- Stack matches Spec §3 (Laravel 13).
- Hybrid structure names match Spec §4 service names exactly (`ClickTrackingService`, `SearchService`, `EventPublishingService`, `OrganizerApprovalService`).
- No placeholders.

- [ ] **Step 3: Commit**

```bash
git add docs/02-engineering-spec.md
git commit -m "docs(spec): write engineering spec (stack, hybrid structure, conventions)"
```

---

### Task 3: Database Schema (`03-database-schema.md`)

**Files:**
- Modify: `docs/03-database-schema.md` (currently a stub)
- Read for source: the Spec §6

- [ ] **Step 1: Draft the field-level schema**

Write `docs/03-database-schema.md` documenting every table from Spec §6 with columns, types, nullability, enums, foreign keys, and indexes. Use the exact tables and fields from Spec §6:
`users`, `organizers`, `venues`, `teachers`, `events`, `event_prices`,
`categories`, `tags`, `event_category`, `event_tag`, `event_teacher`,
`favorites`, `event_clicks`.

For each table give a markdown table with columns: **Column | Type | Null | Notes**. Spell out:
- Enums: `users.role`(user/organizer/admin), `organizers.verification_status`(pending/approved/rejected), `events.status`(draft/pending_review/published/rejected/archived), `events.accommodation`(none/optional/mandatory/external), `event_prices.type`(early_bird/regular/late_bird), `event_clicks.device_type`(desktop/mobile/tablet/other).
- `events.audience` and `events.languages` as JSON.
- **`event_clicks` stores NO IP** — only `country` (char2, nullable) and `device_type`.
- `categories` has `name_de`, `name_en`, nullable `parent_id` (self-reference), `position`.
- Foreign keys for every relation; unique composite on `favorites(user_id, event_id)`.
- Indexes from Spec §6: `events(status, start_date, slug)`, `event_clicks(event_id, organizer_id, clicked_at)`, `organizers(verification_status, slug)`.
- Note soft deletes on `users`, `organizers`, `venues`, `events`.

- [ ] **Step 2: Review checklist**
- All 13 tables from Spec §6 present.
- Every enum lists the exact values above.
- `event_clicks` explicitly says no IP stored.
- Every FK and the listed indexes documented.
- No placeholders.

- [ ] **Step 3: Commit**

```bash
git add docs/03-database-schema.md
git commit -m "docs(schema): write field-level DB schema with indexes and FKs"
```

---

### Task 4: Category Taxonomy (`08-category-taxonomy.md`)

**Files:**
- Modify: `docs/08-category-taxonomy.md` (currently a stub: Tantra, Sex Positive, Conscious Relating, Festival, Retreat, Workshop)
- Read for source: PRD topic list (Task 1 / `archiv/prd.md`), Spec §6 `categories`

(Done before OpenAPI/roadmap because those reference category slugs.)

- [ ] **Step 1: Define the taxonomy**

Write `docs/08-category-taxonomy.md` defining the fixed category set as a tree. Top-level categories (with `slug`, `name_de`, `name_en`) derived from the PRD topics, e.g.:
- Tantra (`tantra`)
- Conscious Relating (`conscious-relating`)
- Sacred Sexuality (`sacred-sexuality`)
- Sex Positive (`sex-positive`)
- Retreat (`retreat`)
- Festival (`festival`)
- Workshop (`workshop`)
- Bodywork (`bodywork`)
- Men's Work (`mens-work`)
- Women's Work (`womens-work`)
- LGBTQ+ (`lgbtq`)
- Shibari (`shibari`)
- Kink (`kink`)

Present as a table: **slug | name_de | name_en | parent**. Most are top-level (parent = none); document at least one example of a sub-category to demonstrate the `parent_id` hierarchy (e.g. `shibari` and `kink` as children of a `bdsm` parent — decide and state it explicitly). Add a short "Tags" section explaining tags are free-form (unlike fixed categories) and seeded examples (e.g. `beginner-friendly`, `couples`, `clothing-optional`).

- [ ] **Step 2: Review checklist**
- Every top-level topic from the PRD has a slug + DE/EN name.
- Hierarchy via `parent` column is shown with at least one concrete parent/child.
- Slugs are kebab-case and stable.
- No placeholders.

- [ ] **Step 3: Commit**

```bash
git add docs/08-category-taxonomy.md
git commit -m "docs(taxonomy): define category tree and tag conventions"
```

---

### Task 5: OpenAPI (`04-openapi.yaml`)

**Files:**
- Modify: `docs/04-openapi.yaml` (currently a stub with `/events` only)
- Read for source: Spec §6 (schemas), §7 (tracking flow), `archiv/CLAUDE.md` (response envelope)

- [ ] **Step 1: Draft the MVP-scoped OpenAPI document**

Rewrite `docs/04-openapi.yaml` (OpenAPI 3.1) scoped to what exists in the MVP. The public REST API is Phase 2, so add a top-level `description` stating: "MVP surface only; full public REST API is Phase 2." Document:
- `GET /go/{event}` — the outbound tracking redirect. Response `302` with `Location` header to the organizer URL; describe that the click is recorded server-side. Path param `event` (slug or id).
- Read-only JSON endpoints that the Inertia frontend / future API share, if any are exposed: `GET /api/events` (list, with query filters: country, region, city, date, category, organizer, language, audience, price, geo radius), `GET /api/events/{slug}`, `GET /api/organizers/{slug}`, `GET /api/categories`. Mark these as the read surface; write operations go through Inertia/Filament, not the API, in the MVP.
- `components/schemas`: `Event`, `Organizer`, `Venue`, `Teacher`, `Category`, `Tag`, `EventPrice` — fields mirroring Spec §6.
- Standard envelopes from `archiv/CLAUDE.md`: success `{success, data, meta}` and error `{success:false, message, errors}` as reusable schemas.

- [ ] **Step 2: Validate the YAML structurally**

Run: `npx --yes @redocly/cli@latest lint docs/04-openapi.yaml`
Expected: parses as valid OpenAPI 3.1 (warnings acceptable, no fatal parse/schema errors). If `npx` is unavailable, at minimum verify it is valid YAML: `php -r "var_dump(yaml_parse_file('docs/04-openapi.yaml'));"` or a YAML linter.

- [ ] **Step 3: Review checklist**
- `/go/{event}` documented as a 302 redirect.
- Schemas match Spec §6 field names.
- Public REST API marked as Phase 2.
- No placeholders.

- [ ] **Step 4: Commit**

```bash
git add docs/04-openapi.yaml
git commit -m "docs(openapi): document MVP API surface (tracking redirect + read endpoints)"
```

---

### Task 6: Project CLAUDE.md (`05-claude.md`)

**Files:**
- Modify: `docs/05-claude.md` (currently a stub)
- Read for source: `docs/archiv/CLAUDE.md`, the Spec

- [ ] **Step 1: Rewrite the operating CLAUDE.md for the aggregator model**

Write `docs/05-claude.md` based on `docs/archiv/CLAUDE.md` but corrected:
- "Project Overview": aggregator / lead-generation / outbound-link-tracking platform. **Remove** the "booking request platform" framing and the entire `BookingRequest` domain model and "Booking Workflow" section.
- Replace the booking workflow with an **Outbound-Link Tracking** section describing the `/go/{event}` redirect and `event_clicks` recording (per Spec §7).
- Keep and keep-accurate: User Roles (Guest/Organizer/Admin + add Registered User with favorites), Event Lifecycle (draft→pending_review→published/rejected/archived), Organizer Verification, Sponsored Listings (note: Phase 2 — visibility only, never changes data).
- Keep: API standards, Laravel architecture (thin controllers / services / models), frontend standards, Filament scope, security rules, database rules, testing requirements, documentation requirements, Claude operating procedure.
- Update the Domain Model section to the Spec §6 entities; delete `BookingRequest`.

- [ ] **Step 2: Review checklist**
- No `BookingRequest`, no "booking platform" language anywhere.
- Outbound-tracking section present.
- Roles include Registered User (favorites).
- Domain model matches Spec §6.
- No placeholders.

- [ ] **Step 3: Commit**

```bash
git add docs/05-claude.md
git commit -m "docs(claude): rewrite operating guide for aggregator + tracking model"
```

---

### Task 7: Implementation Roadmap (`07-implementation-roadmap.md`)

**Files:**
- Modify: `docs/07-implementation-roadmap.md` (currently a stub: "Sprint 1-5")
- Read for source: the whole Spec, especially §2, §4, §6, §7

- [ ] **Step 1: Write the MVP build roadmap**

Write `docs/07-implementation-roadmap.md` sequencing the *software* MVP (the next iteration after these docs). Define sprints with goals and dependencies. Suggested sequence (state dependencies between them):
1. **Sprint 0 – Bootstrap:** Sail/Docker up, verify Laravel 13 + Filament compatibility (pin versions), install Inertia + Vue 3 + TS + Pinia + Tailwind, Scout + Meilisearch, Pint, Pest/PHPUnit. Healthcheck route + CI.
2. **Sprint 1 – Auth & Roles:** users + role enum, registration/login (Inertia), Filament admin login, policies scaffold.
3. **Sprint 2 – Core Catalog:** migrations + models for organizers, venues, teachers, categories, tags, events, event_prices and pivots (per `03-database-schema.md`); Filament resources for admin curation; seeders for taxonomy (per `08`).
4. **Sprint 3 – Organizer Self-Service + Lifecycle:** organizer registration + verification flow (`OrganizerApprovalService`), organizer dashboard (Inertia) to manage venues/events, event lifecycle (`EventPublishingService`), public event/organizer pages.
5. **Sprint 4 – Search & Geosearch:** Scout + Meilisearch indexing of events, `SearchService`, filters + radius geosearch, public search UI; favorites for registered users.
6. **Sprint 5 – Outbound Tracking + i18n + polish:** `/go/{event}` redirect, `ClickTrackingService`, geo-IP (GeoLite2), `event_clicks`, Filament stats dashboard; DE/EN UI localization; test/coverage pass.

For each sprint list the concrete deliverables and the acceptance criterion ("done when ...").

- [ ] **Step 2: Review checklist**
- Every MVP scope item from Spec §2 maps to a sprint.
- Service names match Spec §4.
- Dependencies between sprints stated (e.g. search depends on catalog).
- No placeholders.

- [ ] **Step 3: Commit**

```bash
git add docs/07-implementation-roadmap.md
git commit -m "docs(roadmap): sequence MVP build into sprints 0-5"
```

---

### Task 8: GitHub Issues (`06-github-issues.md`)

**Files:**
- Modify: `docs/06-github-issues.md` (currently a stub: Epic 1-3)
- Read for source: `07-implementation-roadmap.md` (Task 7), the Spec

- [ ] **Step 1: Write epics and issues mapped to the roadmap**

Write `docs/06-github-issues.md` as a backlog. One epic per sprint from Task 7. Under each epic, list concrete issues with a title and a one-line acceptance criterion and labels (e.g. `area:backend`, `area:frontend`, `area:admin`, `area:search`, `area:tracking`). Example epics:
- Epic 0: Bootstrap & Tooling
- Epic 1: Authentication & Roles
- Epic 2: Core Catalog & Admin Resources
- Epic 3: Organizer Self-Service & Event Lifecycle
- Epic 4: Search & Geosearch + Favorites
- Epic 5: Outbound Tracking & i18n

Each issue must be small enough to be one PR (e.g. "Migration + model for `events`", "Filament resource for Organizers with verify action", "`/go/{event}` redirect + `ClickTrackingService`", "Meilisearch geo filter in `SearchService`").

- [ ] **Step 2: Review checklist**
- Epics align 1:1 with the roadmap sprints.
- Every issue references entities/services that exist in the Spec/schema (no invented names).
- Issues are PR-sized.
- No placeholders.

- [ ] **Step 3: Commit**

```bash
git add docs/06-github-issues.md
git commit -m "docs(issues): backlog of epics and PR-sized issues for the MVP"
```

---

## Final Self-Review (run after all 8 tasks)

- [ ] **Cross-document consistency:** stack (Laravel 13 / PHP 8.3) identical in `01`, `02`, `07`. Service names (`ClickTrackingService`, `SearchService`, `EventPublishingService`, `OrganizerApprovalService`) identical in `02`, `05`, `07`. Table/enum names identical in `03`, `04`, `05`.
- [ ] **No booking anywhere:** grep the docs for "booking request", "BookingRequest", "ticketing", "payment" — only allowed as Non-Goals. Run: `grep -rin "bookingrequest\|booking request\|ticketing" docs/0*.md docs/04-openapi.yaml`
- [ ] **No placeholders:** `grep -rin "TBD\|TODO\|FIXME\|placeholder" docs/0*.md docs/04-openapi.yaml` returns nothing.
- [ ] **Category slugs** used in `04`/`06`/`07` exist in `08`.
