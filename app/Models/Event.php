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
