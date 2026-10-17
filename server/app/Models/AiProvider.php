<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiProvider extends Model
{
    protected $fillable = ['name', 'driver', 'base_url', 'api_key', 'is_active'];

    protected $hidden = ['api_key'];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'is_active' => 'boolean',
        ];
    }

    public function agents(): HasMany
    {
        return $this->hasMany(AiAgent::class);
    }

    public function driverLabel(): string
    {
        return config("ai.drivers.{$this->driver}.label", $this->driver);
    }

    /** "sk-a…9f2c" - enough to recognise a key, never the whole key. */
    public function maskedKey(): string
    {
        $key = (string) $this->api_key;

        return $key === '' ? '' : mb_substr($key, 0, 4).'…'.mb_substr($key, -4);
    }
}
