<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ToneController extends Controller
{
    public function index(): View
    {
        return view('admin.tones.index', ['tones' => Tone::query()->orderBy('sort')->orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('admin.tones.form', ['tone' => new Tone(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        Tone::query()->create($this->validated($request));

        return redirect()->route('admin.tones.index')->with('ok', __('ui.saved'));
    }

    public function edit(Tone $tone): View
    {
        return view('admin.tones.form', compact('tone'));
    }

    public function update(Request $request, Tone $tone): RedirectResponse
    {
        $tone->update($this->validated($request));

        return redirect()->route('admin.tones.index')->with('ok', __('ui.saved'));
    }

    public function destroy(Tone $tone): RedirectResponse
    {
        $tone->delete();

        return redirect()->route('admin.tones.index')->with('ok', __('ui.deleted'));
    }

    private function validated(Request $request): array
    {
        $locales = array_diff(array_keys(config('languages.ui')), ['en']);
        $rules = [
            'name' => ['required', 'string', 'max:80'],
            'instruction' => ['nullable', 'string', 'max:1000'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:10000'],
        ];
        foreach ($locales as $l) {
            $rules["labels.{$l}"] = ['nullable', 'string', 'max:80'];
        }
        $data = $request->validate($rules);
        $data['labels'] = array_filter($data['labels'] ?? []);
        $data['sort'] = (int) ($data['sort'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
