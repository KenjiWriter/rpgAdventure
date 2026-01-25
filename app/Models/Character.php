<?php

namespace App\Models;

use App\Enums\CharacterClass;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Character extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'name',
        'class',
        'level',
        'experience',
        'gold',
        'stat_points',
        'current_map_id',
    ];

    protected $casts = [
        'class' => CharacterClass::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stats(): HasOne
    {
        return $this->hasOne(CharacterStats::class);
    }

    public function items(): MorphMany
    {
        return $this->morphMany(ItemInstance::class, 'owner');
    }

    public function map(): BelongsTo
    {
        return $this->belongsTo(Map::class, 'current_map_id');
    }
}
