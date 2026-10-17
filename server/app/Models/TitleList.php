<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TitleList extends Model
{
    protected $fillable = ['user_id', 'name'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function titles(): HasMany
    {
        return $this->hasMany(Title::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /** Lines of text -> rows for syncRows(), all with the same weight. */
    public static function rowsFromText(string $text, float $weight = 1): array
    {
        return collect(preg_split('/\R/u', $text))
            ->map(fn ($t) => ['title' => trim((string) $t), 'weight' => $weight])
            ->filter(fn ($r) => $r['title'] !== '')->values()->all();
    }

    /**
     * Make the list hold exactly these rows: [['id' => ?int, 'title' => string, 'weight' => float], ...].
     * Rows with an id of this list are updated (their usage count stays), rows without an id are
     * added, missing titles are deleted. A repeated title (ignoring case) is kept once.
     * Returns the number of titles in the list.
     */
    public function syncRows(array $rows): int
    {
        $existing = $this->titles()->get()->keyBy('id');
        $byText = $existing->keyBy(fn ($t) => mb_strtolower($t->title));
        $seen = [];
        $keep = [];

        foreach ($rows as $row) {
            $title = trim(mb_substr(trim((string) ($row['title'] ?? '')), 0, 500));
            $key = mb_strtolower($title);
            if ($title === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $weight = max(0, min(Title::MAX_WEIGHT, round((float) ($row['weight'] ?? 1), 2)));

            // Match by id first; a pasted title that is already in the list keeps its row (and usage).
            $model = $existing->get((int) ($row['id'] ?? 0)) ?? $byText->get($key);
            if ($model && ! in_array($model->id, $keep, true)) {
                $model->fill(['title' => $title, 'weight' => $weight])->save();
            } else {
                $model = $this->titles()->create(['title' => $title, 'weight' => $weight]);
            }
            $keep[] = $model->id;
        }
        $this->titles()->whereNotIn('id', $keep ?: [0])->delete();

        return count($keep);
    }

    /** Old callers (paste a block of text): one title per line, weight 1. */
    public function syncTitles(string $text): int
    {
        return $this->syncRows(self::rowsFromText($text));
    }
}
