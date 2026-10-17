<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AiProviderController extends Controller
{
    public function index(): View
    {
        return view('admin.providers.index', ['providers' => AiProvider::query()->withCount('agents')->orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('admin.providers.form', ['provider' => new AiProvider(['is_active' => true, 'driver' => 'openai_compatible'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        AiProvider::query()->create($this->validated($request));

        return redirect()->route('admin.providers.index')->with('ok', __('ui.saved'));
    }

    public function edit(AiProvider $provider): View
    {
        return view('admin.providers.form', compact('provider'));
    }

    public function update(Request $request, AiProvider $provider): RedirectResponse
    {
        $data = $this->validated($request);
        if (($data['api_key'] ?? '') === '') {
            unset($data['api_key']); // empty = keep the old key
        }
        $provider->update($data);

        return redirect()->route('admin.providers.index')->with('ok', __('ui.saved'));
    }

    public function destroy(AiProvider $provider): RedirectResponse
    {
        $provider->delete();

        return redirect()->route('admin.providers.index')->with('ok', __('ui.deleted'));
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'driver' => ['required', Rule::in(array_keys(config('ai.drivers')))],
            'base_url' => ['nullable', 'url:http,https', 'max:255'],
            'api_key' => ['nullable', 'string', 'max:2000'],
        ]);
        $data['api_key'] = trim((string) ($data['api_key'] ?? ''));
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
