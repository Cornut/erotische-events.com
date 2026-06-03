# Sprint 3 — Organizer Self-Service & Event Lifecycle Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: superpowers:subagent-driven-development. Steps use `- [ ]`.

**Goal:** Organizers can register a profile (pending → admin-approved), and approved organizers manage their venues/events through an Inertia dashboard with the draft→pending_review→published/rejected lifecycle. Public visitors see published events and organizer profiles. Admin moderation (approve/reject organizer, publish/reject event) runs through Filament actions backed by services.

**Architecture:** Two services hold the workflows: `App\Services\OrganizerApprovalService` and `App\Services\EventPublishingService`. Authorization via `OrganizerPolicy` and `EventPolicy`. Organizer self-service and public pages are Inertia/Vue. Admin moderation uses Filament actions calling the services. No search yet (Sprint 4).

**Tech Stack:** Laravel 13, Inertia 2 + Vue 3 + TS, Filament 5, Pest. Tests on sqlite :memory:. 59 tests pass on `main`.

**Conventions:** Repo root `/Users/comodo/Documents/sites/erotische-events.com/root`. TDD per task. After each task: `php artisan test` green + `vendor/bin/pint --test` clean, then commit listed files. Reference `docs/03-database-schema.md`, existing models/enums (UserRole, EventStatus, OrganizerVerificationStatus, Event, Organizer, Venue).

---

### Task 1: EventPublishingService (lifecycle transitions)

**Files:**
- Create: `app/Services/EventPublishingService.php`
- Create: `app/Exceptions/InvalidEventTransitionException.php`
- Test: `tests/Feature/EventPublishingServiceTest.php`

- [ ] **Step 1: Failing test** — `tests/Feature/EventPublishingServiceTest.php`:

```php
<?php

use App\Enums\EventStatus;
use App\Exceptions\InvalidEventTransitionException;
use App\Models\Event;
use App\Services\EventPublishingService;

beforeEach(function () {
    $this->service = app(EventPublishingService::class);
});

it('submits a draft for review', function () {
    $event = Event::factory()->create(['status' => EventStatus::Draft]);
    $this->service->submit($event);
    expect($event->fresh()->status)->toBe(EventStatus::PendingReview);
});

it('publishes a pending event', function () {
    $event = Event::factory()->create(['status' => EventStatus::PendingReview]);
    $this->service->publish($event);
    expect($event->fresh()->status)->toBe(EventStatus::Published);
});

it('rejects a pending event', function () {
    $event = Event::factory()->create(['status' => EventStatus::PendingReview]);
    $this->service->reject($event);
    expect($event->fresh()->status)->toBe(EventStatus::Rejected);
});

it('archives a published event', function () {
    $event = Event::factory()->create(['status' => EventStatus::Published]);
    $this->service->archive($event);
    expect($event->fresh()->status)->toBe(EventStatus::Archived);
});

it('forbids publishing a draft directly', function () {
    $event = Event::factory()->create(['status' => EventStatus::Draft]);
    $this->service->publish($event);
})->throws(InvalidEventTransitionException::class);
```

- [ ] **Step 2: Run — expect FAIL.**

- [ ] **Step 3: Exception** `app/Exceptions/InvalidEventTransitionException.php`:

```php
<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidEventTransitionException extends RuntimeException
{
}
```

- [ ] **Step 4: Service** `app/Services/EventPublishingService.php`:

```php
<?php

namespace App\Services;

use App\Enums\EventStatus;
use App\Exceptions\InvalidEventTransitionException;
use App\Models\Event;

class EventPublishingService
{
    public function submit(Event $event): Event
    {
        return $this->transition($event, [EventStatus::Draft, EventStatus::Rejected], EventStatus::PendingReview);
    }

    public function publish(Event $event): Event
    {
        return $this->transition($event, [EventStatus::PendingReview], EventStatus::Published);
    }

    public function reject(Event $event): Event
    {
        return $this->transition($event, [EventStatus::PendingReview], EventStatus::Rejected);
    }

    public function archive(Event $event): Event
    {
        return $this->transition($event, [EventStatus::Published], EventStatus::Archived);
    }

    /**
     * @param  array<EventStatus>  $allowedFrom
     */
    private function transition(Event $event, array $allowedFrom, EventStatus $to): Event
    {
        if (! in_array($event->status, $allowedFrom, true)) {
            throw new InvalidEventTransitionException(
                "Cannot transition event {$event->id} from {$event->status->value} to {$to->value}."
            );
        }

        $event->update(['status' => $to]);

        return $event;
    }
}
```

