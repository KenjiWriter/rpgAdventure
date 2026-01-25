<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quest extends Model
{
    protected $fillable = [
        'title',
        'description',
        'objective_type',
        'objective_target',
        'objective_count',
        'reward_gold',
        'reward_xp'
    ];
    public function characterQuests()
    {
        return $this->hasMany(CharacterQuest::class);
    }
}
