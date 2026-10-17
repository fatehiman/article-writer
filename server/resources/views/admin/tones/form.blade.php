@extends('layouts.app')
@section('title', $tone->exists ? $tone->name : __('ui.new_tone'))

@section('content')
<h1 class="h4 mb-3"><i class="bi bi-mic text-amber me-2"></i>{{ $tone->exists ? $tone->name : __('ui.new_tone') }}</h1>

<div class="row"><div class="col-lg-6">
<form method="post" action="{{ $tone->exists ? route('admin.tones.update', $tone) : route('admin.tones.store') }}" class="card aw-card">
    @csrf
    @if ($tone->exists) @method('PUT') @endif
    <div class="card-body">
        <label class="form-label" for="name">{{ __('ui.name') }} (English)</label>
        <input class="form-control mb-3" id="name" name="name" value="{{ old('name', $tone->name) }}" required placeholder="Friendly" dir="ltr">
        @foreach (config('languages.ui') as $code => $l)
            @continue($code === 'en')
            <label class="form-label" for="label_{{ $code }}">{{ __('ui.name') }} ({{ $l['native'] }})</label>
            <input class="form-control mb-3" id="label_{{ $code }}" name="labels[{{ $code }}]" value="{{ old('labels.'.$code, $tone->labels[$code] ?? '') }}" dir="{{ $l['dir'] }}">
        @endforeach
        <label class="form-label" for="instruction">{{ __('ui.ai_instruction') }}</label>
        <textarea class="form-control mb-1" id="instruction" name="instruction" rows="3" dir="ltr">{{ old('instruction', $tone->instruction) }}</textarea>
        <div class="form-text mb-3">{{ __('ui.tone_instruction_help') }}</div>
        <div class="row g-3 align-items-center">
            <div class="col-4"><label class="form-label" for="sort">{{ __('ui.sort') }}</label>
                <input class="form-control" type="number" id="sort" name="sort" value="{{ old('sort', $tone->sort) }}" dir="ltr"></div>
            <div class="col-8 pt-4"><div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked(old('is_active', $tone->is_active))>
                <label class="form-check-label" for="is_active">{{ __('ui.active') }}</label></div></div>
        </div>
    </div>
    <div class="card-footer bg-transparent d-flex gap-2">
        <button class="btn aw-btn-primary px-4" type="submit"><i class="bi bi-check2 me-1"></i>{{ __('ui.save') }}</button>
        <a class="btn btn-light border" href="{{ route('admin.tones.index') }}">{{ __('ui.cancel') }}</a>
    </div>
</form>
</div></div>
@endsection
