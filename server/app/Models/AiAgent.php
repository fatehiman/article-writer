<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A model that customers can pick, e.g. "DeepSeek Flash" or "Qwen image". */
class AiAgent extends Model
{
    protected $fillable = [
        'ai_provider_id', 'name', 'kind', 'model', 'description', 'options', 'price', 'price_unit', 'is_active', 'sort',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'price' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'ai_provider_id');
    }

    /** Active agent of a kind on an active provider - what customers may choose. */
    public function scopeUsable(Builder $query, string $kind): Builder
    {
        return $query->where('kind', $kind)->where('is_active', true)
            ->whereHas('provider', fn ($q) => $q->where('is_active', true))
            ->orderBy('sort')->orderBy('name');
    }

    public function option(string $key, mixed $default = null): mixed
    {
        return data_get($this->options ?? [], $key, $default);
    }

    public function priceLabel(): string
    {
        $price = (float) $this->price;
        if ($price <= 0) {
            return __('ui.free');
        }

        return money($price).' / '.__('ui.unit_'.$this->price_unit);
    }
}
