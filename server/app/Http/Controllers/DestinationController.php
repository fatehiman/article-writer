<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Publishing\PublisherRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DestinationController extends Controller
{
    public function __construct(private PublisherRegistry $registry)
    {
    }

    public function index(Request $request): View
    {
        $destinations = $request->user()->destinations()->withCount('services')->orderBy('name')->get();

        return view('destinations.index', ['destinations' => $destinations, 'publishers' => $this->registry->all()]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $driver = (string) $request->input('driver');
        if (! $this->registry->has($driver)) {
            return redirect()->route('destinations.index');
        }

        return view('destinations.form', [
            'destination' => new Destination(['driver' => $driver, 'is_active' => true]),
            'publisher' => $this->registry->get($driver),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $driver = (string) $request->input('driver');
        abort_unless($this->registry->has($driver), 404);
        $data = $this->validated($request, $driver, []);
        $request->user()->destinations()->create($data + ['driver' => $driver]);

        return redirect()->route('destinations.index')->with('ok', __('ui.saved'));
    }

    public function edit(Destination $destination): View
    {
        $this->own($destination);

        return view('destinations.form', ['destination' => $destination, 'publisher' => $destination->publisher()]);
    }

    public function update(Request $request, Destination $destination): RedirectResponse
    {
        $this->own($destination);
        $destination->update($this->validated($request, $destination->driver, $destination->config ?? []));

        return redirect()->route('destinations.index')->with('ok', __('ui.saved'));
    }

    public function destroy(Destination $destination): RedirectResponse
    {
        $this->own($destination);
        $destination->delete();

        return redirect()->route('destinations.index')->with('ok', __('ui.deleted'));
    }

    /** Secret fields left empty on edit keep their old value. */
    private function validated(Request $request, string $driver, array $old): array
    {
        $fields = $this->registry->get($driver)->fields();
        $rules = ['name' => ['required', 'string', 'max:120']];
        foreach ($fields as $name => $f) {
            $keepSecret = ! empty($f['secret']) && ! empty($old[$name]);
            $rule = [($f['required'] ?? false) && ! $keepSecret ? 'required' : 'nullable'];
            $rule = array_merge($rule, match ($f['type']) {
                'url' => ['url:http,https', 'max:1000'],
                'select' => [Rule::in($f['options'])],
                'checkbox' => [],
                'textarea' => ['string', 'max:5000'],
                default => ['string', 'max:1000'],
            });
            $rules["config.{$name}"] = $rule;
        }
        $labels = collect($fields)->mapWithKeys(fn ($f, $n) => ["config.{$n}" => __("publishers.{$driver}.{$n}")])->all();
        $v = $request->validate($rules, [], $labels);

        $config = [];
        foreach ($fields as $name => $f) {
            $value = $v['config'][$name] ?? null;
            if ($f['type'] === 'checkbox') {
                $value = $request->boolean("config.{$name}");
            } elseif (! empty($f['secret']) && ($value === null || $value === '')) {
                $value = $old[$name] ?? null;
            }
            $config[$name] = is_string($value) ? trim($value) : $value;
        }

        return ['name' => $v['name'], 'config' => $config, 'is_active' => $request->boolean('is_active')];
    }
}
