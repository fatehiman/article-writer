<?php

namespace App\Ai\Drivers;

use App\Ai\AiException;
use App\Models\AiAgent;
use App\Models\AiProvider;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

abstract class BaseDriver
{
    public function __construct(protected AiProvider $provider, protected AiAgent $agent)
    {
    }

    protected function baseUrl(): string
    {
        $url = $this->provider->base_url ?: config("ai.drivers.{$this->provider->driver}.base_url");

        return rtrim((string) $url, '/');
    }

    protected function http(): PendingRequest
    {
        return Http::timeout((int) config('ai.timeout'))->connectTimeout(20)->acceptJson()->asJson();
    }

    protected function fail(string $what, Response $res): never
    {
        throw new AiException($what.' HTTP '.$res->status().': '.mb_substr($res->body(), 0, 400));
    }

    /** Decode a JSON object from a model reply; tolerates ```json fences and text around it. */
    protected function decodeObject(string $text): array
    {
        $text = trim($text);
        $text = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $text);
        $data = json_decode($text, true);
        if (! is_array($data)) {
            $start = strpos($text, '{');
            $end = strrpos($text, '}');
            if ($start !== false && $end > $start) {
                $data = json_decode(substr($text, $start, $end - $start + 1), true);
            }
        }
        if (! is_array($data)) {
            throw new AiException('The AI reply is not valid JSON.');
        }

        return $data;
    }

    /**
     * Pick the native size closest to the wanted aspect ratio. $sizes are "WxH" strings.
     * With no list, ask for the wanted size itself.
     *
     * @return array{0:int,1:int}
     */
    protected function nativeSize(array $sizes, int $width, int $height): array
    {
        $best = null;
        $want = $width / max($height, 1);
        foreach ($sizes as $s) {
            if (! preg_match('/^(\d+)\s*x\s*(\d+)$/i', trim((string) $s), $m)) {
                continue;
            }
            [$w, $h] = [(int) $m[1], (int) $m[2]];
            $score = abs(log(($w / $h) / $want));
            if ($best === null || $score < $best[2] - 1e-6 || (abs($score - $best[2]) < 1e-6 && $w * $h > $best[0] * $best[1])) {
                $best = [$w, $h, $score];
            }
        }

        return $best ? [$best[0], $best[1]] : [$width, $height];
    }
}
