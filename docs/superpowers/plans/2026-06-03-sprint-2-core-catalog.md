# Sprint 2 — Core Catalog Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax.

**Goal:** Build the catalog domain — organizers, venues, teachers, categories, tags, events, event_prices and their relations — with migrations, models, factories, the taxonomy seeder, and Filament 5 admin resources for curation.

**Architecture:** Eloquent models + migrations per `docs/03-database-schema.md`. Backed enums in `app/Enums`. Filament resources in `app/Filament/Resources` for admin curation. No public pages or search yet (Sprint 3/4). Models stay thin (relations/scopes/casts only).

**Tech Stack:** Laravel 13, Filament 5, Pest. Tests run on sqlite :memory: (already configured). 35 tests currently pass on `main`.

**Conventions:**
- Repo root: `/Users/comodo/Documents/sites/erotische-events.com/root`. Run all commands there. Branch created by the execution skill.
- Reference: `docs/03-database-schema.md` (field-level schema), `docs/08-category-taxonomy.md` (taxonomy).
- Each task: TDD where logic exists (write failing test → implement → pass), then full suite `php artisan test` green + `vendor/bin/pint --test` clean, then commit the listed files.
- All migrations use FKs, the enums below, soft deletes where the schema says so.
- Enums live in `app/Enums`, backed string enums.

---

### Task 1: Categories + taxonomy seeder

**Files:**
- Create: `database/migrations/2026_06_03_000001_create_categories_table.php`
- Create: `app/Models/Category.php`
- Create: `database/seeders/CategorySeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Test: `tests/Feature/CategoryTest.php`

- [ ] **Step 1: Write the failing test**

`tests/Feature/CategoryTest.php`:

```php
<?php

use App\Models\Category;

it('creates a parent category with children', function () {
    $bdsm = Category::create(['slug' => 'bdsm', 'name_de' => 'BDSM', 'name_en' => 'BDSM', 'position' => 12]);
    $shibari = Category::create(['slug' => 'shibari', 'name_de' => 'Shibari', 'name_en' => 'Shibari', 'parent_id' => $bdsm->id, 'position' => 1]);

    expect($shibari->parent->is($bdsm))->toBeTrue()
        ->and($bdsm->children->pluck('slug')->all())->toContain('shibari');
});

