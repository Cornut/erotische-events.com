# Sprint 0 (Bootstrap) + Sprint 1 (Auth & Roles) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Stand up the Conscious Events Platform software foundation — a booting Sail stack with Inertia/Vue/Filament/Scout tooling (Sprint 0) and working session auth with a role model gating the Filament admin (Sprint 1).

**Architecture:** Laravel 13 monolith. Foundation scaffolded with Laravel Breeze (Vue + TypeScript + Pest stack) which provides Inertia 2 + Vue 3 + Tailwind 4 + full session auth. On top we add Pinia, Filament 5 (admin panel), Laravel Scout + Meilisearch (driver only this cycle), a `UserRole` enum, role-gated `/admin`, and a policy/middleware authorization pattern. Hybrid code structure (`app/Enums`, `app/Policies`, `app/Http/Middleware`, `app/Filament`).

**Tech Stack:** Laravel 13.12 / PHP 8.4 (Sail) / 8.5 (host CLI); Inertia 2 (`inertiajs/inertia-laravel ^3.1`); Vue 3 + TypeScript; Tailwind 4 + Vite 8; Laravel Breeze ^2.4; Filament ^5.6; Laravel Scout + meilisearch-php; Pinia; Pest ^4.7; MySQL 8 / Redis / Meilisearch via Sail.

**Conventions for every task:**
- Repo root is `/Users/comodo/Documents/sites/erotische-events.com/root`. All paths relative to it. Run git there.
- Spec (source of truth): `docs/superpowers/specs/2026-06-03-sprint-0-1-bootstrap-auth-design.md`.
- `composer`/`npm`/`php artisan`/`vendor/bin/pint`/`php artisan test` run on the **host** (PHP 8.5, composer 2.9, node 25 are available). The Docker/Sail stack is only needed for the live runtime check in Task 6.
- Tests run against **sqlite :memory:** (configured in Task 1) so the suite runs without Docker. Meilisearch is not exercised this cycle (`SCOUT_DRIVER=null` in tests).
- After each task: run the listed verification, ensure green, then commit exactly the listed files.
- Branch: work happens on a feature branch created by the execution skill, not on `main`.

---

### Task 1: Scaffold foundation with Breeze (Inertia + Vue + TS + Pest) and pin tests to sqlite

**Files:**
- Modify: `composer.json`, `package.json` (via installers)
- Create (by Breeze): `resources/js/**`, `resources/views/app.blade.php`, `app/Http/Middleware/HandleInertiaRequests.php`, `app/Http/Controllers/Auth/**`, `routes/auth.php`, `tests/**` (Pest)
- Modify: `phpunit.xml`

- [ ] **Step 1: Install Breeze (dev) and Inertia**

```bash
composer require laravel/breeze:^2.4 --dev --no-interaction
composer require inertiajs/inertia-laravel:^3.1 --no-interaction
```

- [ ] **Step 2: Run the Breeze Vue scaffolder with TypeScript + Pest**

```bash
php artisan breeze:install vue --typescript --pest --no-interaction
```

This publishes Inertia 2 + Vue 3 + TS pages, Tailwind config, auth controllers (`app/Http/Controllers/Auth/*`), `routes/auth.php`, `app/Http/Middleware/HandleInertiaRequests.php`, and Pest feature tests under `tests/Feature/Auth/`. It overwrites `resources/js/app.ts`, `resources/views/welcome.blade.php`→Inertia, `vite.config.js`, `bootstrap/app.php` (registers the Inertia middleware), and `phpunit.xml`/`tests/Pest.php`.

- [ ] **Step 3: Install JS deps and build**

```bash
npm install
npm run build
```

Expected: Vite build completes with no errors and emits assets under `public/build/`.

- [ ] **Step 4: Pin the test database to sqlite in-memory**

Open `phpunit.xml`. In the `<php>` section, ensure these env lines exist (add or uncomment; Breeze leaves `DB_*` commented):

```xml
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        <env name="SCOUT_DRIVER" value="null"/>
        <env name="MAIL_MAILER" value="array"/>
```

- [ ] **Step 5: Run the test suite to verify the green baseline**

