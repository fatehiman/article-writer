<?php

namespace App\Publishing;

final class PublishResult
{
    public function __construct(
        public ?int $code = null,
        public ?string $response = null,
        public ?string $remoteUrl = null,
    ) {
    }
}
