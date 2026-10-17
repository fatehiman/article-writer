@extends('layouts.app')
@section('title', $agent->exists ? $agent->name : __('ui.new_agent'))

@section('content')
<h1 class="h4 mb-3"><i class="bi bi-robot text-violet me-2"></i>{{ $agent->exists ? $agent->name : __('ui.new_agent') }}</h1>

<div class="row g-3">
<div class="col-lg-7">
<form method="post" action="{{ $agent->exists ? route('admin.agents.update', $agent) : route('admin.agents.store') }}" class="card aw-card">
    @csrf
    @if ($agent->exists) @method('PUT') @endif
    <div class="card-body">
        <div class="row g-3">
            <div class="col-sm-6"><label class="form-label" for="name">{{ __('ui.name') }}</label>
                <input class="form-control" id="name" name="name" value="{{ old('name', $agent->name) }}" required placeholder="DeepSeek Flash"></div>
            <div class="col-sm-6"><label class="form-label" for="kind">{{ __('ui.kind') }}</label>
                <select class="form-select" id="kind" name="kind">
                    @foreach (config('ai.kinds') as $k)
                        <option value="{{ $k }}" @selected(old('kind', $agent->kind) === $k)>{{ __('ui.kind_'.$k) }}</option>
                    @endforeach
                </select></div>
            <div class="col-sm-6"><label class="form-label" for="ai_provider_id">{{ __('ui.provider') }}</label>
                <select class="form-select" id="ai_provider_id" name="ai_provider_id" required>
                    @foreach ($providers as $p)
                        <option value="{{ $p->id }}" @selected((int) old('ai_provider_id', $agent->ai_provider_id) === $p->id)>{{ $p->name }} ({{ implode(', ', config('ai.drivers.'.$p->driver.'.kinds', [])) }})</option>
                    @endforeach
                </select></div>
            <div class="col-sm-6"><label class="form-label" for="model">{{ __('ui.model') }}</label>
                <input class="form-control aw-mono" id="model" name="model" value="{{ old('model', $agent->model) }}" required dir="ltr" placeholder="deepseek-flash"></div>
            <div class="col-12"><label class="form-label" for="description">{{ __('ui.description') }}</label>
                <input class="form-control" id="description" name="description" value="{{ old('description', $agent->description) }}" maxlength="1000"></div>
            <div class="col-sm-4"><label class="form-label" for="price">{{ __('ui.price') }} ({{ \App\Models\Setting::get('currency') }})</label>
                <input class="form-control" type="number" step="0.0001" min="0" id="price" name="price" value="{{ old('price', $agent->price) }}" required dir="ltr"></div>
            <div class="col-sm-5"><label class="form-label" for="price_unit">{{ __('ui.price_unit') }}</label>
                <select class="form-select" id="price_unit" name="price_unit">
                    @foreach (config('ai.price_units') as $u => $kinds)
                        <option value="{{ $u }}" @selected(old('price_unit', $agent->price_unit) === $u)>{{ __('ui.unit_'.$u) }} ({{ implode(', ', $kinds) }})</option>
                    @endforeach
                </select></div>
            <div class="col-sm-3"><label class="form-label" for="sort">{{ __('ui.sort') }}</label>
                <input class="form-control" type="number" id="sort" name="sort" value="{{ old('sort', $agent->sort) }}" dir="ltr"></div>
            <div class="col-12"><label class="form-label" for="options">{{ __('ui.options_json') }}</label>
                <textarea class="form-control aw-mono" id="options" name="options" rows="5" dir="ltr">{{ old('options', $agent->options ? json_encode($agent->options, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '') }}</textarea></div>
            <div class="col-12"><div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked(old('is_active', $agent->is_active))>
                <label class="form-check-label" for="is_active">{{ __('ui.agent_active') }}</label></div></div>
        </div>
    </div>
    <div class="card-footer bg-transparent d-flex gap-2">
        <button class="btn aw-btn-primary px-4" type="submit"><i class="bi bi-check2 me-1"></i>{{ __('ui.save') }}</button>
        <a class="btn btn-light border" href="{{ route('admin.agents.index') }}">{{ __('ui.cancel') }}</a>
    </div>
</form>
</div>
<div class="col-lg-5">
    <div class="card aw-card"><div class="card-header aw-card-header"><i class="bi bi-info-circle text-violet me-2"></i>{{ __('ui.options_json') }}</div>
        <div class="card-body small">
            <p>{{ __('ui.options_help') }}</p>
            <pre class="aw-pre" dir="ltr">// text
{ "max_tokens": 16000, "temperature": 1.0 }

// image (SiliconFlow / OpenAI): sizes the model accepts.
// The best match is used, then cropped to the exact size.
{ "sizes": ["1328x1328", "1664x928", "928x1664"] }

// OpenAI images
{ "sizes": ["1024x1024", "1536x1024", "1024x1536"], "quality": "medium" }</pre>
        </div>
    </div>
</div>
</div>
@endsection