it('seeds the full taxonomy with bdsm as parent of shibari and kink', function () {
    $this->seed(\Database\Seeders\CategorySeeder::class);

    expect(Category::whereNull('parent_id')->count())->toBe(12);
    $bdsm = Category::where('slug', 'bdsm')->firstOrFail();
    expect(Category::where('parent_id', $bdsm->id)->pluck('slug')->all())
        ->toEqualCanonicalizing(['shibari', 'kink']);
    expect(Category::where('slug', 'tantra')->exists())->toBeTrue();
});
```

- [ ] **Step 2: Run it — expect FAIL** (`php artisan test --filter=CategoryTest`): Category model/table missing.

- [ ] **Step 3: Migration**

`database/migrations/2026_06_03_000001_create_categories_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name_de');
            $table->string('name_en');
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
```

- [ ] **Step 4: Model**

`app/Models/Category.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['slug', 'name_de', 'name_en', 'parent_id', 'position'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
```

- [ ] **Step 5: Seeder** — `database/seeders/CategorySeeder.php`. Seed the 12 top-level categories from `docs/08-category-taxonomy.md` with `bdsm` (position 12) as parent of `shibari` and `kink`. Read `docs/08-category-taxonomy.md` for exact slugs/names. Top-level slugs: tantra, conscious-relating, sacred-sexuality, sex-positive, retreat, festival, workshop, bodywork, mens-work, womens-work, lgbtq, bdsm. Children of bdsm: shibari, kink.

```php
<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $top = [
            ['slug' => 'tantra', 'name_de' => 'Tantra', 'name_en' => 'Tantra'],
            ['slug' => 'conscious-relating', 'name_de' => 'Conscious Relating', 'name_en' => 'Conscious Relating'],
            ['slug' => 'sacred-sexuality', 'name_de' => 'Sakrale Sexualität', 'name_en' => 'Sacred Sexuality'],
            ['slug' => 'sex-positive', 'name_de' => 'Sex Positive', 'name_en' => 'Sex Positive'],
            ['slug' => 'retreat', 'name_de' => 'Retreat', 'name_en' => 'Retreat'],
            ['slug' => 'festival', 'name_de' => 'Festival', 'name_en' => 'Festival'],
            ['slug' => 'workshop', 'name_de' => 'Workshop', 'name_en' => 'Workshop'],
            ['slug' => 'bodywork', 'name_de' => 'Körperarbeit', 'name_en' => 'Bodywork'],
            ['slug' => 'mens-work', 'name_de' => 'Männerarbeit', 'name_en' => "Men's Work"],
            ['slug' => 'womens-work', 'name_de' => 'Frauenarbeit', 'name_en' => "Women's Work"],
            ['slug' => 'lgbtq', 'name_de' => 'LGBTQ+', 'name_en' => 'LGBTQ+'],
            ['slug' => 'bdsm', 'name_de' => 'BDSM', 'name_en' => 'BDSM'],
        ];

        foreach ($top as $i => $data) {
            Category::updateOrCreate(['slug' => $data['slug']], $data + ['position' => $i + 1]);
        }

        $bdsm = Category::where('slug', 'bdsm')->firstOrFail();
        foreach ([['slug' => 'shibari', 'name_de' => 'Shibari', 'name_en' => 'Shibari'], ['slug' => 'kink', 'name_de' => 'Kink', 'name_en' => 'Kink']] as $i => $child) {
            Category::updateOrCreate(['slug' => $child['slug']], $child + ['parent_id' => $bdsm->id, 'position' => $i + 1]);
        }
    }
}
```

Then add `$this->call(CategorySeeder::class);` to `database/seeders/DatabaseSeeder.php` `run()` (after the admin user `firstOrCreate`). Add `use Database\Seeders\CategorySeeder;` only if needed (same namespace, so `$this->call(CategorySeeder::class)` works without import).

- [ ] **Step 6: Run `php artisan test --filter=CategoryTest` — expect PASS.** Then full suite `php artisan test` (expect 37) and `vendor/bin/pint --test` clean.

- [ ] **Step 7: Commit**

```bash
git add database/migrations/2026_06_03_000001_create_categories_table.php app/Models/Category.php database/seeders/CategorySeeder.php database/seeders/DatabaseSeeder.php tests/Feature/CategoryTest.php
git commit -m "feat(catalog): categories with hierarchy and taxonomy seeder"
```

---

### Task 2: Tags

**Files:**
- Create: `database/migrations/2026_06_03_000002_create_tags_table.php`
- Create: `app/Models/Tag.php`
- Test: `tests/Feature/TagTest.php`

- [ ] **Step 1: Failing test** — `tests/Feature/TagTest.php`:

```php
<?php

use App\Models\Tag;

it('creates a tag with a unique slug', function () {
    $tag = Tag::create(['name' => 'Beginner Friendly', 'slug' => 'beginner-friendly']);
    expect($tag->slug)->toBe('beginner-friendly');
});
```

- [ ] **Step 2: Run — expect FAIL.**

- [ ] **Step 3: Migration** `2026_06_03_000002_create_tags_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
```

- [ ] **Step 4: Model** `app/Models/Tag.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $fillable = ['name', 'slug'];
}
```

- [ ] **Step 5: Run `--filter=TagTest` (PASS), full suite (expect 38), pint clean.**

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_06_03_000002_create_tags_table.php app/Models/Tag.php tests/Feature/TagTest.php
git commit -m "feat(catalog): tags model"
```

---

### Task 3: Organizers

**Files:**
- Create: `app/Enums/OrganizerVerificationStatus.php`
- Create: `database/migrations/2026_06_03_000003_create_organizers_table.php`
- Create: `app/Models/Organizer.php`
- Create: `database/factories/OrganizerFactory.php`
- Test: `tests/Feature/OrganizerTest.php`

- [ ] **Step 1: Failing test** — `tests/Feature/OrganizerTest.php`:

```php
<?php

use App\Enums\OrganizerVerificationStatus;
use App\Models\Organizer;
use App\Models\User;

it('creates an organizer owned by a user with pending status by default', function () {
    $user = User::factory()->create();
    $organizer = Organizer::factory()->create(['owner_user_id' => $user->id]);

    expect($organizer->owner->is($user))->toBeTrue()
        ->and($organizer->verification_status)->toBe(OrganizerVerificationStatus::Pending)
        ->and($organizer->social_links)->toBeArray();
});

it('soft-deletes an organizer', function () {
    $organizer = Organizer::factory()->create();
    $organizer->delete();
    expect(Organizer::count())->toBe(0)->and(Organizer::withTrashed()->count())->toBe(1);
});
```

