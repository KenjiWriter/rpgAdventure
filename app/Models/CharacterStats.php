<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterStats extends Model
{
    protected $fillable = [
        'character_id',
        'strength',
        'dexterity',
        'intelligence',
        'vitality',
        'resistance_wind',
        'resistance_fire',
        'resistance_water',
        'resistance_earth',
        'attack_speed',
        'computed_stats',
    ];

    protected $casts = [
        'computed_stats' => 'array',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}
