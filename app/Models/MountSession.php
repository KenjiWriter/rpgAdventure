<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MountSession extends Model
{
    use HasUuids;

    protected $fillable = [
        'character_id',
        'mount_type',
        'rented_at',
        'expires_at',
    ];

    protected $casts = [
        'rented_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}