- [ ] **Step 2: Run — expect FAIL.**

- [ ] **Step 3: Enum** `app/Enums/OrganizerVerificationStatus.php`:

```php
<?php

namespace App\Enums;

enum OrganizerVerificationStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
```

- [ ] **Step 4: Migration** `2026_06_03_000003_create_organizers_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('company_name');
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->json('social_links')->nullable();
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('slug')->unique();
            $table->string('verification_status')->default('pending');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['verification_status', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizers');
    }
};
```

- [ ] **Step 5: Model** `app/Models/Organizer.php`:

```php
<?php

namespace App\Models;

use App\Enums\OrganizerVerificationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organizer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'owner_user_id', 'company_name', 'contact_name', 'email', 'phone',
        'website', 'social_links', 'description', 'logo', 'slug', 'verification_status',
    ];

    protected function casts(): array
    {
        return [
            'social_links' => 'array',
            'verification_status' => OrganizerVerificationStatus::class,
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function venues(): HasMany
    {
        return $this->hasMany(Venue::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
```

(Note: `venues()`/`events()` reference models created in later tasks — that is fine, the relations are only resolved when called, and those tasks land before any test exercises them.)

- [ ] **Step 6: Factory** `database/factories/OrganizerFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Enums\OrganizerVerificationStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrganizerFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'owner_user_id' => User::factory(),
            'company_name' => $name,
            'contact_name' => fake()->name(),
            'email' => fake()->companyEmail(),
            'website' => fake()->url(),
            'social_links' => [],
            'description' => fake()->paragraph(),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'verification_status' => OrganizerVerificationStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(['verification_status' => OrganizerVerificationStatus::Approved]);
    }
}
```

- [ ] **Step 7: Run `--filter=OrganizerTest` (PASS), full suite (expect 40), pint clean.**

- [ ] **Step 8: Commit**

```bash
git add app/Enums/OrganizerVerificationStatus.php database/migrations/2026_06_03_000003_create_organizers_table.php app/Models/Organizer.php database/factories/OrganizerFactory.php tests/Feature/OrganizerTest.php
git commit -m "feat(catalog): organizers with verification status"
```

---

### Task 4: Venues

**Files:**
- Create: `database/migrations/2026_06_03_000004_create_venues_table.php`
- Create: `app/Models/Venue.php`
- Create: `database/factories/VenueFactory.php`
- Test: `tests/Feature/VenueTest.php`

- [ ] **Step 1: Failing test** — `tests/Feature/VenueTest.php`:

```php
<?php

use App\Models\Organizer;
use App\Models\Venue;

it('creates a venue belonging to an organizer with coordinates', function () {
    $organizer = Organizer::factory()->create();
    $venue = Venue::factory()->create(['organizer_id' => $organizer->id, 'latitude' => 52.52, 'longitude' => 13.405]);

    expect($venue->organizer->is($organizer))->toBeTrue()
        ->and((float) $venue->latitude)->toBe(52.52)
        ->and($venue->images)->toBeArray();
});
```

- [ ] **Step 2: Run — expect FAIL.**

- [ ] **Step 3: Migration** `2026_06_03_000004_create_venues_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizer_id')->constrained('organizers')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('street')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->string('country', 2)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->json('images')->nullable();
            $table->string('contact_info')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['country', 'city']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venues');
    }
};
```

- [ ] **Step 4: Model** `app/Models/Venue.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Venue extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organizer_id', 'name', 'slug', 'description', 'street', 'postal_code',
        'city', 'region', 'country', 'latitude', 'longitude', 'images', 'contact_info',
    ];

    protected function casts(): array
    {
        return [
            'images' => 'array',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }
}
```

- [ ] **Step 5: Factory** `database/factories/VenueFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Organizer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class VenueFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->company().' Space';

        return [
            'organizer_id' => Organizer::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'description' => fake()->paragraph(),
            'street' => fake()->streetAddress(),
            'postal_code' => fake()->postcode(),
            'city' => fake()->city(),
            'region' => fake()->state(),
            'country' => fake()->countryCode(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'images' => [],
        ];
    }
}
```

- [ ] **Step 6: Run `--filter=VenueTest` (PASS), full suite (expect 41), pint clean.**

- [ ] **Step 7: Commit**

```bash
git add database/migrations/2026_06_03_000004_create_venues_table.php app/Models/Venue.php database/factories/VenueFactory.php tests/Feature/VenueTest.php
git commit -m "feat(catalog): venues with coordinates"
```

---

