<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocsController extends Controller
{
    public function __invoke(Request $request): View
    {
        $service = $request->filled('service')
            ? Service::query()->where('user_id', $request->user()->id)->find($request->integer('service'))
            : null;

        return view('docs', ['service' => $service, 'sample' => self::sampleWebhook()]);
    }

    /** Example webhook body shown in the docs and in the "?" modal. */
    public static function sampleWebhook(): string
    {
        $base = rtrim(config('app.url'), '/');
        $sample = [
            'event' => 'article.created',
            'version' => '1',
            'delivery' => ['id' => '9b1d4c7e-1f0a-4f4b-9a37-3c2b8f1e6d55', 'attempt' => 1],
            'service' => ['id' => 12, 'name' => 'Psychology blog'],
            'article' => [
                'id' => '6f0c2a9e-8a3b-4c51-b2d4-7e9f10a2c3d4',
                'service_id' => 12,
                'status' => 'ready',
                'title' => 'How Sleep Shapes Your Mood',
                'source_title' => 'sleep and mood',
                'slug' => 'how-sleep-shapes-your-mood',
                'excerpt' => 'Short summary of the article…',
                'category' => 'Psychology',
                'language' => 'en',
                'direction' => 'ltr',
                'tone' => 'Friendly',
                'word_count' => 812,
                'body_html' => '<p>Intro…</p><h2>…</h2><p>…</p>',
                'body_html_with_images' => '<p>Intro…</p><figure><img src="…"></figure>…',
                'body_text' => 'Intro…',
                'meta' => ['title' => '…', 'description' => '…', 'keywords' => ['sleep', 'mood']],
                'images' => [[
                    'id' => 'c2a0f3b1-5d6e-4f70-8a91-b2c3d4e5f607',
                    'position' => 1,
                    'is_cover' => true,
                    'size_name' => 'Large',
                    'width' => 1024,
                    'height' => 1024,
                    'mime' => 'image/jpeg',
                    'bytes' => 183422,
                    'alt' => 'A person sleeping peacefully',
                    'url' => $base.'/media/c2a0f3b1-5d6e-4f70-8a91-b2c3d4e5f607.jpg',
                    'base64' => '(only when "Embed images" is on)',
                ]],
                'created_at' => '2026-10-17T09:00:02+00:00',
                'generated_at' => '2026-10-17T09:01:40+00:00',
            ],
        ];

        return json_encode($sample, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
