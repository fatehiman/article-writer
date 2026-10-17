<?php

namespace App\Publishing;

use App\Models\Article;

/** The JSON shape of an article, shared by webhooks and the API. Do not change it lightly. */
final class Payload
{
    public const VERSION = '1';

    public static function article(Article $article, bool $embedImages = false): array
    {
        $article->loadMissing(['images', 'service']);

        return [
            'id' => $article->uuid,
            'service_id' => $article->service_id,
            'status' => $article->status,
            'title' => $article->title,
            'source_title' => $article->source_title,
            'slug' => $article->slug,
            'excerpt' => $article->excerpt,
            'category' => $article->category,
            'language' => $article->language,
            'direction' => $article->isRtl() ? 'rtl' : 'ltr',
            'tone' => $article->tone,
            'word_count' => $article->word_count,
            'body_html' => $article->body,
            'body_html_with_images' => $article->status === Article::READY ? $article->bodyWithImages() : null,
            'body_text' => $article->status === Article::READY ? $article->plainText() : null,
            'meta' => [
                'title' => $article->meta_title,
                'description' => $article->meta_description,
                'keywords' => $article->keywords ?? [],
            ],
            'images' => $article->readyImages()->map(fn ($img, $i) => array_filter([
                'id' => $img->uuid,
                'position' => $img->position,
                'is_cover' => $i === 0,
                'size_name' => $img->size_name,
                'width' => $img->width,
                'height' => $img->height,
                'mime' => $img->mime,
                'bytes' => $img->bytes,
                'alt' => $img->alt,
                'url' => $img->url(),
                'base64' => $embedImages ? base64_encode($img->contents()) : null,
            ], fn ($v) => $v !== null))->all(),
            'created_at' => $article->created_at?->toIso8601String(),
            'generated_at' => $article->generated_at?->toIso8601String(),
        ];
    }

    /** Full webhook body. */
    public static function webhook(Article $article, string $deliveryId, int $attempt, bool $embedImages): array
    {
        return [
            'event' => 'article.created',
            'version' => self::VERSION,
            'delivery' => ['id' => $deliveryId, 'attempt' => $attempt],
            'service' => ['id' => $article->service_id, 'name' => $article->service?->name],
            'article' => self::article($article, $embedImages),
        ];
    }

    /** "t=<unix>,v1=<hex hmac-sha256 of '<unix>.<body>'>" */
    public static function signature(string $body, string $secret, int $timestamp): string
    {
        return 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$body, $secret);
    }
}