### Task 5: Teachers

**Files:**
- Create: `database/migrations/2026_06_03_000005_create_teachers_table.php`
- Create: `app/Models/Teacher.php`
- Create: `database/factories/TeacherFactory.php`
- Test: `tests/Feature/TeacherTest.php`

- [ ] **Step 1: Failing test** — `tests/Feature/TeacherTest.php`:

```php
<?php

use App\Models\Teacher;

it('creates a teacher with links as array', function () {
    $teacher = Teacher::factory()->create(['links' => ['https://example.com']]);
    expect($teacher->links)->toBeArray()->toContain('https://example.com');
});
```

- [ ] **Step 2: Run — expect FAIL.**

- [ ] **Step 3: Migration** `2026_06_03_000005_create_teachers_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('bio')->nullable();
            $table->string('photo')->nullable();
            $table->json('links')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
```

- [ ] **Step 4: Model** `app/Models/Teacher.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'bio', 'photo', 'links'];

    protected function casts(): array
    {
        return ['links' => 'array'];
    }
}
```

- [ ] **Step 5: Factory** `database/factories/TeacherFactory.php`:

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TeacherFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->name();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'bio' => fake()->paragraph(),
            'links' => [],
        ];
    }
}
```

- [ ] **Step 6: Run `--filter=TeacherTest` (PASS), full suite (expect 42), pint clean.**

- [ ] **Step 7: Commit**

```bash
git add database/migrations/2026_06_03_000005_create_teachers_table.php app/Models/Teacher.php database/factories/TeacherFactory.php tests/Feature/TeacherTest.php
git commit -m "feat(catalog): teachers model"
```

---

### Task 6: Events + event_prices

**Files:**
- Create: `app/Enums/EventStatus.php`, `app/Enums/EventAccommodation.php`, `app/Enums/EventPriceType.php`
- Create: `database/migrations/2026_06_03_000006_create_events_table.php`
- Create: `database/migrations/2026_06_03_000007_create_event_prices_table.php`
- Create: `app/Models/Event.php`, `app/Models/EventPrice.php`
- Create: `database/factories/EventFactory.php`, `database/factories/EventPriceFactory.php`
- Test: `tests/Feature/EventTest.php`

- [ ] **Step 1: Failing test** — `tests/Feature/EventTest.php`:

```php
<?php

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventPrice;
use App\Models\Organizer;
use App\Models\Venue;

it('creates a draft event with json audience/languages and relations', function () {
    $organizer = Organizer::factory()->create();
    $venue = Venue::factory()->create(['organizer_id' => $organizer->id]);
    $event = Event::factory()->create([
        'organizer_id' => $organizer->id,
        'venue_id' => $venue->id,
        'audience' => ['couples', 'everyone'],
        'languages' => ['de', 'en'],
    ]);

    expect($event->status)->toBe(EventStatus::Draft)
        ->and($event->audience)->toBe(['couples', 'everyone'])
        ->and($event->organizer->is($organizer))->toBeTrue()
        ->and($event->venue->is($venue))->toBeTrue();
});

it('has many prices and soft-deletes', function () {
    $event = Event::factory()->create();
    EventPrice::factory()->create(['event_id' => $event->id]);

    expect($event->prices)->toHaveCount(1);

    $event->delete();
    expect(Event::count())->toBe(0)->and(Event::withTrashed()->count())->toBe(1);
});

it('scopes published events', function () {
    Event::factory()->create(['status' => EventStatus::Draft]);
    Event::factory()->create(['status' => EventStatus::Published]);

    expect(Event::published()->count())->toBe(1);
});
```

- [ ] **Step 2: Run — expect FAIL.**

- [ ] **Step 3: Enums**

`app/Enums/EventStatus.php`:

```php
<?php

namespace App\Enums;

enum EventStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Published = 'published';
    case Rejected = 'rejected';
    case Archived = 'archived';
}
```

`app/Enums/EventAccommodation.php`:

```php
<?php

namespace App\Enums;

enum EventAccommodation: string
{
    case None = 'none';
    case Optional = 'optional';
    case Mandatory = 'mandatory';
    case External = 'external';
}
```

`app/Enums/EventPriceType.php`:

```php
<?php

namespace App\Enums;

