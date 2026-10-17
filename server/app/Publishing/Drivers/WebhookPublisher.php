<?php

namespace App\Publishing\Drivers;

use App\Models\Article;
use App\Models\Delivery;
use App\Publishing\Payload;
use App\Publishing\PublishResult;

/** POST the article as JSON to any URL. Any 2xx answer is success. */
class WebhookPublisher extends BasePublisher
{
    public function key(): string
    {
        return 'webhook';
    }

    public function icon(): string
    {
        return 'bi-broadcast';
    }

    public function tone(): string
    {
        return 'violet';
    }

    public function fields(): array
    {
        return [
            'url' => ['type' => 'url', 'required' => true],
            'secret' => ['type' => 'password', 'secret' => true],
            'embed_images' => ['type' => 'checkbox'],
        ];
    }

    public function target(array $config): string
    {
        return (string) parse_url((string) ($config['url'] ?? ''), PHP_URL_HOST);
    }

    public function publish(Article $article, array $config, Delivery $delivery): PublishResult
    {
        $this->require($config, 'url');
        $this->guard($config['url']);

        $body = json_encode(Payload::webhook($article, $delivery->uuid, $delivery->attempts, ! empty($config['embed_images'])),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $time = time();
        $headers = [
            'X-AW-Event' => 'article.created',
            'X-AW-Delivery' => $delivery->uuid,
            'X-AW-Attempt' => (string) $delivery->attempts,
            'X-AW-Timestamp' => (string) $time,
        ];
        if (! empty($config['secret'])) {
            $headers['X-AW-Signature'] = Payload::signature($body, (string) $config['secret'], $time);
        }

        $res = $this->ok($this->http()->withHeaders($headers)->withBody($body, 'application/json')
            ->withoutRedirecting()->post($config['url']), 'Webhook');

        return new PublishResult($res->status(), mb_substr($res->body(), 0, 2000));
    }
}
