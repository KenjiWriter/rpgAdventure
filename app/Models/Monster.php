<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Monster extends Model
{
    protected $fillable = [
        'name',
        'hp',
        'min_dmg',
        'max_dmg',
        'speed',
        'element',
        'drops_json',
        'map_id',
    ];

    protected $casts = [
        'drops_json' => 'array',
    ];

    public function map(): BelongsTo
    {
        return $this->belongsTo(Map::class);
    }
}