Run: `php artisan test`
Expected: PASS. Breeze's auth feature tests (registration, login, password, email verification) all pass against sqlite. If any fail because the `users` table lacks a column, that is expected only after Task 7 — at THIS point Breeze's own migrations are unmodified, so the suite must be fully green.

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "feat(bootstrap): scaffold Inertia+Vue+TS auth via Breeze, pin tests to sqlite"
```

---

### Task 2: Add Pinia state management

**Files:**
- Modify: `package.json`, `resources/js/app.ts`
- Create: `resources/js/stores/locale.ts`

- [ ] **Step 1: Install Pinia**

```bash
npm install pinia
```

- [ ] **Step 2: Register Pinia in the Inertia app bootstrap**

In `resources/js/app.ts`, import Pinia and install it on the app. The Breeze-generated `setup({ el, App, props, plugin })` creates the app with `createApp`. Add the Pinia plugin to that chain. The relevant edit:

```ts
import { createPinia } from 'pinia'

// inside setup(), where the app is created:
return createApp({ render: () => h(App, props) })
    .use(plugin)
    .use(ZiggyVue)
    .use(createPinia())
    .mount(el)
```

(Keep the existing `.use(plugin)` / `.use(ZiggyVue)` calls; only add `.use(createPinia())` before `.mount(el)`.)

- [ ] **Step 3: Add a minimal store to prove Pinia works**

Create `resources/js/stores/locale.ts`:

```ts
import { defineStore } from 'pinia'

export const useLocaleStore = defineStore('locale', {
  state: () => ({ current: 'de' as 'de' | 'en' }),
  actions: {
    set(locale: 'de' | 'en') {
      this.current = locale
    },
  },
})
```

- [ ] **Step 4: Build to verify no type/bundize errors**

Run: `npm run build`
Expected: build succeeds; no TypeScript or Vite errors.

- [ ] **Step 5: Commit**

```bash
git add package.json package-lock.json resources/js/app.ts resources/js/stores/locale.ts
git commit -m "feat(bootstrap): add Pinia with a locale store"
```

---

### Task 3: Install Filament 5 admin panel

**Files:**
- Modify: `composer.json`
- Create (by installer): `app/Providers/Filament/AdminPanelProvider.php`
- Modify: `bootstrap/providers.php`

- [ ] **Step 1: Install Filament and the admin panel**

```bash
composer require filament/filament:^5.6 --no-interaction
php artisan filament:install --panels --no-interaction
```

The installer creates `app/Providers/Filament/AdminPanelProvider.php` (panel id `admin`, path `/admin`) and registers it in `bootstrap/providers.php`.

- [ ] **Step 2: Verify the panel provider exists and the route is registered**

Run: `php artisan route:list --path=admin`
Expected: routes under `admin/...` (e.g. `admin/login`) are listed.

- [ ] **Step 3: Run the suite to confirm nothing broke**

Run: `php artisan test`
Expected: PASS (unchanged green baseline).

- [ ] **Step 4: Commit**

```bash
git add composer.json composer.lock bootstrap/providers.php app/Providers/Filament/
git commit -m "feat(bootstrap): install Filament 5 admin panel at /admin"
```

---

### Task 4: Install Scout + Meilisearch driver (configuration only)

**Files:**
- Modify: `composer.json`
- Create: `config/scout.php` (published)
- Modify: `.env`, `.env.example`

- [ ] **Step 1: Install Scout and the Meilisearch client**

```bash
composer require laravel/scout meilisearch/meilisearch-php --no-interaction
php artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider" --no-interaction
```

- [ ] **Step 2: Configure the Scout driver in env files**

Append to BOTH `.env` and `.env.example`:

```env
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://meilisearch:7700
MEILISEARCH_KEY=masterKey
```

(No model is made searchable this cycle — indexing is Sprint 4.)

- [ ] **Step 3: Verify config loads**

Run: `php artisan config:show scout.driver`
Expected: prints `meilisearch`. (If `config:show` is unavailable, run `php -r "require 'vendor/autoload.php';" ` is not needed — instead `php artisan tinker --execute="echo config('scout.driver');"` prints `meilisearch`.)

- [ ] **Step 4: Run the suite (uses SCOUT_DRIVER=null from phpunit.xml)**

Run: `php artisan test`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add composer.json composer.lock config/scout.php .env.example
git commit -m "feat(bootstrap): install Scout + Meilisearch driver config"
```

