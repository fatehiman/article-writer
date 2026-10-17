<?php

namespace App\Publishing\Drivers;

use App\Models\Article;
use App\Publishing\PublishException;
use App\Publishing\Publisher;
use App\Support\UrlGuard;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

abstract class BasePublisher implements Publisher
{
    protected function http(): PendingRequest
    {
        return Http::timeout((int) config('publishers.timeout'))->connectTimeout(15)
            ->withUserAgent('ArticleWriter/1.0 (+'.config('app.url').')');
    }

    protected function guard(string $url): void
    {
        try {
            UrlGuard::check($url);
        } catch (RuntimeException $e) {
            throw new PublishException($e->getMessage());
        }
    }

    protected function ok(Response $res, string $what): Response
    {
        if (! $res->successful()) {
            throw PublishException::fromResponse($what, $res);
        }

        return $res;
    }

    protected function require(array $config, string ...$keys): void
    {
        foreach ($keys as $key) {
            if (trim((string) ($config[$key] ?? '')) === '') {
                throw new PublishException("Destination setting \"{$key}\" is empty.");
            }
        }
    }

    /** Text cut to $max characters on a word end, with an ellipsis. */
    protected function cut(string $text, int $max): string
    {
        if (mb_strlen($text) <= $max) {
            return $text;
        }
        $cut = mb_substr($text, 0, $max - 1);
        $space = mb_strrpos($cut, ' ');

        return ($space > $max * 0.7 ? mb_substr($cut, 0, $space) : $cut).'…';
    }

    /** Caption for social posts: title, excerpt/body, hashtags. */
    protected function caption(Article $article, string $mode, int $max): string
    {
        $tags = $article->hashtags();
        $main = $mode === 'full' ? $article->plainText() : (string) $article->excerpt;
        $room = $max - mb_strlen($article->title) - mb_strlen($tags) - 6;

        return trim($article->title."\n\n".$this->cut($main, max($room, 50))."\n\n".$tags);
    }
}
