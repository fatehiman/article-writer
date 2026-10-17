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
                        ? 'The title list has no active title (empty, or every weight is 0).'
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

    /**
     * Weighted fair rotation: pick the title that is most behind its share, i.e. the lowest
     * used_count / weight. Over time each title gets weight / (sum of weights) of the articles.
     * Ties: higher weight first, then the one used longest ago, then list order. Weight 0 = paused.
     */
    private function pickTitle(Service $service): ?Title
    {
        if (! $service->title_list_id) {
            return null;
        }
        $title = Title::query()->where('title_list_id', $service->title_list_id)->where('weight', '>', 0)
            ->lockForUpdate()
            ->orderByRaw('used_count / weight')->orderByDesc('weight')
            ->orderByRaw('last_used_at is null desc')->orderBy('last_used_at')->orderBy('id')->first();
        if ($title) {
            $title->forceFill(['used_count' => $title->used_count + 1, 'last_used_at' => now()])->save();
        }

        return $title;
    }
}