(Do NOT commit `.env` — it is gitignored. Only `.env.example` is tracked.)

---

### Task 5: Add Redis + Meilisearch services to Docker Compose

**Files:**
- Modify: `docker-compose.yml`
- Modify: `.env`, `.env.example`

- [ ] **Step 1: Add the two services and a volume**

In `docker-compose.yml`, add these services under the existing `services:` block (siblings of `mysql`), matching the existing indentation (4 spaces) and the `sail` network already used by `laravel`:

```yaml
    redis:
        image: 'redis:alpine'
        ports:
            - '${FORWARD_REDIS_PORT:-6379}:6379'
        command: 'redis-server --save 60 1 --loglevel warning'
        volumes:
            - 'erotic-redis:/data'
        networks:
            - sail
        healthcheck:
            test: ['CMD', 'redis-cli', 'ping']
            retries: 3
            timeout: 5s
    meilisearch:
        image: 'getmeili/meilisearch:latest'
        ports:
            - '${FORWARD_MEILISEARCH_PORT:-7700}:7700'
        environment:
            MEILI_NO_ANALYTICS: 'true'
            MEILI_MASTER_KEY: '${MEILISEARCH_KEY:-masterKey}'
        volumes:
            - 'erotic-meilisearch:/meili_data'
        networks:
            - sail
        healthcheck:
            test: ['CMD', 'wget', '--no-verbose', '--spider', 'http://localhost:7700/health']
            retries: 3
            timeout: 5s
```

In the existing `volumes:` block (bottom of the file, next to `erotic-mysql`), add:

```yaml
    erotic-redis:
        driver: local
    erotic-meilisearch:
        driver: local
```

In the `laravel` service's `depends_on:` list, add `redis` and `meilisearch` alongside `mysql`.

- [ ] **Step 2: Add the Redis host env to env files**

Append to BOTH `.env` and `.env.example`:

```env
REDIS_HOST=redis
REDIS_PORT=6379
```

- [ ] **Step 3: Validate the compose file**

Run: `docker compose config --quiet && echo "compose OK"`
Expected: prints `compose OK` with no YAML/schema errors.

- [ ] **Step 4: Commit**

```bash
git add docker-compose.yml .env.example
git commit -m "feat(bootstrap): add redis and meilisearch services to Sail"
```

---

### Task 6: CI workflow + live runtime verification

**Files:**
- Create: `.github/workflows/ci.yml`

- [ ] **Step 1: Write the CI workflow**

Create `.github/workflows/ci.yml`:

```yaml
name: CI
on:
  push:
    branches: [main]
  pull_request:
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          coverage: none
      - name: Install PHP deps
        run: composer install --no-interaction --prefer-dist
      - name: Copy env
        run: cp .env.example .env && php artisan key:generate
      - name: Pint (lint)
        run: vendor/bin/pint --test
      - name: Tests
        run: php artisan test
      - uses: actions/setup-node@v4
        with:
          node-version: '22'
      - name: Build assets
        run: npm ci && npm run build
```

- [ ] **Step 2: Verify Pint passes locally (CI will run the same)**

Run: `vendor/bin/pint --test`
Expected: "PASS" / no style issues. If issues are reported, run `vendor/bin/pint` to fix, then re-run `--test`.

- [ ] **Step 3: Live runtime verification on Sail**

```bash
cp -n .env.example .env || true
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --force
curl -s -o /dev/null -w "%{http_code}" http://localhost/up
```

Expected: the `/up` health route returns `200`. Also `curl -s http://localhost/ | grep -q 'id="app"'` should succeed (the Inertia root div is present, proving the Vue app mounts). Record the actual HTTP codes in the task report. Then `./vendor/bin/sail down`.

- [ ] **Step 4: Commit**

```bash
git add .github/workflows/ci.yml
git commit -m "ci: add lint+test+build workflow"
```

---

### Task 7: Extend users (role, locale, soft deletes) + UserRole enum + model casts

