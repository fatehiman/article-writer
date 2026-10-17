<?php

namespace App\Ai;

interface ImageDriver
{
    /**
     * Draw one image. The result may have another size than asked;
     * the caller crops it to the exact size.
     *
     * @return string raw image bytes (PNG/JPEG/WebP)
     *
     * @throws AiException
     */
    public function image(string $prompt, int $width, int $height): string;
}
