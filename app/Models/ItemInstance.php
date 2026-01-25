<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ItemInstance extends Model
{
    use HasUuids;

    protected $fillable = [
        'item_template_id',
        'owner_id',
        'owner_type',
        'slot_id',
        'upgrade_level',
        'bonuses',
    ];

    protected $casts = [
        'bonuses' => 'array',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ItemTemplate::class, 'item_template_id');
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }
}