**Files:**
- Create: `app/Enums/UserRole.php`
- Modify: `database/migrations/0001_01_01_000000_create_users_table.php`
- Modify: `app/Models/User.php`
- Modify: `database/factories/UserFactory.php`
- Test: `tests/Unit/UserRoleTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/UserRoleTest.php`:

```php
<?php

use App\Enums\UserRole;
use App\Models\User;

it('defaults a new user to the user role', function () {
    $user = User::factory()->create();
    expect($user->role)->toBeInstanceOf(UserRole::class)
        ->and($user->role)->toBe(UserRole::User)
        ->and($user->locale)->toBe('de');
});

it('can create an admin and soft-delete it', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    expect($admin->role)->toBe(UserRole::Admin);

    $admin->delete();
    expect(User::query()->count())->toBe(0)
        ->and(User::withTrashed()->count())->toBe(1);
});
```

- [ ] **Step 2: Run it to verify it fails**

Run: `php artisan test --filter=UserRoleTest`
Expected: FAIL — `App\Enums\UserRole` does not exist / `role` is not cast.

- [ ] **Step 3: Create the enum**

Create `app/Enums/UserRole.php`:

```php
<?php

namespace App\Enums;

enum UserRole: string
{
    case User = 'user';
    case Organizer = 'organizer';
    case Admin = 'admin';
}
```

- [ ] **Step 4: Add columns to the users migration**

In `database/migrations/0001_01_01_000000_create_users_table.php`, inside the `Schema::create('users', ...)` closure, add after the `password` line and before `rememberToken()`:

```php
            $table->string('role')->default('user');
            $table->string('locale', 5)->default('de');
```

And after `$table->timestamps();` add:

```php
            $table->softDeletes();
```

- [ ] **Step 5: Cast role and enable soft deletes on the model**

In `app/Models/User.php`: add the imports and trait, and the cast.

```php
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\SoftDeletes;
```

Add `SoftDeletes` to the `use ...;` traits line inside the class (e.g. `use HasFactory, Notifiable, SoftDeletes;`).

In the `casts()` method's returned array, add:

```php
            'role' => UserRole::class,
```

- [ ] **Step 6: Let the factory accept a role and default locale**

In `database/factories/UserFactory.php`, add to the `definition()` returned array:

```php
            'role' => \App\Enums\UserRole::User,
            'locale' => 'de',
```

- [ ] **Step 7: Run the test to verify it passes**

Run: `php artisan test --filter=UserRoleTest`
Expected: PASS (sqlite rebuilds the in-memory schema from migrations each run).

- [ ] **Step 8: Commit**

```bash
git add app/Enums/UserRole.php database/migrations/0001_01_01_000000_create_users_table.php app/Models/User.php database/factories/UserFactory.php tests/Unit/UserRoleTest.php
git commit -m "feat(auth): add UserRole enum, role/locale columns, soft deletes"
```

---

### Task 8: Registration assigns the default role and locale

**Files:**
- Modify: `app/Http/Controllers/Auth/RegisteredUserController.php`
- Test: `tests/Feature/Auth/RegistrationRoleTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Auth/RegistrationRoleTest.php`:

```php
<?php

use App\Enums\UserRole;
use App\Models\User;

it('registers a new user with the default user role and de locale', function () {
    $response = $this->post('/register', [
        'name' => 'Test Person',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect();
    $user = User::where('email', 'test@example.com')->firstOrFail();
    expect($user->role)->toBe(UserRole::User)
        ->and($user->locale)->toBe('de');
});
```

- [ ] **Step 2: Run it to verify it fails or passes**

Run: `php artisan test --filter=RegistrationRoleTest`
Expected: This may PASS already because the migration defaults `role=user` and `locale=de`. If it passes, the DB defaults are doing the work — still proceed to Step 3 to make the intent explicit (defense in depth), then keep the test. If it FAILS, Step 3 fixes it.

- [ ] **Step 3: Set role and locale explicitly on creation**

In `app/Http/Controllers/Auth/RegisteredUserController.php`, in the `store()` method's `User::create([...])` array, add:

