<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImageSize;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ImageSizeController extends Controller
{
    public function index(): View
    {
        return view('admin.image-sizes.index', ['sizes' => ImageSize::query()->orderBy('group')->orderBy('sort')->orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('admin.image-sizes.form', ['size' => new ImageSize(['is_active' => true, 'group' => 'general'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        ImageSize::query()->create($this->validated($request));

        return redirect()->route('admin.image-sizes.index')->with('ok', __('ui.saved'));
    }

    public function edit(ImageSize $size): View
    {
        return view('admin.image-sizes.form', compact('size'));
    }

    public function update(Request $request, ImageSize $size): RedirectResponse
    {
        $size->update($this->validated($request));

        return redirect()->route('admin.image-sizes.index')->with('ok', __('ui.saved'));
    }

    public function destroy(ImageSize $size): RedirectResponse
    {
        $size->delete();

        return redirect()->route('admin.image-sizes.index')->with('ok', __('ui.deleted'));
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'group' => ['required', 'string', 'max:40', 'regex:/^[a-z0-9_-]+$/'],
            'width' => ['required', 'integer', 'min:64', 'max:4096'],
            'height' => ['required', 'integer', 'min:64', 'max:4096'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:10000'],
        ]);
        $data['sort'] = (int) ($data['sort'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
