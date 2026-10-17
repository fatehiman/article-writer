<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tone extends Model
{
    protected $fillable = ['name', 'labels', 'instruction', 'is_active', 'sort'];

    protected function casts(): array
    {
        return [
            'labels' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /** Name in the current UI language, falling back to the English name. */
    public function label(): string
    {
        return ($this->labels[app()->getLocale()] ?? '') ?: $this->name;
    }
}
