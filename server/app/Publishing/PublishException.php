<?php

namespace App\Publishing;

use Illuminate\Http\Client\Response;
use RuntimeException;

class PublishException extends RuntimeException
{
    public ?int $status = null;

    public ?string $responseBody = null;

    public static function fromResponse(string $what, Response $res): self
    {
        $e = new self($what.' HTTP '.$res->status().': '.mb_substr($res->body(), 0, 500));
        $e->status = $res->status();
        $e->responseBody = mb_substr($res->body(), 0, 2000);

        return $e;
    }
}
