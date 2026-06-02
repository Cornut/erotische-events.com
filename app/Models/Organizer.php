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
