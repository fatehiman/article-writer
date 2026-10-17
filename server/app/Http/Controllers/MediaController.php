<?php

namespace App\Http\Controllers;

use App\Models\ArticleImage;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaController extends Controller
{
    public function show(string $uuid): BinaryFileResponse
    {
        $image = ArticleImage::query()->where('uuid', $uuid)->whereNotNull('path')->firstOrFail();
        abort_unless(Storage::disk('local')->exists($image->path), 404);

        return response()->file($image->absolutePath(), [
            'Content-Type' => $image->mime,
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