```php
            'role' => \App\Enums\UserRole::User,
            'locale' => 'de',
```

(Place these alongside the existing `name`, `email`, `password` keys.)

- [ ] **Step 4: Run the test to verify it passes**

Run: `php artisan test --filter=RegistrationRoleTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Auth/RegisteredUserController.php tests/Feature/Auth/RegistrationRoleTest.php
git commit -m "feat(auth): registration assigns default user role and de locale"
```

---

### Task 9: Role-gate the Filament admin panel

**Files:**
- Modify: `app/Models/User.php`
- Test: `tests/Feature/AdminPanelAccessTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/AdminPanelAccessTest.php`:

```php
<?php

use App\Enums\UserRole;
use App\Models\User;

it('redirects guests away from the admin panel', function () {
    $this->get('/admin')->assertRedirect();
});

it('forbids non-admin users from the admin panel', function () {
    $user = User::factory()->create(['role' => UserRole::User]);
    $this->actingAs($user)->get('/admin')->assertForbidden();
});

it('allows admins into the admin panel', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($admin)->get('/admin')->assertSuccessful();
});
```

- [ ] **Step 2: Run it to verify it fails**

Run: `php artisan test --filter=AdminPanelAccessTest`
Expected: FAIL — by default Filament authorizes all authenticated users (non-admin test fails: gets 200 instead of 403).

- [ ] **Step 3: Implement `canAccessPanel` on the User model**

In `app/Models/User.php`, implement Filament's contract. Add the import and the interface, and the method.

```php
use Filament\Panel;
use Filament\Models\Contracts\FilamentUser;
```

Change the class declaration to implement the contract, e.g.:

```php
class User extends Authenticatable implements FilamentUser, MustVerifyEmail
```

(If the class did not previously implement `MustVerifyEmail`, just add `implements FilamentUser`. Keep any existing `implements` interfaces and append `FilamentUser` to the comma-separated list.)

Add the method to the class body:

```php
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role === UserRole::Admin;
    }
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `php artisan test --filter=AdminPanelAccessTest`
Expected: PASS (guest redirect, non-admin 403, admin 200).

- [ ] **Step 5: Commit**

```bash
git add app/Models/User.php tests/Feature/AdminPanelAccessTest.php
git commit -m "feat(auth): restrict Filament admin panel to admin role"
```

---

### Task 10: Role middleware + example policy pattern

**Files:**
- Create: `app/Http/Middleware/EnsureUserHasRole.php`
- Modify: `bootstrap/app.php`
- Create: `app/Policies/UserPolicy.php`
- Test: `tests/Feature/EnsureUserHasRoleTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/EnsureUserHasRoleTest.php`:

```php
<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware(['web', 'auth', 'role:admin'])->get('/_test/admin-only', fn () => 'ok');
});

it('blocks a non-admin from a role:admin route', function () {
    $user = User::factory()->create(['role' => UserRole::User]);
    $this->actingAs($user)->get('/_test/admin-only')->assertForbidden();
});

it('allows an admin through a role:admin route', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($admin)->get('/_test/admin-only')->assertOk();
});
```

- [ ] **Step 2: Run it to verify it fails**

Run: `php artisan test --filter=EnsureUserHasRoleTest`
Expected: FAIL — the `role` middleware alias is not registered.

- [ ] **Step 3: Create the middleware**

Create `app/Http/Middleware/EnsureUserHasRole.php`:

```php
<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null || ! in_array($user->role, array_map(
            fn (string $role) => UserRole::from($role),
            $roles
        ), true)) {
            abort(403);
        }

        return $next($request);
    }
}
```

- [ ] **Step 4: Register the `role` middleware alias**

In `bootstrap/app.php`, inside `->withMiddleware(function (Middleware $middleware) { ... })`, add:

```php
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);
```

(If a `$middleware->alias([...])` call already exists, add the `'role' => ...` entry to it instead of creating a second call.)

- [ ] **Step 5: Run the middleware test to verify it passes**

Run: `php artisan test --filter=EnsureUserHasRoleTest`
Expected: PASS.

- [ ] **Step 6: Create the example policy (the template for later entities)**

Create `app/Policies/UserPolicy.php`:

```php
<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function update(User $user, User $model): bool
    {
        return $user->role === UserRole::Admin || $user->is($model);
    }

    public function delete(User $user, User $model): bool
    {
        return $user->role === UserRole::Admin;
    }
}
```

- [ ] **Step 7: Run the whole suite to confirm nothing regressed**

Run: `php artisan test`
Expected: PASS (all tests green).

- [ ] **Step 8: Commit**

```bash
git add app/Http/Middleware/EnsureUserHasRole.php bootstrap/app.php app/Policies/UserPolicy.php tests/Feature/EnsureUserHasRoleTest.php
git commit -m "feat(auth): role middleware and example user policy"
```

---

### Task 11: Seed an admin user

**Files:**
- Modify: `database/seeders/DatabaseSeeder.php`
- Test: `tests/Feature/AdminSeederTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/AdminSeederTest.php`:

```php
<?php

