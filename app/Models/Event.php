<?php

namespace App\Models;

use App\Enums\EventAccommodation;
use App\Enums\EventStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Event extends Model
{
    use HasFactory, Searchable, SoftDeletes;

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

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'event_category');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'event_tag');
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'event_teacher');
    }

    public function shouldBeSearchable(): bool
    {
        return $this->status === EventStatus::Published;
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        $this->loadMissing(['organizer', 'venue', 'categories', 'tags', 'teachers']);

        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'short_description' => $this->short_description,
            'long_description' => $this->long_description,
            'organizer' => $this->organizer?->company_name,
            'venue_city' => $this->venue?->city,
            'venue_country' => $this->venue?->country,
            'categories' => $this->categories->pluck('name_de')->all(),
            'tags' => $this->tags->pluck('name')->all(),
            'teachers' => $this->teachers->pluck('name')->all(),
            'start_date' => $this->start_date?->timestamp,
        ];

        if ($this->venue && $this->venue->latitude !== null && $this->venue->longitude !== null) {
            $data['_geo'] = [
                'lat' => (float) $this->venue->latitude,
                'lng' => (float) $this->venue->longitude,
            ];
        }

        return $data;
    }
}
