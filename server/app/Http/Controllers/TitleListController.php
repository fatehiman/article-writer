<?php

namespace App\Http\Controllers;

use App\Models\TitleList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TitleListController extends Controller
{
    public function index(Request $request): View
    {
        $lists = $request->user()->titleLists()
            ->withCount(['titles', 'titles as unused_count' => fn ($q) => $q->where('used_count', 0), 'services'])
            ->orderBy('name')->get();

        return view('title-lists.index', compact('lists'));
    }

    public function create(): View
    {
        return view('title-lists.form', ['list' => new TitleList(), 'text' => '']);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($request, $data) {
            $list = $request->user()->titleLists()->create(['name' => $data['name']]);
            $list->syncTitles($data['titles']);
        });

        return redirect()->route('title-lists.index')->with('ok', __('ui.saved'));
    }

    /** Called from the modal in the service form. */
    public function quickStore(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $list = DB::transaction(function () use ($request, $data) {
            $list = $request->user()->titleLists()->create(['name' => $data['name']]);
            $list->syncTitles($data['titles']);

            return $list;
        });

        return response()->json(['id' => $list->id, 'name' => $list->name, 'count' => $list->titles()->count()]);
    }

    public function edit(TitleList $titleList): View
    {
        $this->own($titleList);
        $titles = $titleList->titles()->orderBy('id')->get();

        return view('title-lists.form', [
            'list' => $titleList,
            'text' => $titles->pluck('title')->implode("\n"),
            'titles' => $titles,
        ]);
    }

    public function update(Request $request, TitleList $titleList): RedirectResponse
    {
        $this->own($titleList);
        $data = $this->validated($request, $titleList);
        DB::transaction(function () use ($titleList, $data, $request) {
            $titleList->update(['name' => $data['name']]);
            $titleList->syncTitles($data['titles']);
            if ($request->boolean('reset_usage')) {
                $titleList->titles()->update(['used_count' => 0, 'last_used_at' => null]);
            }
        });

        return redirect()->route('title-lists.index')->with('ok', __('ui.saved'));
    }

    public function destroy(TitleList $titleList): RedirectResponse
    {
        $this->own($titleList);
        $titleList->delete();

        return redirect()->route('title-lists.index')->with('ok', __('ui.deleted'));
    }

    private function validated(Request $request, ?TitleList $list = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120',
                Rule::unique('title_lists', 'name')->where('user_id', $request->user()->id)->ignore($list?->id)],
            'titles' => ['required', 'string', 'max:500000'],
        ], [], ['name' => __('ui.name'), 'titles' => __('ui.titles')]);
    }
}
