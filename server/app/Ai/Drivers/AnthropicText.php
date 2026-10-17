<?php

namespace App\Ai\Drivers;

use App\Ai\AiException;
use App\Ai\TextDriver;

/** POST {base}/v1/messages - Anthropic Claude. */
class AnthropicText extends BaseDriver implements TextDriver
{
    public function json(string $system, string $user, array $options = []): array
    {
        $payload = [
            'model' => $this->agent->model,
            'max_tokens' => (int) $this->agent->option('max_tokens', $options['max_tokens'] ?? 16000),
            'system' => $system."\nReply with one JSON object only, no other text.",
            'messages' => [['role' => 'user', 'content' => $user]],
        ];
        $temperature = $this->agent->option('temperature', $options['temperature'] ?? null);
        if ($temperature !== null && $temperature !== '') {
            $payload['temperature'] = min((float) $temperature, 1.0);
        }

        $res = $this->http()
            ->withHeaders(['x-api-key' => (string) $this->provider->api_key, 'anthropic-version' => '2023-06-01'])
            ->post($this->baseUrl().'/v1/messages', $payload);
        if (! $res->successful()) {
            $this->fail('Claude', $res);
        }
        if ($res->json('stop_reason') === 'max_tokens') {
            throw new AiException('The AI reply was cut off (max_tokens too small).');
        }
        $text = collect($res->json('content', []))->where('type', 'text')->pluck('text')->implode('');

        return $this->decodeObject($text);
    }
}