- [ ] **Step 5: Run filter (PASS), full suite (expect 64), pint clean.**

- [ ] **Step 6: Commit**

```bash
git add app/Services/EventPublishingService.php app/Exceptions/InvalidEventTransitionException.php tests/Feature/EventPublishingServiceTest.php
git commit -m "feat(lifecycle): EventPublishingService with guarded status transitions"
```

---

### Task 2: OrganizerApprovalService

**Files:**
- Create: `app/Services/OrganizerApprovalService.php`
- Test: `tests/Feature/OrganizerApprovalServiceTest.php`

- [ ] **Step 1: Failing test** — `tests/Feature/OrganizerApprovalServiceTest.php`:

```php
<?php

use App\Enums\OrganizerVerificationStatus;
use App\Models\Organizer;
use App\Services\OrganizerApprovalService;

beforeEach(fn () => $this->service = app(OrganizerApprovalService::class));

it('approves a pending organizer', function () {
    $organizer = Organizer::factory()->create(['verification_status' => OrganizerVerificationStatus::Pending]);
    $this->service->approve($organizer);
    expect($organizer->fresh()->verification_status)->toBe(OrganizerVerificationStatus::Approved);
});

it('rejects a pending organizer', function () {
    $organizer = Organizer::factory()->create(['verification_status' => OrganizerVerificationStatus::Pending]);
    $this->service->reject($organizer);
    expect($organizer->fresh()->verification_status)->toBe(OrganizerVerificationStatus::Rejected);
});

it('reports whether an organizer may publish', function () {
    $approved = Organizer::factory()->approved()->create();
    $pending = Organizer::factory()->create();
    expect($this->service->canPublish($approved))->toBeTrue()
        ->and($this->service->canPublish($pending))->toBeFalse();
});
```

- [ ] **Step 2: Run — expect FAIL.**

- [ ] **Step 3: Service** `app/Services/OrganizerApprovalService.php`:

```php
<?php

namespace App\Services;

use App\Enums\OrganizerVerificationStatus;
use App\Models\Organizer;

class OrganizerApprovalService
{
    public function approve(Organizer $organizer): Organizer
    {
        $organizer->update(['verification_status' => OrganizerVerificationStatus::Approved]);

        return $organizer;
    }

    public function reject(Organizer $organizer): Organizer
    {
        $organizer->update(['verification_status' => OrganizerVerificationStatus::Rejected]);

        return $organizer;
    }

    public function canPublish(Organizer $organizer): bool
    {
        return $organizer->verification_status === OrganizerVerificationStatus::Approved;
    }
}
```

- [ ] **Step 4: Run filter (PASS), full suite (expect 67), pint clean.**

- [ ] **Step 5: Commit**

```bash
git add app/Services/OrganizerApprovalService.php tests/Feature/OrganizerApprovalServiceTest.php
git commit -m "feat(organizer): OrganizerApprovalService approve/reject/canPublish"
```

---

### Task 3: Policies (Organizer, Event)

**Files:**
- Create: `app/Policies/OrganizerPolicy.php`, `app/Policies/EventPolicy.php`
- Test: `tests/Feature/CatalogPolicyTest.php`

- [ ] **Step 1: Failing test** — `tests/Feature/CatalogPolicyTest.php`:

```php
<?php

use App\Enums\UserRole;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\User;

it('lets an organizer owner update their own event but not another', function () {
    $owner = User::factory()->create(['role' => UserRole::Organizer]);
    $organizer = Organizer::factory()->approved()->create(['owner_user_id' => $owner->id]);
    $event = Event::factory()->create(['organizer_id' => $organizer->id]);

    $stranger = User::factory()->create(['role' => UserRole::Organizer]);

    expect($owner->can('update', $event))->toBeTrue()
        ->and($stranger->can('update', $event))->toBeFalse();
});

it('lets an admin update any event', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $event = Event::factory()->create();
    expect($admin->can('update', $event))->toBeTrue();
});

it('lets an owner update their organizer profile and admin any', function () {
    $owner = User::factory()->create(['role' => UserRole::Organizer]);
    $organizer = Organizer::factory()->create(['owner_user_id' => $owner->id]);
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    expect($owner->can('update', $organizer))->toBeTrue()
        ->and($admin->can('update', $organizer))->toBeTrue();
});
```