enum EventPriceType: string
{
    case EarlyBird = 'early_bird';
    case Regular = 'regular';
    case LateBird = 'late_bird';
}
```

- [ ] **Step 4: Migrations**

`2026_06_03_000006_create_events_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizer_id')->constrained('organizers')->cascadeOnDelete();
            $table->foreignId('venue_id')->nullable()->constrained('venues')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('short_description')->nullable();
            $table->longText('long_description')->nullable();
            $table->string('main_image')->nullable();
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->string('status')->default('draft');
            $table->json('audience')->nullable();
            $table->unsignedInteger('min_participants')->nullable();
            $table->unsignedInteger('max_participants')->nullable();
            $table->json('languages')->nullable();
            $table->string('accommodation')->default('none');
            $table->string('currency', 3)->default('EUR');
            $table->string('booking_url');
            $table->string('source_url')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'start_date', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
```

`2026_06_03_000007_create_event_prices_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('type')->default('regular');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('EUR');
            $table->date('valid_until')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_prices');
    }
};
```

- [ ] **Step 5: Models**

`app/Models/Event.php`:

```php
<?php

namespace App\Models;

use App\Enums\EventAccommodation;
use App\Enums\EventStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organizer_id', 'venue_id', 'title', 'slug', 'short_description', 'long_description',
        'main_image', 'start_date', 'end_date', 'status', 'audience', 'min_participants',
        'max_participants', 'languages', 'accommodation', 'currency', 'booking_url', 'source_url',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'status' => EventStatus::class,
            'accommodation' => EventAccommodation::class,
            'audience' => 'array',
            'languages' => 'array',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', EventStatus::Published);
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(EventPrice::class);
    }
}
```

`app/Models/EventPrice.php`:

```php
<?php

namespace App\Models;

use App\Enums\EventPriceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventPrice extends Model
{
    use HasFactory;

    protected $fillable = ['event_id', 'type', 'amount', 'currency', 'valid_until'];

    protected function casts(): array
    {
        return [
            'type' => EventPriceType::class,
            'amount' => 'decimal:2',
            'valid_until' => 'date',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
```

- [ ] **Step 6: Factories**

`database/factories/EventFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Models\Organizer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class EventFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->sentence(4);
        $start = fake()->dateTimeBetween('+1 week', '+3 months');

        return [
            'organizer_id' => Organizer::factory(),
            'venue_id' => null,
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 99999),
            'short_description' => fake()->sentence(),
            'long_description' => fake()->paragraphs(3, true),
            'start_date' => $start,
            'end_date' => (clone $start)->modify('+2 days'),
            'status' => EventStatus::Draft,
            'audience' => ['everyone'],
            'languages' => ['de'],
            'accommodation' => 'none',
            'currency' => 'EUR',
            'booking_url' => fake()->url(),
        ];
    }

    public function published(): static
    {
        return $this->state(['status' => EventStatus::Published]);
    }
}
```

`database/factories/EventPriceFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Enums\EventPriceType;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventPriceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'type' => EventPriceType::Regular,
            'amount' => fake()->randomFloat(2, 50, 500),
            'currency' => 'EUR',
        ];
    }
}
```

- [ ] **Step 7: Run `--filter=EventTest` (PASS), full suite (expect 45), pint clean.**

- [ ] **Step 8: Commit**

```bash
git add app/Enums/EventStatus.php app/Enums/EventAccommodation.php app/Enums/EventPriceType.php database/migrations/2026_06_03_000006_create_events_table.php database/migrations/2026_06_03_000007_create_event_prices_table.php app/Models/Event.php app/Models/EventPrice.php database/factories/EventFactory.php database/factories/EventPriceFactory.php tests/Feature/EventTest.php
git commit -m "feat(catalog): events and event prices with enums and relations"
```

---

### Task 7: Many-to-many pivots (categories, tags, teachers)

**Files:**
- Create: `database/migrations/2026_06_03_000008_create_event_pivot_tables.php`
- Modify: `app/Models/Event.php`, `app/Models/Category.php`, `app/Models/Tag.php`, `app/Models/Teacher.php`
- Test: `tests/Feature/EventRelationsTest.php`

- [ ] **Step 1: Failing test** — `tests/Feature/EventRelationsTest.php`:

```php
<?php

use App\Models\Category;
use App\Models\Event;
use App\Models\Tag;
use App\Models\Teacher;

