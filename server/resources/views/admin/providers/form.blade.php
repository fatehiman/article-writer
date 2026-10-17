@extends('layouts.app')
@section('title', $provider->exists ? $provider->name : __('ui.new_provider'))

@section('content')
<h1 class="h4 mb-3"><i class="bi bi-cpu text-mint me-2"></i>{{ $provider->exists ? $provider->name : __('ui.new_provider') }}</h1>

<div class="row"><div class="col-lg-7">
<form method="post" action="{{ $provider->exists ? route('admin.providers.update', $provider) : route('admin.providers.store') }}" class="card aw-card" autocomplete="off">
    @csrf
    @if ($provider->exists) @method('PUT') @endif
    <div class="card-body">
        <label class="form-label" for="name">{{ __('ui.name') }}</label>
        <input class="form-control mb-3" id="name" name="name" value="{{ old('name', $provider->name) }}" required placeholder="DeepSeek">
        <label class="form-label" for="driver">{{ __('ui.driver') }}</label>
        <select class="form-select mb-1" id="driver" name="driver" data-base-urls='@json(collect(config('ai.drivers'))->map(fn ($d) => $d['base_url']))'>
            @foreach (config('ai.drivers') as $key => $d)
                <option value="{{ $key }}" @selected(old('driver', $provider->driver) === $key)>{{ $d['label'] }} [{{ implode(', ', $d['kinds']) }}]</option>
            @endforeach
        </select>
        <div class="form-text mb-3">{{ __('ui.driver_help') }}</div>
        <label class="form-label" for="base_url">{{ __('ui.base_url') }}</label>
        <input class="form-control mb-3" type="url" id="base_url" name="base_url" value="{{ old('base_url', $provider->base_url) }}" dir="ltr">
        <label class="form-label" for="api_key">{{ __('ui.api_key') }}</label>
        <input class="form-control aw-mono mb-1" type="password" id="api_key" name="api_key" value="" autocomplete="new-password" dir="ltr"
               placeholder="{{ $provider->api_key ? __('ui.keep_secret').' ('.$provider->maskedKey().')' : '' }}">
        <div class="form-text mb-3">{{ __('ui.api_key_help') }}</div>
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked(old('is_active', $provider->is_active))>
            <label class="form-check-label" for="is_active">{{ __('ui.active') }}</label>
        </div>
    </div>
    <div class="card-footer bg-transparent d-flex gap-2">
        <button class="btn aw-btn-primary px-4" type="submit"><i class="bi bi-check2 me-1"></i>{{ __('ui.save') }}</button>
        <a class="btn btn-light border" href="{{ route('admin.providers.index') }}">{{ __('ui.cancel') }}</a>
    </div>
</form>
</div></div>
@endsection
