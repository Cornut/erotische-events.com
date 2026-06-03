<?php

namespace App\Models;

use App\Enums\DeviceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventClick extends Model
{
    public $timestamps = false;

    protected $fillable = ['event_id', 'organizer_id', 'clicked_at', 'country', 'device_type', 'referrer'];

    protected function casts(): array
    {
        return [
            'clicked_at' => 'datetime',
            'device_type' => DeviceType::class,
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }
}
