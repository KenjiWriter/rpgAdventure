<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Map extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    protected $fillable = [
        'name',
        'min_level',
    ];

    public function monsters(): HasMany
    {
        return $this->hasMany(Monster::class);
    }

    public function characters(): HasMany
    {
        return $this->hasMany(Character::class, 'current_map_id');
    }
}
