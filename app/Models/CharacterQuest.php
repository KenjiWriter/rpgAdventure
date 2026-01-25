<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CharacterQuest extends Model
{
    protected $fillable = ['character_id', 'quest_id', 'progress', 'is_completed', 'is_claimed'];

    public function quest()
    {
        return $this->belongsTo(Quest::class);
    }

    public function character()
    {
        return $this->belongsTo(Character::class);
    }
}