use App\Enums\UserRole;
use App\Models\User;

it('seeds exactly one admin user', function () {
    $this->seed(\Database\Seeders\DatabaseSeeder::class);

    $admin = User::where('email', 'admin@erotische-events.com')->firstOrFail();
    expect($admin->role)->toBe(UserRole::Admin);
});
```

- [ ] **Step 2: Run it to verify it fails**

Run: `php artisan test --filter=AdminSeederTest`
Expected: FAIL — no admin user is seeded.

- [ ] **Step 3: Seed the admin**

Replace the body of `run()` in `database/seeders/DatabaseSeeder.php` with:

```php
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@erotische-events.com'],
            [
                'name' => 'Admin',
                'password' => bcrypt('password'),
                'role' => \App\Enums\UserRole::Admin,
                'locale' => 'de',
            ],
        );
    }
```

Ensure `use App\Models\User;` is present at the top of the file.

- [ ] **Step 4: Run the test to verify it passes**

Run: `php artisan test --filter=AdminSeederTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add database/seeders/DatabaseSeeder.php tests/Feature/AdminSeederTest.php
git commit -m "feat(auth): seed an admin user for Filament login"
```

---

### Task 12: Documentation consistency follow-ups

**Files:**
- Modify: `docs/02-engineering-spec.md`
- Modify: `docs/07-implementation-roadmap.md`

- [ ] **Step 1: Correct the Filament version and PHP note**

In `docs/02-engineering-spec.md`: find the Filament line that says the version must be verified / mentions Filament "3" or "4" and change it to state **Filament 5 (`^5.6`), verified compatible with Laravel 13**. Where the PHP version is given, ensure it reads "PHP 8.3+ (Sail image: 8.4)".

In `docs/07-implementation-roadmap.md`: in Sprint 0, change any "Filament-Kompatibilität prüfen (3/4)" wording to state **Filament 5 (`^5.6`) ist verifiziert kompatibel**.

- [ ] **Step 2: Verify no stale version text remains**

Run: `grep -rin "filament 3\|filament 4\|Filament-Version" docs/02-engineering-spec.md docs/07-implementation-roadmap.md`
Expected: no hits implying an unverified or 3/4 Filament version.

- [ ] **Step 3: Commit**

```bash
git add docs/02-engineering-spec.md docs/07-implementation-roadmap.md
git commit -m "docs: record verified Filament 5 + PHP 8.4 Sail runtime"
```

---

## Self-Review (run after all tasks)

- [ ] **Spec coverage:** Sprint 0 deliverables (Inertia/Vue/TS via Breeze=Task 1; Pinia=2; Filament 5=3; Scout/Meilisearch=4; redis+meilisearch compose=5; Pest baseline=1; CI=6; live `/up` + render=6) and Sprint 1 deliverables (users migration+enum+casts=7; registration defaults=8; Filament role gate=9; role middleware+policy=10; admin seeder=11; doc fixes=12) each map to a task.
- [ ] **Green suite:** `php artisan test` passes after every task; `vendor/bin/pint --test` clean.
- [ ] **Runtime proven:** Task 6 records actual HTTP 200 from `/up` and the Inertia root div on `/` — evidence, not assumption.
- [ ] **No placeholders:** every code/edit step contains concrete code or an exact command.
