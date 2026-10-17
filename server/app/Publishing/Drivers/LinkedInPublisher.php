<?php

namespace App\Publishing\Drivers;

use App\Models\Article;
use App\Models\Delivery;
use App\Publishing\PublishResult;

/**
 * LinkedIn Posts API (w_member_social or w_organization_social).
 * author: urn:li:person:xxxx or urn:li:organization:xxxx. The cover is uploaded first.
 */
class LinkedInPublisher extends BasePublisher
{
    public function key(): string
    {
        return 'linkedin';
    }

    public function icon(): string
    {
        return 'bi-linkedin';
    }

    public function tone(): string
    {
        return 'sky';
    }

    public function fields(): array
    {
        return [
            'author_urn' => ['type' => 'text', 'required' => true],
            'access_token' => ['type' => 'password', 'required' => true, 'secret' => true],
            'caption_mode' => ['type' => 'select', 'options' => ['full', 'summary'], 'default' => 'full'],
            'api_version' => ['type' => 'text', 'default' => '202509'],
        ];
    }

    public function target(array $config): string
    {
        return (string) ($config['author_urn'] ?? '');
    }

    public function publish(Article $article, array $config, Delivery $delivery): PublishResult
    {
        $this->require($config, 'author_urn', 'access_token');
        $author = trim($config['author_urn']);
        $http = fn () => $this->http()->withToken(trim($config['access_token']))->withHeaders([
            'LinkedIn-Version' => ($config['api_version'] ?? '') ?: '202509',
            'X-Restli-Protocol-Version' => '2.0.0',
        ]);

        $post = [
            'author' => $author,
            'commentary' => $this->escape($this->caption($article, $config['caption_mode'] ?? 'full', 3000)),
            'visibility' => 'PUBLIC',
            'distribution' => ['feedDistribution' => 'MAIN_FEED', 'targetEntities' => [], 'thirdPartyDistributionChannels' => []],
            'lifecycleState' => 'PUBLISHED',
            'isReshareDisabledByAuthor' => false,
        ];

        if ($cover = $article->readyImages()->first()) {
            $init = $this->ok($http()->post('https://api.linkedin.com/rest/images?action=initializeUpload',
                ['initializeUploadRequest' => ['owner' => $author]]), 'LinkedIn image init');
            $this->ok($http()->withBody($cover->contents(), 'image/jpeg')->put((string) $init->json('value.uploadUrl')), 'LinkedIn image upload');
            $post['content'] = ['media' => ['id' => (string) $init->json('value.image'), 'altText' => (string) $cover->alt]];
        }

        $res = $this->ok($http()->post('https://api.linkedin.com/rest/posts', $post), 'LinkedIn post');
        $urn = (string) $res->header('x-restli-id');

        return new PublishResult($res->status(), 'post '.$urn, $urn !== '' ? 'https://www.linkedin.com/feed/update/'.$urn : null);
    }

    /** LinkedIn "little text format" reserves these characters. */
    private function escape(string $text): string
    {
        return preg_replace('/([\\\\|{}@\[\]()<>#*_~])/u', '\\\\$1', $text);
    }
}
