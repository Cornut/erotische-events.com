# CLAUDE.md

## Project Overview

This project is an **event aggregator and lead-generation platform** for Tantra,
Sex-Positive, Conscious-Relating, Retreat, Festival, and Workshop events.

The platform links visitors to the event organizer's own booking page and tracks
every outbound click (statistics: how often → which organizer → which event).

The platform acts as:

* Event discovery directory
* Lead-generation platform
* Outbound-link-tracking platform
* Administration and moderation platform

The platform is NOT:

* A payment provider
* A ticketing system
* A booking / reservation system
* A hotel booking system

There is **no** `BookingRequest` concept. Reservations, contracts, invoicing, and
payments are handled entirely by the organizer on their own platform. The
`BookingRequest` model from the legacy archive (`docs/archiv/CLAUDE.md`) has been
permanently removed.

---

# Core Business Rules

## User Roles

### Guest

Can:

* Browse and search events
* View organizer and venue profiles
* Follow outbound links (tracked redirect)

Cannot:

* Save favorites
* Create events
* Moderate content

---

### Registered User

Can:

* Everything a Guest can
* Save and manage favorite events

Cannot:

* Create events
* Moderate content

---

### Organizer (verified)

Can:

* Create and edit own events
* Manage own profile, venues, and teachers
* View own outbound-click statistics
* Upload event images

Cannot:

* Approve own moderation requests
* Access administration functions

---

### Admin

Can:

* Review submitted events
* Approve or reject events
* Manage and verify organizers
* Manage users, categories, tags, teachers
* View platform-wide click statistics
* Manage sponsored placements (Phase 2)
* Manage system settings

---

## Event Lifecycle

Event status:

```text
draft
pending_review
published
rejected
archived
```

Rules:

* New events start as `draft`.
* The organizer submits the event, moving it to `pending_review`.
* Admin approval changes status to `published`.
* Admin rejection changes status to `rejected`.
* Only `published` events are publicly visible.
* `archived` is used for events that have ended or been retired.

---

## Outbound-Link Tracking

This is the core tracking feature of the platform.

Flow:

```text
Visitor clicks "Go to Event" / "Zum Veranstalter"
   ↓
GET /go/{event}   (no JavaScript required)
   ↓
ClickTrackingService:
  - resolves country via GeoLite2 local DB (IP is discarded immediately after lookup)
  - resolves device_type via User-Agent header
  - writes one row to event_clicks (event_id, organizer_id, clicked_at, country, device_type, referrer)
   ↓
302 Redirect → event.booking_url (organizer's own page)
```

`event_clicks` fields:

| Field | Notes |
|---|---|
| `event_id` | FK to events |
| `organizer_id` | Denormalised FK for fast statistics queries |
| `clicked_at` | Timestamp of the click |
| `country` | ISO-3166-1 alpha-2 code derived from Geo-IP; **IP is never stored** |
| `device_type` | `desktop` / `mobile` / `tablet` / `other` |
| `referrer` | HTTP Referer header (may be empty) |

GDPR / DSGVO compliance: no IP address is stored at any point. Statistics are
visible per event and per organizer in the Filament Admin dashboard.

---

## Organizer Verification

Organizers can self-register freely.

After registration:

* Organizer account requires admin approval.
* Admin receives notification.
* Admin can approve or reject the organizer.

Only `approved` organizers may submit or publish events.

Organizer verification statuses:

```text
pending
approved
rejected
```

---

## Sponsored Listings

**Phase 2 feature — not in MVP.**

Rules (when implemented):

* Sponsored placement never changes event data.
* Sponsored placement only affects visibility / display position.
* Sponsored events must remain clearly identifiable as sponsored.

---

# Domain Model

All tables use foreign keys, indexes, and soft deletes where appropriate.
All schema changes are managed via Laravel migrations — no manual schema changes.

## Core Entities