- [ ] **Step 2: Run — expect FAIL.**

- [ ] **Step 3: Policies**

`app/Policies/OrganizerPolicy.php`:

```php
<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Organizer;
use App\Models\User;

class OrganizerPolicy
{
    public function update(User $user, Organizer $organizer): bool
    {
        return $user->role === UserRole::Admin || $organizer->owner_user_id === $user->id;
    }

    public function delete(User $user, Organizer $organizer): bool
    {
        return $user->role === UserRole::Admin;
    }
}
```

`app/Policies/EventPolicy.php`:

```php
<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function update(User $user, Event $event): bool
    {
        return $user->role === UserRole::Admin
            || ($event->organizer !== null && $event->organizer->owner_user_id === $user->id);
    }

    public function delete(User $user, Event $event): bool
    {
        return $this->update($user, $event);
    }
}
```

(Laravel auto-discovers policies by model name in `app/Policies`.)

- [ ] **Step 4: Run filter (PASS), full suite (expect 70), pint clean.**

- [ ] **Step 5: Commit**

```bash
git add app/Policies/OrganizerPolicy.php app/Policies/EventPolicy.php tests/Feature/CatalogPolicyTest.php
git commit -m "feat(authz): organizer and event policies"
```

---

### Task 4: Become-organizer self-registration

**Files:**
- Create: `app/Http/Controllers/OrganizerRegistrationController.php`
- Create: `resources/js/Pages/Organizer/Register.vue`
- Modify: `routes/web.php`
- Test: `tests/Feature/OrganizerRegistrationTest.php`

- [ ] **Step 1: Failing test** — `tests/Feature/OrganizerRegistrationTest.php`:

```php
<?php

use App\Enums\OrganizerVerificationStatus;
use App\Enums\UserRole;
use App\Models\Organizer;
use App\Models\User;

it('lets an authenticated user register an organizer profile as pending', function () {
    $user = User::factory()->create(['role' => UserRole::User]);

    $response = $this->actingAs($user)->post('/organizer/register', [
        'company_name' => 'Tantra Berlin',
    ]);

    $response->assertRedirect();
    $organizer = Organizer::where('owner_user_id', $user->id)->firstOrFail();
    expect($organizer->verification_status)->toBe(OrganizerVerificationStatus::Pending)
        ->and($user->fresh()->role)->toBe(UserRole::Organizer);
});

it('requires authentication to register an organizer', function () {
    $this->post('/organizer/register', ['company_name' => 'X'])->assertRedirect('/login');
});
```

- [ ] **Step 2: Run — expect FAIL.**

- [ ] **Step 3: Controller** `app/Http/Controllers/OrganizerRegistrationController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Enums\OrganizerVerificationStatus;
use App\Enums\UserRole;
use App\Models\Organizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class OrganizerRegistrationController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Organizer/Register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url'],
        ]);

        $user = $request->user();

        $organizer = Organizer::create([
            'owner_user_id' => $user->id,
            'company_name' => $data['company_name'],
            'contact_name' => $data['contact_name'] ?? null,
            'website' => $data['website'] ?? null,
            'slug' => Str::slug($data['company_name']).'-'.Str::lower(Str::random(6)),
            'verification_status' => OrganizerVerificationStatus::Pending,
        ]);

        if ($user->role === UserRole::User) {
            $user->update(['role' => UserRole::Organizer]);
        }

        return redirect()->route('organizer.dashboard');
    }
}
```

- [ ] **Step 4: Vue page** `resources/js/Pages/Organizer/Register.vue` — a minimal Inertia form posting to `/organizer/register` with a `company_name` text input (and optional contact_name, website), using Inertia's `useForm`. Follow the existing Breeze page style in `resources/js/Pages/Auth/Register.vue`. Keep it simple and typed.

- [ ] **Step 5: Routes** — in `routes/web.php` add (inside the `auth` middleware group; create one if absent):

```php
use App\Http\Controllers\OrganizerRegistrationController;

Route::middleware('auth')->group(function () {
    Route::get('/organizer/register', [OrganizerRegistrationController::class, 'create'])->name('organizer.register');
    Route::post('/organizer/register', [OrganizerRegistrationController::class, 'store']);
});
```

