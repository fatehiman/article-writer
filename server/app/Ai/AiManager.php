<?php

namespace App\Ai;

use App\Models\AiAgent;

/** Builds the driver object for an agent, from config/ai.php. */
class AiManager
{
    public function text(AiAgent $agent): TextDriver
    {
        return $this->make($agent, 'text');
    }

    public function image(AiAgent $agent): ImageDriver
    {
        return $this->make($agent, 'image');
    }

    private function make(AiAgent $agent, string $kind): object
    {
        $provider = $agent->provider;
        if (! $provider || ! $provider->is_active || ! $agent->is_active) {
            throw new AiException("AI agent \"{$agent->name}\" is disabled.");
        }
        $driver = config("ai.drivers.{$provider->driver}");
        if (! $driver || ! in_array($kind, $driver['kinds'], true)) {
            throw new AiException("Driver \"{$provider->driver}\" cannot make {$kind}.");
        }

        return new $driver['class']($provider, $agent);
    }
}
