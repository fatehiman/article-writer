<?php

namespace App\Publishing\Drivers;

use App\Models\Article;
use App\Models\Delivery;
use App\Publishing\PublishException;
use App\Publishing\PublishResult;

/**
 * Instagram Graph API (Business/Creator account linked to a Facebook page).
 * Needs a long-lived access token with instagram_content_publish. Instagram needs at
 * least one image (aspect 4:5 to 1.91:1); 2+ images become a carousel (max 10).
 */
class InstagramPublisher extends BasePublisher
{
    public function key(): string
    {
        return 'instagram';
    }

    public function icon(): string
    {
        return 'bi-instagram';
    }

    public function tone(): string
    {
        return 'rose';
    }

    public function fields(): array
    {
        return [
            'ig_user_id' => ['type' => 'text', 'required' => true],
            'access_token' => ['type' => 'password', 'required' => true, 'secret' => true],
            'caption_mode' => ['type' => 'select', 'options' => ['summary', 'full'], 'default' => 'summary'],
            'graph_version' => ['type' => 'text', 'default' => 'v23.0'],
        ];
    }

    public function target(array $config): string
    {
        return 'IG '.($config['ig_user_id'] ?? '');
    }

    public function publish(Article $article, array $config, Delivery $delivery): PublishResult
    {
        $this->require($config, 'ig_user_id', 'access_token');
        $images = $article->readyImages()->take(10)->values();
        if ($images->isEmpty()) {
            throw new PublishException('Instagram needs at least one image. Add images to the service.');
        }
        $api = 'https://graph.facebook.com/'.($config['graph_version'] ?? 'v23.0' ?: 'v23.0');
        $user = $api.'/'.trim($config['ig_user_id']);
        $token = trim($config['access_token']);
        $caption = $this->caption($article, $config['caption_mode'] ?? 'summary', 2200);

        if ($images->count() === 1) {
            $container = $this->post($user.'/media', ['image_url' => $images[0]->url(), 'caption' => $caption, 'access_token' => $token]);
        } else {
            $children = [];
            foreach ($images as $img) {
                $children[] = $this->post($user.'/media', ['image_url' => $img->url(), 'is_carousel_item' => 'true', 'access_token' => $token]);
            }
            $container = $this->post($user.'/media', [
                'media_type' => 'CAROUSEL', 'children' => implode(',', $children), 'caption' => $caption, 'access_token' => $token,
            ]);
        }

        $this->waitReady($api, $container, $token);
        $id = $this->post($user.'/media_publish', ['creation_id' => $container, 'access_token' => $token]);
        $link = $this->http()->get($api.'/'.$id, ['fields' => 'permalink', 'access_token' => $token])->json('permalink');

        return new PublishResult(200, 'media id '.$id, $link);
    }

    private function post(string $url, array $data): string
    {
        $res = $this->ok($this->http()->asForm()->post($url, $data), 'Instagram');

        return (string) $res->json('id');
    }

    /** Instagram processes the upload first; publish fails until it is FINISHED. */
    private function waitReady(string $api, string $id, string $token): void
    {
        for ($i = 0; $i < 20; $i++) {
            $status = $this->http()->get($api.'/'.$id, ['fields' => 'status_code', 'access_token' => $token])->json('status_code');
            if ($status === 'FINISHED' || $status === null) {
                return;
            }
            if ($status === 'ERROR' || $status === 'EXPIRED') {
                throw new PublishException('Instagram could not process the media ('.$status.').');
            }
            sleep(3);
        }
    }
}
