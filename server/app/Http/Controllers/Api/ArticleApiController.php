<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Service;
use App\Publishing\Payload;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** Pull API: target apps read the articles of one service with its token. */
class ArticleApiController extends Controller
{
    public function service(Request $request): JsonResponse
    {
        $service = $this->tokenService($request);

        return response()->json(['data' => [
            'id' => $service->id,
            'name' => $service->name,
            'language' => $service->language,
            'categories' => $service->categories ?? [],
            'is_active' => $service->is_active,
            'next_run_at' => $service->next_run_at?->toIso8601String(),
            'articles_ready' => $service->articles()->where('status', Article::READY)->count(),
        ]]);
    }

    public function index(Request $request): JsonResponse
    {
        $service = $this->tokenService($request);
        $request->validate([
            'since' => ['nullable', 'date'],
            'status' => ['nullable', 'in:ready,failed,pending,generating,all'],
            'category' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'order' => ['nullable', 'in:asc,desc'],
            'fields' => ['nullable', 'in:full,summary'],
        ]);

        $status = $request->input('status', 'ready');
        $page = $service->articles()->with('images')
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($request->input('since'), fn ($q, $s) => $q->where('created_at', '>=', Carbon::parse($s)->utc()))
            ->when($request->input('category'), fn ($q, $c) => $q->where('category', $c))
            ->orderBy('id', $request->input('order', 'desc'))
            ->paginate($request->integer('per_page', 20) ?: 20);

        $summary = $request->input('fields') === 'summary';
        $items = $page->getCollection()->map(function (Article $a) use ($summary) {
            $item = Payload::article($a);
            if ($summary) {
                unset($item['body_html'], $item['body_html_with_images'], $item['body_text']);
            }

            return $item;
        });

        return response()->json([
            'data' => $items,
            'meta' => [
                'page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'last_page' => $page->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, string $uuid): JsonResponse
    {
        $service = $this->tokenService($request);
        $article = $service->articles()->where('uuid', $uuid)->firstOrFail();

        return response()->json(['data' => Payload::article($article, $request->boolean('embed_images'))]);
    }

    private function tokenService(Request $request): Service
    {
        $token = (string) ($request->bearerToken() ?: $request->header('X-Service-Token'));
        $service = Service::findByToken($token);
        if (! $service || ! $service->user?->is_active) {
            throw new AuthenticationException();
        }

        return $service;
    }

    public function openapi(): JsonResponse
    {
        $image = ['type' => 'object', 'properties' => [
            'id' => ['type' => 'string', 'format' => 'uuid'],
            'position' => ['type' => 'integer'],
            'is_cover' => ['type' => 'boolean'],
            'size_name' => ['type' => 'string'],
            'width' => ['type' => 'integer'],
            'height' => ['type' => 'integer'],
            'mime' => ['type' => 'string', 'example' => 'image/jpeg'],
            'bytes' => ['type' => 'integer'],
            'alt' => ['type' => 'string'],
            'url' => ['type' => 'string', 'format' => 'uri'],
            'base64' => ['type' => 'string', 'description' => 'Only with embed_images=1 (or "Embed images" for webhooks).'],
        ]];
        $article = ['type' => 'object', 'properties' => [
            'id' => ['type' => 'string', 'format' => 'uuid'],
            'service_id' => ['type' => 'integer'],
            'status' => ['type' => 'string', 'enum' => ['pending', 'generating', 'ready', 'failed']],
            'title' => ['type' => 'string'],
            'source_title' => ['type' => 'string', 'description' => 'The title from your title list.'],
            'slug' => ['type' => 'string'],
            'excerpt' => ['type' => 'string'],
            'category' => ['type' => 'string', 'nullable' => true, 'description' => 'One of the service categories.'],
            'language' => ['type' => 'string', 'example' => 'en'],
            'direction' => ['type' => 'string', 'enum' => ['ltr', 'rtl']],
            'tone' => ['type' => 'string', 'nullable' => true],
            'word_count' => ['type' => 'integer'],
            'body_html' => ['type' => 'string', 'description' => 'HTML: h2, h3, p, ul, ol, li, strong, em, blockquote. No images.'],
            'body_html_with_images' => ['type' => 'string', 'description' => 'body_html with images 2..n placed in it as <figure><img>.'],
            'body_text' => ['type' => 'string'],
            'meta' => ['type' => 'object', 'properties' => [
                'title' => ['type' => 'string'], 'description' => ['type' => 'string'],
                'keywords' => ['type' => 'array', 'items' => ['type' => 'string']],
            ]],
            'images' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/Image']],
            'created_at' => ['type' => 'string', 'format' => 'date-time'],
            'generated_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
        ]];
        $err = ['401' => ['description' => 'Invalid or missing service token']];

        return response()->json([
            'openapi' => '3.0.3',
            'info' => [
                'title' => config('app.name').' API',
                'version' => '1.0.0',
                'description' => 'Read the articles of one service. Each service has its own token (service page > API token). '
                    .'Send it as "Authorization: Bearer <token>" or "X-Service-Token: <token>".',
            ],
            'servers' => [['url' => rtrim(config('app.url'), '/').'/api']],
            'security' => [['bearerAuth' => []], ['serviceToken' => []]],
            'paths' => [
                '/v1/service' => ['get' => [
                    'summary' => 'Information about the service of this token',
                    'responses' => ['200' => ['description' => 'OK']] + $err,
                ]],
                '/v1/articles' => ['get' => [
                    'summary' => 'List articles (newest first)',
                    'parameters' => [
                        ['name' => 'since', 'in' => 'query', 'schema' => ['type' => 'string', 'format' => 'date-time'], 'description' => 'Only articles created at or after this time.'],
                        ['name' => 'status', 'in' => 'query', 'schema' => ['type' => 'string', 'enum' => ['ready', 'failed', 'pending', 'generating', 'all'], 'default' => 'ready']],
                        ['name' => 'category', 'in' => 'query', 'schema' => ['type' => 'string']],
                        ['name' => 'order', 'in' => 'query', 'schema' => ['type' => 'string', 'enum' => ['desc', 'asc'], 'default' => 'desc']],
                        ['name' => 'fields', 'in' => 'query', 'schema' => ['type' => 'string', 'enum' => ['full', 'summary'], 'default' => 'full'], 'description' => 'summary = without the body.'],
                        ['name' => 'page', 'in' => 'query', 'schema' => ['type' => 'integer', 'default' => 1]],
                        ['name' => 'per_page', 'in' => 'query', 'schema' => ['type' => 'integer', 'default' => 20, 'maximum' => 100]],
                    ],
                    'responses' => ['200' => ['description' => 'OK', 'content' => ['application/json' => ['schema' => [
                        'type' => 'object', 'properties' => [
                            'data' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/Article']],
                            'meta' => ['type' => 'object', 'properties' => [
                                'page' => ['type' => 'integer'], 'per_page' => ['type' => 'integer'],
                                'total' => ['type' => 'integer'], 'last_page' => ['type' => 'integer'],
                            ]],
                        ],
                    ]]]]] + $err,
                ]],
                '/v1/articles/{id}' => ['get' => [
                    'summary' => 'One article',
                    'parameters' => [
                        ['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string', 'format' => 'uuid']],
                        ['name' => 'embed_images', 'in' => 'query', 'schema' => ['type' => 'boolean'], 'description' => 'Add base64 image data.'],
                    ],
                    'responses' => ['200' => ['description' => 'OK', 'content' => ['application/json' => ['schema' => [
                        'type' => 'object', 'properties' => ['data' => ['$ref' => '#/components/schemas/Article']],
                    ]]]], '404' => ['description' => 'Not found']] + $err,
                ]],
            ],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => ['type' => 'http', 'scheme' => 'bearer'],
                    'serviceToken' => ['type' => 'apiKey', 'in' => 'header', 'name' => 'X-Service-Token'],
                ],
                'schemas' => ['Article' => $article, 'Image' => $image],
            ],
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }
}
