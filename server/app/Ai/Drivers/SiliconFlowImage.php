<?php

namespace App\Ai\Drivers;

use App\Ai\AiException;
use App\Ai\ImageDriver;
use Illuminate\Support\Facades\Http;

/**
 * POST {base}/images/generations with "image_size". The reply has a temporary URL,
 * which we download. Agent option "sizes" lists the sizes the model accepts.
 */
class SiliconFlowImage extends BaseDriver implements ImageDriver
{
    public function image(string $prompt, int $width, int $height): string
    {
        [$w, $h] = $this->nativeSize((array) $this->agent->option('sizes', []), $width, $height);
        $payload = ['model' => $this->agent->model, 'prompt' => $prompt, 'image_size' => "{$w}x{$h}", 'batch_size' => 1];
        foreach (['num_inference_steps', 'guidance_scale', 'negative_prompt'] as $key) {
            if (($v = $this->agent->option($key)) !== null && $v !== '') {
                $payload[$key] = is_numeric($v) ? $v + 0 : $v;
            }
        }

        $res = $this->http()->withToken((string) $this->provider->api_key)
            ->post($this->baseUrl().'/images/generations', $payload);
        if (! $res->successful()) {
            $this->fail('Image AI', $res);
        }
        $url = $res->json('images.0.url') ?? $res->json('data.0.url');
        if (! $url) {
            throw new AiException('Image AI returned no image.');
        }

        $file = Http::timeout(120)->get($url);
        if (! $file->successful() || $file->body() === '') {
            throw new AiException('Image download failed: HTTP '.$file->status());
        }

        return $file->body();
    }
}