(The `organizer.dashboard` route is added in Task 5; if running tests before Task 5, temporarily redirect to `/` — but implement Task 5 right after so the named route exists. To avoid an undefined-route error, define a placeholder `organizer.dashboard` route now returning `Inertia::render('Organizer/Dashboard')` or add it in Task 5 and run Task 4+5 tests together. Simplest: add the dashboard route in this task's routes group too, see Task 5.)

- [ ] **Step 6: Run filter (PASS), full suite, pint clean, build (`npm run build`).**

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/OrganizerRegistrationController.php resources/js/Pages/Organizer/Register.vue routes/web.php tests/Feature/OrganizerRegistrationTest.php
git commit -m "feat(organizer): become-organizer self-registration"
```

---

### Task 5: Organizer dashboard (manage own events/venues) + lifecycle actions

**Files:**
- Create: `app/Http/Controllers/Organizer/DashboardController.php`, `app/Http/Controllers/Organizer/EventController.php`
- Create: `resources/js/Pages/Organizer/Dashboard.vue`, `resources/js/Pages/Organizer/Events/Create.vue`, `resources/js/Pages/Organizer/Events/Edit.vue`
- Modify: `routes/web.php`
- Test: `tests/Feature/OrganizerDashboardTest.php`

- [ ] **Step 1: Failing test** — `tests/Feature/OrganizerDashboardTest.php`:

```php
<?php

use App\Enums\EventStatus;
use App\Enums\UserRole;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\User;

function organizerUser(): array
{
    $user = User::factory()->create(['role' => UserRole::Organizer]);
    $organizer = Organizer::factory()->approved()->create(['owner_user_id' => $user->id]);

    return [$user, $organizer];
}

it('shows the dashboard with only the organizer own events', function () {
    [$user, $organizer] = organizerUser();
    Event::factory()->create(['organizer_id' => $organizer->id, 'title' => 'Mine']);
    Event::factory()->create(['title' => 'Someone else']);

    $this->actingAs($user)->get('/organizer/dashboard')->assertSuccessful();
});

it('lets an organizer create a draft event', function () {
    [$user, $organizer] = organizerUser();

    $response = $this->actingAs($user)->post('/organizer/events', [
        'title' => 'New Workshop',
        'booking_url' => 'https://example.com/book',
        'start_date' => now()->addWeek()->toDateTimeString(),
    ]);

    $response->assertRedirect();
    $event = Event::where('title', 'New Workshop')->firstOrFail();
    expect($event->status)->toBe(EventStatus::Draft)
        ->and($event->organizer_id)->toBe($organizer->id);
});

it('lets an organizer submit their draft for review', function () {
    [$user, $organizer] = organizerUser();
    $event = Event::factory()->create(['organizer_id' => $organizer->id, 'status' => EventStatus::Draft]);

    $this->actingAs($user)->post("/organizer/events/{$event->id}/submit")->assertRedirect();
    expect($event->fresh()->status)->toBe(EventStatus::PendingReview);
});

it('forbids submitting another organizer event', function () {
    [$user] = organizerUser();
    $foreign = Event::factory()->create(['status' => EventStatus::Draft]);

    $this->actingAs($user)->post("/organizer/events/{$foreign->id}/submit")->assertForbidden();
});
```

- [ ] **Step 2: Run — expect FAIL.**

- [ ] **Step 3: Controllers**

`app/Http/Controllers/Organizer/DashboardController.php` — resolve the current user's organizer, render `Organizer/Dashboard` with their events (with venue) and venues. Render via Inertia.

`app/Http/Controllers/Organizer/EventController.php` — `store` (create draft for the user's organizer, validate title/booking_url/start_date and optional fields, status Draft, generate slug), `update`, and `submit` (authorize `update` on the event via EventPolicy, then call `EventPublishingService::submit`). Use `$this->authorize('update', $event)` and the `AuthorizesRequests` trait. Inject `EventPublishingService`.

Example `submit`:

```php
public function submit(Event $event, EventPublishingService $service): RedirectResponse
{
    $this->authorize('update', $event);
    $service->submit($event);

    return back();
}
```

Example `store` (organizer resolved from the authenticated user's `organizer` — add a `organizer()` hasOne relation on User: `return $this->hasOne(Organizer::class, 'owner_user_id');` — add that to `app/Models/User.php`):

```php
public function store(Request $request): RedirectResponse
{
    $organizer = $request->user()->organizer;
    abort_if($organizer === null, 403);

    $data = $request->validate([
        'title' => ['required', 'string', 'max:255'],
        'booking_url' => ['required', 'url'],
        'start_date' => ['required', 'date'],
        'short_description' => ['nullable', 'string', 'max:255'],
    ]);

    $organizer->events()->create([
        ...$data,
        'slug' => Str::slug($data['title']).'-'.Str::lower(Str::random(6)),
        'status' => EventStatus::Draft,
    ]);

    return redirect()->route('organizer.dashboard');
}
```

Add `use Illuminate\Foundation\Auth\Access\AuthorizesRequests;` and `use AuthorizesRequests;` in the controller (or ensure the base `App\Http\Controllers\Controller` uses it; in Laravel 13 add the trait to the controller).

- [ ] **Step 4: Add the `organizer()` relation to `app/Models/User.php`:**

```php
public function organizer(): \Illuminate\Database\Eloquent\Relations\HasOne
{
    return $this->hasOne(Organizer::class, 'owner_user_id');
}
```

- [ ] **Step 5: Vue pages** — `Organizer/Dashboard.vue` (table of own events with status + a "submit" button posting to `/organizer/events/{id}/submit`, link to create), `Organizer/Events/Create.vue` and `Edit.vue` (Inertia `useForm` with title, booking_url, start_date, short_description). Minimal, typed, following Breeze page conventions.

- [ ] **Step 6: Routes** — in `routes/web.php`, inside an `auth` group:

```php
use App\Http\Controllers\Organizer\DashboardController;
use App\Http\Controllers\Organizer\EventController;

Route::middleware('auth')->prefix('organizer')->name('organizer.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/events', [EventController::class, 'store'])->name('events.store');
    Route::put('/events/{event}', [EventController::class, 'update'])->name('events.update');
    Route::post('/events/{event}/submit', [EventController::class, 'submit'])->name('events.submit');
});
```

- [ ] **Step 7: Run filter (PASS), full suite, pint clean, `npm run build`.**

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/Organizer app/Models/User.php resources/js/Pages/Organizer routes/web.php tests/Feature/OrganizerDashboardTest.php
git commit -m "feat(organizer): self-service dashboard with event create/submit lifecycle"
```

