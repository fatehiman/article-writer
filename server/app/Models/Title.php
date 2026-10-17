<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Title extends Model
{
    protected $fillable = ['title_list_id', 'title', 'used_count', 'last_used_at'];

    protected function casts(): array
    {
        return ['last_used_at' => 'datetime'];
    }

    public function titleList(): BelongsTo
    {
        return $this->belongsTo(TitleList::class);
    }
}
