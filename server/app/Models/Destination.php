<?php

namespace App\Models;

use App\Publishing\PublisherRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** A saved publish target of a customer (a WordPress site, a Telegram channel...). */
class Destination extends Model
{
    protected $fillable = ['user_id', 'name', 'driver', 'config', 'is_active'];

    protected $hidden = ['config'];

    protected function casts(): array
    {
        return [
            'config' => 'encrypted:array',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class);
    }

    public function publisher(): \App\Publishing\Publisher
    {
        return app(PublisherRegistry::class)->get($this->driver);
    }
}
