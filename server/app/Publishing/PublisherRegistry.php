<?php

namespace App\Publishing;

use InvalidArgumentException;

class PublisherRegistry
{
    /** @var array<string, Publisher> */
    private array $made = [];

    /** @return array<string, Publisher> */
    public function all(): array
    {
        foreach (array_keys(config('publishers.drivers')) as $key) {
            $this->get($key);
        }

        return $this->made;
    }

    public function has(string $key): bool
    {
        return isset(config('publishers.drivers')[$key]);
    }

    public function get(string $key): Publisher
    {
        if (! isset($this->made[$key])) {
            $class = config('publishers.drivers')[$key] ?? throw new InvalidArgumentException("Unknown publisher {$key}");
            $this->made[$key] = app($class);
        }

        return $this->made[$key];
    }
}
