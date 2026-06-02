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
