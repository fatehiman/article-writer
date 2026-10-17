<?php

namespace App\Jobs;

use App\Generation\ArticleGenerator;
use App\Models\Article;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateArticle implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 1500;

    public function __construct(public int $articleId)
    {
    }

    public function handle(ArticleGenerator $generator): void
    {
        $article = Article::query()->find($this->articleId);
        if ($article) {
            $generator->run($article);
        }
    }
}
