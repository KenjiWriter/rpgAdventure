<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CharacterLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'character_id',
        'type',
        'message',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function character(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}
