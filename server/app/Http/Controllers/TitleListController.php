<?php

namespace App\Http\Controllers;

use App\Models\Title;
use App\Models\TitleList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TitleListController extends Controller
{
    /** Most titles one list may hold. */
    private const MAX_ROWS = 5000;

    public function index(Request $request): View
    {
        $lists = $request->user()->titleLists()
            ->withCount(['titles', 'titles as active_count' => fn ($q) => $q->where('weight', '>', 0), 'services'])
            ->orderBy('name')->get();

        return view('title-lists.index', compact('lists'));
    }

    public function create(): View
    {
        return view('title-lists.form', ['list' => new TitleList(), 'rows' => []]);
    }

    public function store(Request $request): RedirectResponse
    {
        $name = $this->validName($request);
        $rows = $this->validRows($request);
        DB::transaction(function () use ($request, $name, $rows) {
            $request->user()->titleLists()->create(['name' => $name])->syncRows($rows);
        });

        return redirect()->route('title-lists.index')->with('ok', __('ui.saved'));
    }

    /** Called from the modal in the service form: a name and pasted lines, one weight for all. */
    public function quickStore(Request $request): JsonResponse
    {
        $name = $this->validName($request);
        $data = $request->validate([
            'titles' => ['required', 'string', 'max:500000'],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:'.Title::MAX_WEIGHT],
        ], [], ['titles' => __('ui.titles'), 'weight' => __('ui.weight')]);

        $list = DB::transaction(function () use ($request, $name, $data) {
            $list = $request->user()->titleLists()->create(['name' => $name]);
            $list->syncRows(TitleList::rowsFromText($data['titles'], (float) ($data['weight'] ?? 1)));

            return $list;
        });

        return response()->json(['id' => $list->id, 'name' => $list->name, 'count' => $list->titles()->count()]);
    }

    public function edit(TitleList $titleList): View
    {
        $this->own($titleList);
        $rows = $titleList->titles()->orderBy('id')->get(['id', 'title', 'weight', 'used_count'])
            ->map(fn ($t) => ['id' => $t->id, 'title' => $t->title, 'weight' => $t->weight, 'used' => $t->used_count])->all();

        return view('title-lists.form', ['list' => $titleList, 'rows' => $rows]);
    }

    public function update(Request $request, TitleList $titleList): RedirectResponse
    {
        $this->own($titleList);
        $name = $this->validName($request, $titleList);
        $rows = $this->validRows($request);
        DB::transaction(function () use ($titleList, $name, $rows, $request) {
            $titleList->update(['name' => $name]);
            $titleList->syncRows($rows);
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

    private function validName(Request $request, ?TitleList $list = null): string
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120',
                Rule::unique('title_lists', 'name')->where('user_id', $request->user()->id)->ignore($list?->id)],
        ], [], ['name' => __('ui.name')])['name'];
    }

    /**
     * The table is sent as one JSON field "rows" (so big lists do not hit PHP's max_input_vars):
     * [{"id": 12|null, "title": "...", "weight": 1}, ...]
     */
    private function validRows(Request $request): array
    {
        $rows = json_decode((string) $request->input('rows'), true);
        $v = Validator::make(['rows' => is_array($rows) ? array_values($rows) : null], [
            'rows' => ['required', 'array', 'min:1', 'max:'.self::MAX_ROWS],
            'rows.*.id' => ['nullable', 'integer'],
            'rows.*.title' => ['required', 'string', 'max:500'],
            'rows.*.weight' => ['required', 'numeric', 'min:0', 'max:'.Title::MAX_WEIGHT],
        ], [], [
            'rows' => __('ui.titles'), 'rows.*.title' => __('ui.title'), 'rows.*.weight' => __('ui.weight'),
        ]);

        return $v->validate()['rows']; // on error, old('rows') gives the table back to the form
    }
}
