<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImageSize extends Model
{
    protected $fillable = ['name', 'group', 'width', 'height', 'is_active', 'sort'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function label(): string
    {
        return "{$this->name} ({$this->width}×{$this->height})";
    }
}
