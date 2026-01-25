<?php

namespace App\Models;

use App\Enums\ItemType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemTemplate extends Model
{
    protected $fillable = [
        'name',
        'type',
        'base_stats',
        'min_level',
        'class_restriction',
    ];

    protected $casts = [
        'type' => ItemType::class,
        'base_stats' => 'array',
    ];

    public function instances(): HasMany
    {
        return $this->hasMany(ItemInstance::class);
    }
}
