# Design: Sprint 0 (Bootstrap) + Sprint 1 (Auth & Roles)

Date: 2026-06-03
Status: Approved (brainstorming) — ready for implementation planning
Parent design: `docs/superpowers/specs/2026-06-02-mvp-conscious-events-platform-design.md`
Roadmap reference: `docs/07-implementation-roadmap.md` (Sprints 0 and 1)

## 1. Scope

This cycle delivers the software foundation of the MVP: a booting Sail stack with
the full toolchain (Sprint 0) and working authentication with a role model
(Sprint 1). It is the first executable slice of the "Conscious Events Platform"
(an aggregator + outbound-link-tracking event platform — NOT a booking system).

Out of scope for this cycle (later sprints): catalog entities, organizer
self-service, search/geosearch, outbound tracking, i18n UI, public API/Sanctum.

## 2. Verified Environment

- Laravel **13.12.0** installed; Sail app image is **PHP 8.4** (host CLI 8.5);
  `composer.json` requires `php ^8.3` (satisfied).
- `package.json` already has Tailwind **4** + Vite **8**.
- `docker-compose.yml` has `laravel`, `worker`, `mysql` (MySQL 8.0). **No Redis,
  no Meilisearch yet** — added in Sprint 0.
- `filament/filament ^5.6` resolves cleanly against Laravel 13 (verified via
  `composer require --dry-run`). **Filament 5** is the Laravel-13-compatible line
  (the planning docs' guess of "3/4" is corrected — see §6 Doc follow-ups).
- Default `users` migration has name/email/password only (no role/locale/soft
  deletes).

## 3. Architecture Decision

Foundation built on the **official Laravel Vue Starter Kit** scaffolding (Inertia
2 + Vue 3 + TypeScript + Tailwind 4 + session-based auth: register / login /
password reset / email verification), adopted into the existing app. On top of it
we layer Pinia, the role model, Filament 5, Scout/Meilisearch, and the policy
pattern. Rationale: it is the blessed, well-tested path for this exact stack and
removes fragile Inertia/Vite/SSR wiring and most of the Sprint-1 auth work.

Follows the Hybrid code structure from the parent spec (§4): `app/Enums`,
`app/Policies`, `app/Filament`, thin controllers, services only where workflows
exist (none needed for auth yet).

## 4. Sprint 0 — Bootstrap

**Goal:** App boots on Sail with the full toolchain; one passing test; CI scaffold.

Deliverables:
1. Adopt the Vue Starter Kit foundation into the app: Inertia 2 + Vue 3 +
   TypeScript + Tailwind 4 + auth scaffolding (register/login/password
   reset/email verification) + Ziggy.
2. Add **Pinia** (`npm i pinia`), registered in `resources/js/app.ts`.
3. Install **Filament 5** (`composer require filament/filament:^5.6`,
   `php artisan filament:install --panels`); admin panel at `/admin`.
4. Install **Scout + Meilisearch** driver (`laravel/scout`,
   `meilisearch/meilisearch-php`); `SCOUT_DRIVER=meilisearch` configured.
   (Model indexing itself is Sprint 4.)
5. Extend **`docker-compose.yml`** with `redis` and `meilisearch` services;
   update `depends_on`; add env keys to `.env` and `.env.example`
   (`REDIS_HOST`, `MEILISEARCH_HOST`, and Scout/Redis settings).
6. **Test toolchain:** install Pest (Laravel 13 default) as the test runner;
   Pint already present.
7. **CI:** `.github/workflows/ci.yml` running Pint check + Pest tests +
   `npm run build`.
8. **Sanctum stays out** (public API is Phase 2; starter kit uses session auth).

**Acceptance — done when:**
- `./vendor/bin/sail up -d` brings up app + mysql + redis + meilisearch.
- `/up` health route returns 200.
- `/` renders a Vue/Inertia page (frontend pipeline proven).
- `php artisan test` (Pest) is green with at least one passing test.
- CI workflow file present and structurally valid.

## 5. Sprint 1 — Auth & Roles

**Goal:** Register/login (Inertia); role enum drives access; Filament admin
restricted to admins; policy pattern established.

Deliverables:
1. Extend the base `users` migration (greenfield, no data → edit the create
   migration): `role` enum(`user`/`organizer`/`admin`, default `user`),
   `locale` (string, default `de`), `softDeletes()` (`deleted_at`).
2. `app/Enums/UserRole.php` (cases: `User`, `Organizer`, `Admin`); `User` model
   casts `role` to it and uses the `SoftDeletes` trait.
3. Registration sets new users to `role = user`, `locale = de`.
4. Filament gate: `User::canAccessPanel(Panel $panel): bool` → true only when
   `role === UserRole::Admin`; `/admin` is correspondingly protected.
5. Authorization pattern: the role enum + an `EnsureUserHasRole` middleware (or
   Gate) + one example Policy as the template for later entities.
6. Seeder: `DatabaseSeeder` creates one admin user (for Filament login) with a
   known dev credential.
7. Tests (Pest feature tests, TDD):
   - registration creates a user with `role = user`;
   - login succeeds; logout works;
   - guest is redirected from `/admin`; a `user`/`organizer` gets 403 at
     `/admin`; an `admin` can access `/admin`;
   - `role` enum cast returns a `UserRole` instance.

**Acceptance — done when:** register/login feature tests pass; `/admin` access
is correctly role-gated (guest redirect, non-admin 403, admin 200); admin seeder
works; full suite green.

## 6. Documentation follow-ups (small consistency fixes in this cycle)

- `docs/02-engineering-spec.md` and `docs/07-implementation-roadmap.md`: change
  the Filament version note from "3/4" to **Filament 5** (Laravel-13-compatible).
- Note PHP as "8.3+ (Sail image: 8.4)" where the exact runtime matters.

## 7. Testing & Verification

Pest feature tests as above; subagents follow TDD per task. Verification of the
running app (Sail up, `/up`, `/` render) is part of Sprint 0 acceptance and will
be confirmed with actual command output, not assumed.

## 8. Risks

- Adopting the starter kit into an existing (non-fresh) app may require
  reconciling generated files with the current skeleton — handle file-by-file in
  the plan, committing in small steps.
- Meilisearch container health on first boot — the plan includes a healthcheck/
  wait step before asserting Scout connectivity.
