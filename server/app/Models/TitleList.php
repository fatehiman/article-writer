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

    /**
     * Replace the list with these lines. Kept titles keep their usage count;
     * removed titles are deleted. Returns the number of titles in the list.
     */
    public function syncTitles(string $text): int
    {
        $wanted = collect(preg_split('/\R/u', $text))
            ->map(fn ($t) => trim(mb_substr(trim((string) $t), 0, 500)))
            ->filter()
            ->unique(fn ($t) => mb_strtolower($t))
            ->values();

        $existing = $this->titles()->get()->keyBy(fn ($t) => mb_strtolower($t->title));
        $keep = [];
        foreach ($wanted as $title) {
            $row = $existing->get(mb_strtolower($title));
            if ($row) {
                $keep[] = $row->id;
            } else {
                $keep[] = $this->titles()->create(['title' => $title])->id;
            }
        }
        $this->titles()->whereNotIn('id', $keep)->delete();

        return count($keep);
    }
}