---

### Task 6: Filament moderation actions (approve/reject organizer; publish/reject event)

**Files:**
- Modify: `app/Filament/Resources/Organizers/Tables/OrganizersTable.php`, `app/Filament/Resources/Events/Tables/EventsTable.php`
- Test: `tests/Feature/FilamentModerationTest.php`

- [ ] **Step 1: Failing test** — `tests/Feature/FilamentModerationTest.php`. Use Livewire/Filament test helpers to invoke the table action. Filament 5 table action testing pattern:

```php
<?php

use App\Enums\EventStatus;
use App\Enums\OrganizerVerificationStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Events\Pages\ListEvents;
use App\Filament\Resources\Organizers\Pages\ListOrganizers;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\User;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
});

it('approves an organizer via the table action', function () {
    $organizer = Organizer::factory()->create(['verification_status' => OrganizerVerificationStatus::Pending]);

    livewire(ListOrganizers::class)
        ->callTableAction('approve', $organizer);

    expect($organizer->fresh()->verification_status)->toBe(OrganizerVerificationStatus::Approved);
});

it('publishes a pending event via the table action', function () {
    $event = Event::factory()->create(['status' => EventStatus::PendingReview]);

    livewire(ListEvents::class)
        ->callTableAction('publish', $event);

    expect($event->fresh()->status)->toBe(EventStatus::Published);
});
```

(If `Pest\Livewire\livewire` is unavailable, add `pestphp/pest-plugin-livewire` via `composer require pestphp/pest-plugin-livewire --dev`, or use `Livewire\Livewire::test(...)`. Confirm the action-calling API name in Filament 5 — it may be `callTableAction` or `callAction`; check `php artisan about` / Filament docs and adapt.)

- [ ] **Step 2: Run — expect FAIL.**

- [ ] **Step 3: Add actions to the tables.** In `OrganizersTable.php`, add Filament table actions "approve" and "reject" (visible when pending) that call `OrganizerApprovalService`. In `EventsTable.php`, add "publish" and "reject" actions (visible when pending_review) calling `EventPublishingService`. Use Filament 5 `Filament\Actions\Action` in the table `->recordActions([...])` (confirm the exact API in the generated table file and Filament 5 — actions attach via `->actions([...])` or `->recordActions([...])`). Example action:

