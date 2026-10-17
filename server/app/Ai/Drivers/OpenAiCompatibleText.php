<?php

namespace App\Ai\Drivers;

use App\Ai\AiException;
use App\Ai\TextDriver;

/** POST {base}/chat/completions - DeepSeek, OpenAI, OpenRouter, SiliconFlow chat, ... */
class OpenAiCompatibleText extends BaseDriver implements TextDriver
{
    public function json(string $system, string $user, array $options = []): array
    {
        $payload = [
            'model' => $this->agent->model,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
            'response_format' => ['type' => 'json_object'],
            'max_tokens' => (int) $this->agent->option('max_tokens', $options['max_tokens'] ?? 16000),
        ];
        $temperature = $this->agent->option('temperature', $options['temperature'] ?? null);
        if ($temperature !== null && $temperature !== '') {
            $payload['temperature'] = (float) $temperature;
        }

        $res = $this->http()->withToken((string) $this->provider->api_key)
            ->post($this->baseUrl().'/chat/completions', $payload);
        if (! $res->successful()) {
            $this->fail('Text AI', $res);
        }
        $choice = $res->json('choices.0');
        if (($choice['finish_reason'] ?? '') === 'length') {
            throw new AiException('The AI reply was cut off (max_tokens too small).');
        }

        return $this->decodeObject((string) ($choice['message']['content'] ?? ''));
    }
}
