<?php

namespace App\Generation;

use App\Jobs\GenerateArticle;
use App\Models\Article;
use App\Models\Service;
use App\Models\Title;
use Illuminate\Support\Facades\DB;

/** Creates the article row for a service run and picks its title. */
class ArticleFactory
{
    public function start(Service $service, string $trigger = 'schedule'): Article
    {
        $article = DB::transaction(function () use ($service, $trigger) {
            $title = $this->pickTitle($service);

            $article = Article::query()->create([
                'user_id' => $service->user_id,
                'service_id' => $service->id,
                'title_id' => $title?->id,
                'source_title' => $title?->title,
                'status' => Article::PENDING,
                'language' => $service->language,
                'tone' => $service->tone?->name,
                'requested_words' => $service->word_count,
                'text_agent_id' => $service->text_agent_id,
                'image_agent_id' => $service->imageSizeIds() ? $service->image_agent_id : null,
                'trigger' => $trigger,
            ]);

            if (! $title) {
                $article->update([
                    'status' => Article::FAILED,
                    'error' => $service->title_list_id
                        ? 'All titles of the list are used. Add new titles or allow re-use of titles.'
                        : 'The service has no title list.',
                ]);
            }

            return $article;
        });

        if ($article->status === Article::PENDING) {
            GenerateArticle::dispatch($article->id);
        }

        return $article;
    }

    /** Least-used title first; unused titles only, unless the service allows re-use. */
    private function pickTitle(Service $service): ?Title
    {
        if (! $service->title_list_id) {
            return null;
        }
        $query = Title::query()->where('title_list_id', $service->title_list_id)->lockForUpdate();
        if (! $service->reuse_titles) {
            $query->where('used_count', 0);
        }
        $title = $query->orderBy('used_count')->orderByRaw('last_used_at is null desc')->orderBy('last_used_at')
            ->orderBy('id')->first();
        if ($title) {
            $title->forceFill(['used_count' => $title->used_count + 1, 'last_used_at' => now()])->save();
        }

        return $title;
    }
}