```php
use App\Services\OrganizerApprovalService;
use App\Enums\OrganizerVerificationStatus;
use Filament\Actions\Action;

Action::make('approve')
    ->visible(fn ($record) => $record->verification_status === OrganizerVerificationStatus::Pending)
    ->requiresConfirmation()
    ->action(fn ($record) => app(OrganizerApprovalService::class)->approve($record)),
```

Adapt the import/namespace to what the generated table file already uses (Filament 5 may use `Filament\Tables\Actions\Action` — match the existing file's imports).

- [ ] **Step 4: Run filter (PASS), full suite, pint clean.**

- [ ] **Step 5: Commit**

```bash
git add app/Filament/Resources/Organizers/Tables/OrganizersTable.php app/Filament/Resources/Events/Tables/EventsTable.php tests/Feature/FilamentModerationTest.php composer.json composer.lock
git commit -m "feat(admin): moderation actions for organizer approval and event publishing"
```

---

### Task 7: Public pages (event listing, event detail, organizer profile)

**Files:**
- Create: `app/Http/Controllers/Public/EventController.php`, `app/Http/Controllers/Public/OrganizerController.php`
- Create: `resources/js/Pages/Public/Events/Index.vue`, `resources/js/Pages/Public/Events/Show.vue`, `resources/js/Pages/Public/Organizers/Show.vue`
- Modify: `routes/web.php`
- Test: `tests/Feature/PublicPagesTest.php`

- [ ] **Step 1: Failing test** — `tests/Feature/PublicPagesTest.php`:

```php
<?php

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Organizer;

it('lists only published events publicly', function () {
    Event::factory()->published()->create(['title' => 'Visible']);
    Event::factory()->create(['status' => EventStatus::Draft, 'title' => 'Hidden']);

    $this->get('/events')->assertSuccessful();
});

it('shows a published event detail page', function () {
    $event = Event::factory()->published()->create();
    $this->get("/events/{$event->slug}")->assertSuccessful();
});

it('returns 404 for a non-published event detail', function () {
    $event = Event::factory()->create(['status' => EventStatus::Draft]);
    $this->get("/events/{$event->slug}")->assertNotFound();
});

it('shows an organizer public profile', function () {
    $organizer = Organizer::factory()->approved()->create();
    $this->get("/organizers/{$organizer->slug}")->assertSuccessful();
});
```

- [ ] **Step 2: Run — expect FAIL.**

- [ ] **Step 3: Controllers** — `Public\EventController@index` paginates `Event::published()->with(['organizer','venue'])->orderBy('start_date')`; `@show` finds by slug scoped to published (`Event::published()->where('slug',$slug)->firstOrFail()` → 404 when not published) with relations loaded; `Public\OrganizerController@show` finds approved organizer by slug with published events. All render Inertia pages.

- [ ] **Step 4: Vue pages** — `Public/Events/Index.vue` (list of event cards: title, organizer, start_date, link to detail), `Public/Events/Show.vue` (full detail incl. a "Zum Veranstalter" link to `event.booking_url`), `Public/Organizers/Show.vue` (profile + their published events). Minimal, typed, Tailwind-styled following the Breeze welcome page conventions.

- [ ] **Step 5: Routes** — in `routes/web.php` (public, no auth):

```php
use App\Http\Controllers\Public\EventController as PublicEventController;
use App\Http\Controllers\Public\OrganizerController as PublicOrganizerController;

Route::get('/events', [PublicEventController::class, 'index'])->name('events.index');
Route::get('/events/{slug}', [PublicEventController::class, 'show'])->name('events.show');
Route::get('/organizers/{slug}', [PublicOrganizerController::class, 'show'])->name('organizers.show');
```

- [ ] **Step 6: Run filter (PASS), full suite, pint clean, `npm run build`.**

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Public resources/js/Pages/Public routes/web.php tests/Feature/PublicPagesTest.php
git commit -m "feat(public): published event listing/detail and organizer profile pages"
```

---

## Self-Review (after all tasks)

- [ ] Lifecycle service guards transitions (draft→pending→published/rejected, published→archived); invalid transitions throw.
- [ ] OrganizerApprovalService + Filament approve/reject; only approved organizers' published events show publicly.
- [ ] Policies enforce owner-or-admin on events/organizers; cross-organizer actions forbidden (403).
- [ ] Public pages expose ONLY published events / approved organizers (draft → 404).
- [ ] `php artisan test` fully green; `vendor/bin/pint --test` clean; `npm run build` succeeds.
- [ ] No search/geosearch, favorites, or tracking yet (Sprint 4+).