| Table | Key Fields |
|---|---|
| `users` | `role` enum(user/organizer/admin), `locale`, auth fields, soft deletes |
| `organizers` | `owner_user_id`→users, `company_name`, `contact_name`, `email`, `phone`, `website`, `social_links`(json), `description`, `logo`, `slug`, `verification_status` enum(pending/approved/rejected), soft deletes |
| `venues` | `organizer_id`, `name`, `slug`, `description`, address fields, `latitude`, `longitude`, `images`(json), `contact_info`, soft deletes |
| `teachers` | `name`, `slug`, `bio`, `photo`, `links`(json) — n:m to events |
| `events` | `organizer_id`, `venue_id`?, `title`, `slug`, `short_description`, `long_description`, `main_image`, `start_date`, `end_date`, `status` enum(draft/pending_review/published/rejected/archived), `audience`(json), `min_participants`?, `max_participants`?, `languages`(json), `accommodation` enum, `currency`, `booking_url` (outbound target), `source_url`?, soft deletes |
| `event_prices` | `event_id`, `type` enum(early_bird/regular/late_bird), `amount`, `currency`, `valid_until`? |
| `categories` | `parent_id`? (hierarchy), `slug`, `name_de`, `name_en`, `position` |
| `tags` | `name`, `slug` |
| `event_category` | pivot: `event_id`, `category_id` |
| `event_tag` | pivot: `event_id`, `tag_id` |
| `event_teacher` | pivot: `event_id`, `teacher_id` |
| `favorites` | `user_id`, `event_id` (unique composite) — registered users only |
| `event_clicks` | `event_id`, `organizer_id`, `clicked_at`, `country`(char2)?, `device_type` enum, `referrer`? — **no IP stored** |

See `docs/03-database-schema.md` for complete field-level definitions, types,
indexes, and foreign-key constraints.

### What is NOT in the domain model

* `BookingRequest` — does not exist. The platform is an aggregator, not a booking system.

---

# API Standards

All APIs must follow OpenAPI 3.1 (see `docs/04-openapi.yaml`).

Every endpoint must:

* Be documented
* Define request schema
* Define response schema
* Define error responses

Response envelope:

```json
{
  "success": true,
  "data": {},
  "meta": {}
}
```

Error envelope:

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {}
}
```

---

# Laravel Architecture

## Controllers

Controllers must remain thin.

Allowed:

* Request validation
* Authorization
* Service invocation

Forbidden:

* Business logic
* Database queries
* Complex transformations

---

## Services

Business logic belongs in Services.

Named services for this project:

| Service | Location | Responsibility |
|---|---|---|
| `ClickTrackingService` | `app/Tracking` | Geo-IP lookup, device detection, write `event_clicks`, return redirect |
| `SearchService` | `app/Search` | Scout/Meilisearch full-text + geo queries |
| `EventPublishingService` | `app/Services` | Event lifecycle transitions (submit, approve, reject, archive) |
| `OrganizerApprovalService` | `app/Services` | Organizer verification workflow (approve, reject, notify) |

---

## Models

Models should contain:

* Relationships
* Scopes
* Accessors
* Mutators

Models should not contain complex business workflows.

---

# Frontend Standards

## Vue

Use:

* Composition API
* TypeScript
* Pinia

Avoid:

* Options API
* Global state outside Pinia

---

## Components

Components should:

* Have a single responsibility
* Be reusable
* Remain under 300 lines whenever possible

---

# Filament Administration

Filament is reserved for:

* Admin users only
* Event moderation (approve/reject)
* User management
* Organizer verification management
* Click statistics dashboard
* Category and tag management (Phase 2: sponsored content management)

Public user functionality must never depend on Filament.

---

# Security Rules

Always:

* Validate all input
* Sanitize rich text content
* Protect uploads
* Use policies and permissions
* Apply rate limiting

Never:

* Trust client-side validation
* Expose internal system information
* Allow direct file execution
* Store IP addresses

---

# Database Rules

Always:

* Use foreign keys
* Use indexes
* Use soft deletes where appropriate

Never:

* Use manual schema changes
* Skip migrations

---

# Testing Requirements

Every feature requires:

* Feature Tests
* Validation Tests

Critical workflows require:

* Integration Tests

This includes:

* Tracking redirect (`GET /go/{event}`) — verify click is recorded and redirect is issued
* Search / Geosearch — verify index and filter correctness

Bug fixes require:

* Regression Tests

---

# Documentation Requirements

Whenever functionality changes, update:

* OpenAPI specification (`docs/04-openapi.yaml`)
* Engineering specification (`docs/02-engineering-spec.md`)
* Database documentation (`docs/03-database-schema.md`)

Documentation is part of the implementation.

---

# Claude Operating Procedure

For every task:

1. Analyze current implementation.
2. Explain implementation plan.
3. Identify affected files.
4. Implement changes.
5. Run tests.
6. Report results.
7. Report risks.

Before generating code:

* Search existing codebase.
* Reuse existing patterns.
* Avoid duplication.

When uncertain:

STOP and ask for clarification.