it('attaches categories, tags and teachers to an event', function () {
    $event = Event::factory()->create();
    $category = Category::create(['slug' => 'tantra', 'name_de' => 'Tantra', 'name_en' => 'Tantra']);
    $tag = Tag::create(['name' => 'Couples', 'slug' => 'couples']);
    $teacher = Teacher::factory()->create();

    $event->categories()->attach($category);
    $event->tags()->attach($tag);
    $event->teachers()->attach($teacher);

    expect($event->categories)->toHaveCount(1)
        ->and($event->tags)->toHaveCount(1)
        ->and($event->teachers)->toHaveCount(1)
        ->and($category->events)->toHaveCount(1);
});
```

- [ ] **Step 2: Run — expect FAIL.**

- [ ] **Step 3: Pivot migration** `2026_06_03_000008_create_event_pivot_tables.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_category', function (Blueprint $table) {
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->primary(['event_id', 'category_id']);
        });

        Schema::create('event_tag', function (Blueprint $table) {
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->primary(['event_id', 'tag_id']);
        });

        Schema::create('event_teacher', function (Blueprint $table) {
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->primary(['event_id', 'teacher_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_teacher');
        Schema::dropIfExists('event_tag');
        Schema::dropIfExists('event_category');
    }
};
```

- [ ] **Step 4: Add relations**

In `app/Models/Event.php` add `use Illuminate\Database\Eloquent\Relations\BelongsToMany;` and these methods:

```php
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class);
    }
```

In `app/Models/Category.php`, `app/Models/Tag.php`, `app/Models/Teacher.php` add (with the `use ...BelongsToMany;` import):

```php
    public function events(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Event::class);
    }
```

- [ ] **Step 5: Run `--filter=EventRelationsTest` (PASS), full suite (expect 46), pint clean.**

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_06_03_000008_create_event_pivot_tables.php app/Models/Event.php app/Models/Category.php app/Models/Tag.php app/Models/Teacher.php tests/Feature/EventRelationsTest.php
git commit -m "feat(catalog): event many-to-many relations (categories, tags, teachers)"
```

---

### Task 8: Filament admin resources for catalog curation

**Files:**
- Create (generated): `app/Filament/Resources/**` for Organizer, Venue, Teacher, Category, Tag, Event
- Test: `tests/Feature/FilamentResourcesTest.php`

- [ ] **Step 1: Generate resources**

```bash
php artisan make:filament-resource Organizer --generate --no-interaction
php artisan make:filament-resource Venue --generate --no-interaction
php artisan make:filament-resource Teacher --generate --no-interaction
php artisan make:filament-resource Category --generate --no-interaction
php artisan make:filament-resource Tag --generate --no-interaction
php artisan make:filament-resource Event --generate --no-interaction
```

(`--generate` infers form/table columns from the migrations. If Filament 5's generator prompts despite `--no-interaction`, pass the model name explicitly and accept defaults.)

- [ ] **Step 2: Write the access test** — `tests/Feature/FilamentResourcesTest.php`:

```php
<?php

use App\Enums\UserRole;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
});

it('lists each catalog resource index for an admin', function (string $path) {
    $this->actingAs($this->admin)->get($path)->assertSuccessful();
})->with([
    '/admin/organizers',
    '/admin/venues',
    '/admin/teachers',
    '/admin/categories',
    '/admin/tags',
    '/admin/events',
]);
```

- [ ] **Step 3: Run `php artisan test --filter=FilamentResourcesTest`.** If a resource path differs from the pluralized guess (Filament derives the slug from the model), run `php artisan route:list --path=admin` to get the actual slugs and correct the test's dataset to match. Expect PASS once paths are right.

- [ ] **Step 4: Full suite `php artisan test` (expect 52: 46 + 6 dataset cases) and `vendor/bin/pint --test`.** Filament-generated resources should pass Pint; if any generated file is flagged, run `vendor/bin/pint app/Filament` and re-check.

- [ ] **Step 5: Commit**

```bash
git add app/Filament tests/Feature/FilamentResourcesTest.php
git commit -m "feat(admin): Filament resources for catalog curation"
```

---

## Self-Review (after all tasks)

- [ ] Every schema table from `docs/03-database-schema.md` present: users (prior), organizers, venues, teachers, categories, tags, events, event_prices, event_category, event_tag, event_teacher, favorites (favorites is Sprint 4 — NOT this sprint).
- [ ] All enums match the schema values exactly (EventStatus, EventAccommodation, EventPriceType, OrganizerVerificationStatus).
- [ ] `php artisan test` fully green; `vendor/bin/pint --test` clean.
- [ ] Taxonomy seeder matches `docs/08-category-taxonomy.md` (12 top-level + bdsm→shibari,kink).
- [ ] No over-build: no public pages, no search/Scout, no favorites, no tracking — those are later sprints.
