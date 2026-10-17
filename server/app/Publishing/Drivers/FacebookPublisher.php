<?php

namespace App\Publishing\Drivers;

use App\Models\Article;
use App\Models\Delivery;
use App\Publishing\PublishResult;

/** Facebook Page post (Graph API) with a Page access token (pages_manage_posts). */
class FacebookPublisher extends BasePublisher
{
    public function key(): string
    {
        return 'facebook';
    }

    public function icon(): string
    {
        return 'bi-facebook';
    }

    public function tone(): string
    {
        return 'sky';
    }

    public function fields(): array
    {
        return [
            'page_id' => ['type' => 'text', 'required' => true],
            'page_token' => ['type' => 'password', 'required' => true, 'secret' => true],
            'caption_mode' => ['type' => 'select', 'options' => ['full', 'summary'], 'default' => 'full'],
            'graph_version' => ['type' => 'text', 'default' => 'v23.0'],
        ];
    }

    public function target(array $config): string
    {
        return 'Page '.($config['page_id'] ?? '');
    }

    public function publish(Article $article, array $config, Delivery $delivery): PublishResult
    {
        $this->require($config, 'page_id', 'page_token');
        $api = 'https://graph.facebook.com/'.(($config['graph_version'] ?? '') ?: 'v23.0').'/'.trim($config['page_id']);
        $token = trim($config['page_token']);
        $message = $this->caption($article, $config['caption_mode'] ?? 'full', 60000);
        $cover = $article->readyImages()->first();

        $res = $cover
            ? $this->http()->asForm()->post($api.'/photos', ['url' => $cover->url(), 'message' => $message, 'access_token' => $token])
            : $this->http()->asForm()->post($api.'/feed', ['message' => $message, 'access_token' => $token]);
        $this->ok($res, 'Facebook');
        $id = (string) ($res->json('post_id') ?? $res->json('id'));

        return new PublishResult($res->status(), 'post id '.$id, $id !== '' ? 'https://www.facebook.com/'.$id : null);
    }
}
