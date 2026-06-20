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
        'owner_user_id', 'company_name', 'legal_name', 'contact_name', 'email', 'phone',
        'website', 'impressum_url', 'social_links', 'description', 'logo', 'slug', 'verification_status',
        'category', 'street', 'postal_code', 'city', 'country', 'vat_id',
        'events_url', 'scrape_urls', 'last_scraped_at',
    ];

    /**
     * Event-listing seed URLs for auto-discovery (one per line).
     *
     * @return array<int, string>
     */
    public function eventUrls(): array
    {
        return $this->urlsFromMultiline($this->events_url);
    }

    /**
     * Curated listing/feed URLs (one per line) to scrape without AI.
     *
     * @return array<int, string>
     */
    public function scrapeUrls(): array
    {
        return $this->urlsFromMultiline($this->scrape_urls);
    }

    /**
     * @return array<int, string>
     */
    private function urlsFromMultiline(?string $value): array
    {
        return array_values(array_filter(
            array_map('trim', preg_split('/\r\n|\r|\n/', (string) $value) ?: []),
            fn (string $url) => $url !== '',
        ));
    }

    protected function casts(): array
    {
        return [
            'social_links' => 'array',
            'verification_status' => OrganizerVerificationStatus::class,
            'last_scraped_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /**
     * Storage directory (on the `public` disk) holding ALL of this organizer's
     * images, including the logo: `organizers/{slug}/`.
     *
     * Convention (noted for later): each event gets its own subdirectory under
     * the owning organizer, e.g. `organizers/{slug}/events/{event-slug}/`.
     */
    public function imageDirectory(): string
    {
        return "organizers/{$this->slug}";
    }

    public function venues(): HasMany
    {
        return $this->hasMany(Venue::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(EventClick::class);
    }
}
