<?php

namespace App\Models;

use App\Support\Schedule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/** A customer's article generator: what to write, when, and where to send it. */
class Service extends Model
{
    public const MAX_IMAGES = 10;

    public const MAX_RETRIES = 10;

    protected $fillable = [
        'name', 'is_active', 'title_list_id', 'categories', 'word_count', 'tone_id', 'language',
        'instructions', 'text_agent_id', 'image_agent_id', 'images', 'timezone', 'schedule_times', 'schedule_type',
        'schedule_days', 'webhook_url', 'webhook_secret', 'webhook_embed_images', 'retry_limit', 'retry_delay',
    ];

    protected $hidden = ['api_token', 'api_token_hash', 'webhook_secret'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'webhook_embed_images' => 'boolean',
            'categories' => 'array',
            'images' => 'array',
            'schedule_times' => 'array',
            'schedule_days' => 'array',
            'webhook_secret' => 'encrypted',
            'api_token' => 'encrypted',
            'next_run_at' => 'datetime',
            'last_run_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function titleList(): BelongsTo
    {
        return $this->belongsTo(TitleList::class);
    }

    public function tone(): BelongsTo
    {
        return $this->belongsTo(Tone::class);
    }

    public function textAgent(): BelongsTo
    {
        return $this->belongsTo(AiAgent::class, 'text_agent_id');
    }

    public function imageAgent(): BelongsTo
    {
        return $this->belongsTo(AiAgent::class, 'image_agent_id');
    }

    public function destinations(): BelongsToMany
    {
        return $this->belongsToMany(Destination::class);
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    /** Make a new API token. The plain token is stored encrypted so the owner can view it. */
    public function rotateToken(): string
    {
        $token = 'aw_'.Str::random(40);
        $this->api_token = $token;
        $this->api_token_hash = hash('sha256', $token);

        return $token;
    }

    public function rotateWebhookSecret(): void
    {
        $this->webhook_secret = 'whsec_'.Str::random(32);
    }

    public static function findByToken(string $token): ?self
    {
        return $token === '' ? null : static::query()->where('api_token_hash', hash('sha256', $token))->first();
    }

    /** @return list<int> image_size ids, at most MAX_IMAGES */
    public function imageSizeIds(): array
    {
        return array_slice(array_values(array_map('intval', $this->images ?? [])), 0, self::MAX_IMAGES);
    }

    public function computeNextRun(?Carbon $after = null): ?Carbon
    {
        return Schedule::next(
            $this->schedule_times ?? [],
            $this->schedule_type,
            $this->schedule_days ?? [],
            $this->timezone ?: 'UTC',
            $after ?? now(),
        );
    }

    public function refreshNextRun(): void
    {
        $this->next_run_at = $this->is_active ? $this->computeNextRun() : null;
    }
}
