<?php

namespace App\Http\Controllers;

use App\Generation\ArticleFactory;
use App\Models\AiAgent;
use App\Models\ImageSize;
use App\Models\Service;
use App\Models\Tone;
use App\Support\Schedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(Request $request): View
    {
        $services = $request->user()->services()->with(['titleList', 'textAgent', 'destinations'])
            ->withCount(['articles as ready_count' => fn ($q) => $q->where('status', 'ready')])
            ->orderBy('name')->get();

        return view('services.index', compact('services'));
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $service = new Service([
            'is_active' => true,
            'language' => $user->locale,
            'timezone' => $user->timezone ?: 'UTC',
            'word_count' => 800,
            'schedule_times' => ['09:00'],
            'schedule_type' => 'daily',
            'schedule_days' => [],
            'images' => [],
            'retry_limit' => 5,
            'retry_delay' => 3,
            'text_agent_id' => AiAgent::query()->usable('text')->value('id'),
            'image_agent_id' => AiAgent::query()->usable('image')->value('id'),
            'title_list_id' => $request->integer('title_list') ?: null,
        ]);

        return view('services.form', $this->formData($request, $service));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $service = new Service($data['fields']);
        $service->user_id = $request->user()->id;
        $service->rotateToken();
        $service->rotateWebhookSecret();
        $service->refreshNextRun();
        $service->save();
        $service->destinations()->sync($data['destinations']);

        return redirect()->route('services.show', $service)->with('ok', __('ui.saved'));
    }

    public function show(Service $service): View
    {
        $this->own($service);
        $service->load(['titleList', 'tone', 'textAgent', 'imageAgent', 'destinations']);
        $articles = $service->articles()->latest('id')->limit(15)->get();
        $sizes = ImageSize::query()->whereIn('id', $service->imageSizeIds())->get()->keyBy('id');
        $titlesLeft = $service->titleList ? $service->titleList->titles()->where('weight', '>', 0)->count() : 0;

        return view('services.show', compact('service', 'articles', 'sizes', 'titlesLeft'));
    }

    public function edit(Request $request, Service $service): View
    {
        $this->own($service);

        return view('services.form', $this->formData($request, $service));
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $this->own($service);
        $data = $this->validated($request);
        $service->fill($data['fields']);
        $service->refreshNextRun();
        $service->save();
        $service->destinations()->sync($data['destinations']);

        return redirect()->route('services.show', $service)->with('ok', __('ui.saved'));
    }

    public function destroy(Service $service): RedirectResponse
    {
        $this->own($service);
        $service->delete();

        return redirect()->route('services.index')->with('ok', __('ui.deleted'));
    }

    public function run(Service $service, ArticleFactory $factory): RedirectResponse
    {
        $this->own($service);
        $article = $factory->start($service, 'manual');

        return redirect()->route('articles.show', $article)->with('ok', __('ui.generation_started'));
    }

    public function rotateToken(Service $service): RedirectResponse
    {
        $this->own($service);
        $service->rotateToken();
        $service->save();

        return back()->with('ok', __('ui.token_rotated'));
    }

    public function rotateSecret(Service $service): RedirectResponse
    {
        $this->own($service);
        $service->rotateWebhookSecret();
        $service->save();

        return back()->with('ok', __('ui.secret_rotated'));
    }

    public function toggle(Service $service): RedirectResponse
    {
        $this->own($service);
        $service->is_active = ! $service->is_active;
        $service->refreshNextRun();
        $service->save();

        return back()->with('ok', __('ui.saved'));
    }

    public function duplicate(Service $service): RedirectResponse
    {
        $this->own($service);
        $copy = $service->replicate(['api_token', 'api_token_hash', 'next_run_at', 'last_run_at']);
        $copy->name = $service->name.' ('.__('ui.copy').')';
        $copy->is_active = false;
        $copy->rotateToken();
        $copy->rotateWebhookSecret();
        $copy->save();
        $copy->destinations()->sync($service->destinations()->pluck('destinations.id'));

        return redirect()->route('services.edit', $copy)->with('ok', __('ui.duplicated'));
    }

    private function formData(Request $request, Service $service): array
    {
        $user = $request->user();

        return [
            'service' => $service,
            'titleLists' => $user->titleLists()->withCount('titles')->orderBy('name')->get(),
            'tones' => Tone::query()->where('is_active', true)->orderBy('sort')->orderBy('name')->get(),
            'textAgents' => AiAgent::query()->usable('text')->get(),
            'imageAgents' => AiAgent::query()->usable('image')->get(),
            'imageSizes' => ImageSize::query()->where('is_active', true)->orderBy('sort')->orderBy('name')->get(),
            'destinations' => $user->destinations()->orderBy('name')->get(),
            'selectedDestinations' => $service->exists ? $service->destinations()->pluck('destinations.id')->all() : [],
        ];
    }

    /** @return array{fields: array, destinations: list<int>} */
    private function validated(Request $request): array
    {
        $userId = $request->user()->id;
        $type = (string) $request->input('schedule_type');
        $imageCount = max(0, min(Service::MAX_IMAGES, $request->integer('image_count')));

        $v = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'title_list_id' => ['required', Rule::exists('title_lists', 'id')->where('user_id', $userId)],
            'categories' => ['nullable', 'string', 'max:5000'],
            'word_count' => ['required', 'integer', 'min:100', 'max:6000'],
            'tone_id' => ['nullable', Rule::exists('tones', 'id')->where('is_active', true)],
            'language' => ['required', Rule::in(array_keys(config('languages.articles')))],
            'instructions' => ['nullable', 'string', 'max:3000'],
            'text_agent_id' => ['required', Rule::in(AiAgent::query()->usable('text')->pluck('id')->all())],
            'image_count' => ['required', 'integer', 'min:0', 'max:'.Service::MAX_IMAGES],
            'images' => [$imageCount > 0 ? 'required' : 'nullable', 'array', 'max:'.Service::MAX_IMAGES],
            'images.*' => ['integer', Rule::exists('image_sizes', 'id')->where('is_active', true)],
            'image_agent_id' => [$imageCount > 0 ? 'required' : 'nullable', Rule::in(AiAgent::query()->usable('image')->pluck('id')->all())],
            'timezone' => ['required', 'timezone:all'],
            'schedule_times' => ['required', 'array', 'min:1', 'max:24'],
            'schedule_times.*' => ['required', 'date_format:H:i'],
            'schedule_type' => ['required', Rule::in(Schedule::TYPES)],
            'schedule_days' => [$type === 'daily' ? 'nullable' : 'required', 'array'],
            'schedule_days.*' => ['integer', $type === 'weekly' ? 'between:0,6' : 'between:1,31'],
            'webhook_url' => ['nullable', 'url:http,https', 'max:1000'],
            'retry_limit' => ['required', 'integer', 'min:0', 'max:'.Service::MAX_RETRIES],
            'retry_delay' => ['required', 'integer', 'min:1', 'max:1440'],
            'destinations' => ['nullable', 'array'],
            'destinations.*' => ['integer', Rule::exists('destinations', 'id')->where('user_id', $userId)],
        ], [], [
            'title_list_id' => __('ui.title_list'),
            'schedule_times.*' => __('ui.time'),
        ]);

        $categories = collect(preg_split('/[\r\n,،]+/u', (string) ($v['categories'] ?? '')))
            ->map(fn ($c) => trim(mb_substr(trim($c), 0, 100)))->filter()->unique()->take(100)->values()->all();

        return [
            'fields' => [
                'name' => $v['name'],
                'is_active' => $request->boolean('is_active'),
                'title_list_id' => (int) $v['title_list_id'],
                'categories' => $categories,
                'word_count' => (int) $v['word_count'],
                'tone_id' => $v['tone_id'] ?? null,
                'language' => $v['language'],
                'instructions' => $v['instructions'] ?? null,
                'text_agent_id' => (int) $v['text_agent_id'],
                'image_agent_id' => $imageCount > 0 ? (int) $v['image_agent_id'] : ($v['image_agent_id'] ?? null),
                'images' => array_slice(array_map('intval', array_values($v['images'] ?? [])), 0, $imageCount),
                'timezone' => $v['timezone'],
                'schedule_times' => Schedule::cleanTimes($v['schedule_times']),
                'schedule_type' => $v['schedule_type'],
                'schedule_days' => $type === 'daily' ? [] : array_values(array_unique(array_map('intval', $v['schedule_days'] ?? []))),
                'webhook_url' => $v['webhook_url'] ?? null,
                'webhook_embed_images' => $request->boolean('webhook_embed_images'),
                'retry_limit' => (int) $v['retry_limit'],
                'retry_delay' => (int) $v['retry_delay'],
            ],
            'destinations' => array_map('intval', $v['destinations'] ?? []),
        ];
    }
}
