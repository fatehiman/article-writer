<?php

namespace App\Ai;

interface TextDriver
{
    /**
     * Ask for one JSON object. Returns the decoded object.
     *
     * @throws AiException
     */
    public function json(string $system, string $user, array $options = []): array;
}
