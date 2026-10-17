<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiAgent;
use App\Models\AiProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AiAgentController extends Controller
{
    public function index(): View
    {
        $agents = AiAgent::query()->with('provider')->orderBy('kind')->orderBy('sort')->orderBy('name')->get();

        return view('admin.agents.index', compact('agents'));
    }

    public function create(Request $request): View
    {
        $kind = in_array($request->input('kind'), config('ai.kinds'), true) ? $request->input('kind') : 'text';

        return view('admin.agents.form', [
            'agent' => new AiAgent(['kind' => $kind, 'is_active' => true, 'price' => 0,
                'price_unit' => $kind === 'text' ? 'per_1k_words' : 'per_image']),
            'providers' => AiProvider::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        AiAgent::query()->create($this->validated($request));

        return redirect()->route('admin.agents.index')->with('ok', __('ui.saved'));
    }

    public function edit(AiAgent $agent): View
    {
        return view('admin.agents.form', ['agent' => $agent, 'providers' => AiProvider::query()->orderBy('name')->get()]);
    }

    public function update(Request $request, AiAgent $agent): RedirectResponse
    {
        $agent->update($this->validated($request));

        return redirect()->route('admin.agents.index')->with('ok', __('ui.saved'));
    }

    public function destroy(AiAgent $agent): RedirectResponse
    {
        $agent->delete();

        return redirect()->route('admin.agents.index')->with('ok', __('ui.deleted'));
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'ai_provider_id' => ['required', 'exists:ai_providers,id'],
            'name' => ['required', 'string', 'max:120'],
            'kind' => ['required', Rule::in(config('ai.kinds'))],
            'model' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:1000'],
            'options' => ['nullable', 'json'],
            'price' => ['required', 'numeric', 'min:0', 'max:100000'],
            'price_unit' => ['required', Rule::in(array_keys(config('ai.price_units')))],
            'sort' => ['nullable', 'integer', 'min:0', 'max:10000'],
        ]);

        if (! in_array($data['kind'], config('ai.price_units.'.$data['price_unit']), true)) {
            throw ValidationException::withMessages(['price_unit' => __('ui.price_unit_kind')]);
        }
        $provider = AiProvider::query()->findOrFail($data['ai_provider_id']);
        if (! in_array($data['kind'], config("ai.drivers.{$provider->driver}.kinds", []), true)) {
            throw ValidationException::withMessages(['ai_provider_id' => __('ui.provider_kind')]);
        }

        $data['options'] = $data['options'] ? json_decode($data['options'], true) : null;
        $data['sort'] = (int) ($data['sort'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
