<?php

namespace App\Ai;

/** Crops and scales an image to an exact size ("cover"), and saves it as JPEG. */
final class ImageProcessor
{
    public static function toJpeg(string $bytes, int $width, int $height, int $quality = 88): string
    {
        $src = @imagecreatefromstring($bytes);
        if (! $src) {
            throw new AiException('The AI image cannot be read.');
        }
        $sw = imagesx($src);
        $sh = imagesy($src);

        $scale = max($width / $sw, $height / $sh);
        $cw = (int) round($width / $scale);
        $ch = (int) round($height / $scale);
        $sx = (int) floor(($sw - $cw) / 2);
        $sy = (int) floor(($sh - $ch) / 2);

        $dst = imagecreatetruecolor($width, $height);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagecopyresampled($dst, $src, 0, 0, $sx, $sy, $width, $height, $cw, $ch);
        imageinterlace($dst, true);

        ob_start();
        imagejpeg($dst, null, $quality);
        $out = (string) ob_get_clean();
        imagedestroy($src);
        imagedestroy($dst);

        return $out;
    }
}
