<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'bio', 'photo', 'links'];

    protected function casts(): array
    {
        return ['links' => 'array'];
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_teacher');
    }
}
