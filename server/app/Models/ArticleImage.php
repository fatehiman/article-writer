<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ArticleImage extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /** Public, unguessable address of the file (see MediaController). */
    public function url(): string
    {
        return route('media.show', ['uuid' => $this->uuid, 'ext' => 'jpg']);
    }

    public function absolutePath(): string
    {
        return Storage::disk('local')->path($this->path);
    }

    public function contents(): string
    {
        return (string) Storage::disk('local')->get($this->path);
    }

    public function figureHtml(): string
    {
        $alt = e((string) $this->alt);

        return '<figure><img src="'.e($this->url()).'" alt="'.$alt.'" width="'.$this->width.'" height="'.$this->height.'"></figure>';
    }
}
