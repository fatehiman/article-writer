<?php

namespace App\Ai\Drivers;

use App\Ai\AiException;
use App\Ai\ImageDriver;

/** POST {base}/images/generations - OpenAI gpt-image (base64 reply). */
class OpenAiImage extends BaseDriver implements ImageDriver
{
    public function image(string $prompt, int $width, int $height): string
    {
        $sizes = (array) $this->agent->option('sizes', ['1024x1024', '1536x1024', '1024x1536']);
        [$w, $h] = $this->nativeSize($sizes, $width, $height);
        $payload = ['model' => $this->agent->model, 'prompt' => $prompt, 'size' => "{$w}x{$h}", 'n' => 1];
        if ($quality = $this->agent->option('quality')) {
            $payload['quality'] = $quality;
        }

        $res = $this->http()->withToken((string) $this->provider->api_key)
            ->post($this->baseUrl().'/images/generations', $payload);
        if (! $res->successful()) {
            $this->fail('Image AI', $res);
        }
        $b64 = $res->json('data.0.b64_json');
        if (! $b64) {
            throw new AiException('Image AI returned no image.');
        }

        return (string) base64_decode($b64);
    }
}
