<?php

namespace App\Publishing\Drivers;

use App\Models\Article;
use App\Models\Delivery;
use App\Publishing\PublishResult;

/**
 * WordPress REST API with an Application Password (Users > Profile > Application Passwords).
 * Uploads the images, sets the cover as featured image, finds or creates the category.
 */
class WordPressPublisher extends BasePublisher
{
    public function key(): string
    {
        return 'wordpress';
    }

    public function icon(): string
    {
        return 'bi-wordpress';
    }

    public function tone(): string
    {
        return 'sky';
    }

    public function fields(): array
    {
        return [
            'site_url' => ['type' => 'url', 'required' => true],
            'username' => ['type' => 'text', 'required' => true],
            'app_password' => ['type' => 'password', 'required' => true, 'secret' => true],
            'status' => ['type' => 'select', 'options' => ['publish', 'draft', 'pending', 'private'], 'default' => 'publish'],
            'create_categories' => ['type' => 'checkbox', 'default' => true],
        ];
    }

    public function target(array $config): string
    {
        return (string) parse_url((string) ($config['site_url'] ?? ''), PHP_URL_HOST);
    }

    public function publish(Article $article, array $config, Delivery $delivery): PublishResult
    {
        $this->require($config, 'site_url', 'username', 'app_password');
        $api = rtrim($config['site_url'], '/').'/wp-json/wp/v2';
        $this->guard($api);
        $http = fn () => $this->http()->acceptJson()->withBasicAuth($config['username'], str_replace(' ', '', $config['app_password']));

        // 1. images
        $media = [];
        foreach ($article->readyImages() as $img) {
            $res = $this->ok($http()->withHeaders([
                'Content-Disposition' => 'attachment; filename="'.($article->slug ?: 'image').'-'.$img->position.'.jpg"',
            ])->withBody($img->contents(), 'image/jpeg')->post($api.'/media'), 'WordPress media upload');
            $id = (int) $res->json('id');
            $media[$img->id] = ['id' => $id, 'url' => (string) $res->json('source_url')];
            if ($img->alt) {
                $http()->post($api.'/media/'.$id, ['alt_text' => $img->alt]);
            }
        }

        // 2. body with the uploaded image URLs
        $body = $article->bodyWithImages();
        foreach ($article->readyImages() as $img) {
            if (isset($media[$img->id]['url']) && $media[$img->id]['url'] !== '') {
                $body = str_replace(e($img->url()), e($media[$img->id]['url']), $body);
            }
        }

        // 3. category
        $categories = [];
        if ($article->category) {
            $found = $http()->get($api.'/categories', ['search' => $article->category, 'per_page' => 100]);
            $match = collect($found->successful() ? $found->json() : [])
                ->first(fn ($c) => mb_strtolower(html_entity_decode($c['name'] ?? '')) === mb_strtolower($article->category));
            if ($match) {
                $categories[] = (int) $match['id'];
            } elseif (! empty($config['create_categories'])) {
                $made = $http()->post($api.'/categories', ['name' => $article->category]);
                if ($made->successful()) {
                    $categories[] = (int) $made->json('id');
                }
            }
        }

        // 4. post
        $cover = $article->readyImages()->first();
        $post = array_filter([
            'title' => $article->title,
            'slug' => $article->slug,
            'content' => $body,
            'excerpt' => $article->excerpt,
            'status' => in_array($config['status'] ?? '', ['publish', 'draft', 'pending', 'private'], true) ? $config['status'] : 'publish',
            'categories' => $categories ?: null,
            'featured_media' => $cover ? ($media[$cover->id]['id'] ?? null) : null,
        ], fn ($v) => $v !== null && $v !== '');
        $res = $this->ok($http()->post($api.'/posts', $post), 'WordPress post');

        return new PublishResult($res->status(), 'post id '.$res->json('id'), (string) $res->json('link'));
    }
}
