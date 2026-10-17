<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Article extends Model
{
    use HasUuids;

    public const PENDING = 'pending';

    public const GENERATING = 'generating';

    public const READY = 'ready';

    public const FAILED = 'failed';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'keywords' => 'array',
            'cost' => 'decimal:4',
            'generated_at' => 'datetime',
        ];
    }

    /** Only "uuid" is a UUID column; "id" stays an integer. */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ArticleImage::class)->orderBy('position');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    public function textAgent(): BelongsTo
    {
        return $this->belongsTo(AiAgent::class, 'text_agent_id');
    }

    public function imageAgent(): BelongsTo
    {
        return $this->belongsTo(AiAgent::class, 'image_agent_id');
    }

    /** Images that were made successfully. */
    public function readyImages()
    {
        return $this->images->filter(fn ($i) => $i->path !== null)->values();
    }

    /**
     * Body HTML with the images placed in it: image 1 is the cover (not in the body),
     * the others go after evenly spaced paragraphs/sections.
     */
    public function bodyWithImages(): string
    {
        $body = (string) $this->body;
        $extra = $this->readyImages()->slice(1)->values();
        if ($extra->isEmpty()) {
            return $body;
        }

        $parts = preg_split('~(?<=</p>|</ul>|</ol>|</blockquote>)~i', $body);
        $slots = max(count($parts) - 1, 1);
        $step = max(intdiv($slots, $extra->count() + 1), 1);
        $out = '';
        $next = 0;
        foreach ($parts as $i => $part) {
            $out .= $part;
            if ($next < $extra->count() && $i + 1 === $step * ($next + 1) && $i < count($parts) - 1) {
                $out .= "\n".$extra[$next]->figureHtml()."\n";
                $next++;
            }
        }
        for (; $next < $extra->count(); $next++) {
            $out .= "\n".$extra[$next]->figureHtml();
        }

        return $out;
    }

    /** Plain text body, for platforms without HTML (Instagram, LinkedIn...). */
    public function plainText(): string
    {
        $html = preg_replace(['~<(h[1-6])[^>]*>~i', '~</(h[1-6]|p|li|blockquote)>~i', '~<li[^>]*>~i', '~<br\s*/?>~i'],
            ["\n\n", "\n", '• ', "\n"], (string) $this->body);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace("/\n{3,}/", "\n\n", preg_replace('/[ \t]+/', ' ', $text)));
    }

    public function hashtags(int $max = 8): string
    {
        return collect($this->keywords ?? [])->take($max)
            ->map(fn ($k) => '#'.preg_replace('/[^\p{L}\p{N}_]+/u', '', str_replace(' ', '_', (string) $k)))
            ->filter(fn ($t) => mb_strlen($t) > 1)->implode(' ');
    }

    public function isRtl(): bool
    {
        return in_array($this->language, ['fa', 'ar', 'he', 'ur', 'ps', 'ku'], true);
    }
}
