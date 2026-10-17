<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One title of a list. "weight" is its share of the articles over time
 * (weights 1, 1, 2 = 25%, 25%, 50%); 0 = paused.
 */
class Title extends Model
{
    public const MAX_WEIGHT = 1000;

    protected $fillable = ['title_list_id', 'title', 'weight', 'used_count', 'last_used_at'];

    protected function casts(): array
    {
        return [
            'weight' => 'float',
            'last_used_at' => 'datetime',
        ];
    }

    public function titleList(): BelongsTo
    {
        return $this->belongsTo(TitleList::class);
    }
}
