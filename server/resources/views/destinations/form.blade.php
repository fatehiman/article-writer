@extends('layouts.app')
@php $key = $publisher->key(); $config = $destination->config ?? []; @endphp
@section('title', __('publishers.'.$key.'.name'))

@section('content')
<h1 class="h4 mb-3">
    <span class="aw-ico-box tone-{{ $publisher->tone() }} me-2"><i class="bi {{ $publisher->icon() }}"></i></span>{{ __('publishers.'.$key.'.name') }}
</h1>

<div class="row g-3">
    <div class="col-lg-7">
        <form method="post" action="{{ $destination->exists ? route('destinations.update', $destination) : route('destinations.store') }}" class="card aw-card">
            @csrf
            @if ($destination->exists) @method('PUT') @else <input type="hidden" name="driver" value="{{ $key }}"> @endif
            <div class="card-body">
                <label class="form-label" for="name">{{ __('ui.name') }}</label>
                <input class="form-control mb-3" id="name" name="name" value="{{ old('name', $destination->name) }}" required maxlength="120" placeholder="{{ __('ui.destination_name_ph') }}">

                @foreach ($publisher->fields() as $name => $f)
                    @php
                        $id = 'cfg_'.$name;
                        $label = __('publishers.'.$key.'.'.$name);
                        $helpKey = 'publishers.'.$key.'.'.$name.'_help';
                        $value = old('config.'.$name, $config[$name] ?? ($f['default'] ?? ''));
                        $hasSecret = ! empty($f['secret']) && ! empty($config[$name]);
                    @endphp
                    <div class="mb-3">
                        @if ($f['type'] === 'checkbox')
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="{{ $id }}" name="config[{{ $name }}]" value="1" @checked(old('config.'.$name, $config[$name] ?? ($f['default'] ?? false)))>
                                <label class="form-check-label" for="{{ $id }}">{{ $label }}</label>
                            </div>
                        @else
                            <label class="form-label" for="{{ $id }}">{{ $label }} @if (! empty($f['required'])) <span class="text-danger">*</span> @endif</label>
                            @if ($f['type'] === 'select')
                                <select class="form-select" id="{{ $id }}" name="config[{{ $name }}]">
                                    @foreach ($f['options'] as $opt)
                                        <option value="{{ $opt }}" @selected($value === $opt)>{{ __('publishers.opt_'.$opt) }}</option>
                                    @endforeach
                                </select>
                            @elseif ($f['type'] === 'textarea')
                                <textarea class="form-control" id="{{ $id }}" name="config[{{ $name }}]" rows="3" dir="ltr">{{ $value }}</textarea>
                            @elseif ($f['type'] === 'password')
                                <input class="form-control aw-mono" type="password" id="{{ $id }}" name="config[{{ $name }}]" value="" dir="ltr" autocomplete="new-password"
                                       placeholder="{{ $hasSecret ? __('ui.keep_secret') : '' }}">
                            @else
                                <input class="form-control" type="{{ $f['type'] === 'url' ? 'url' : 'text' }}" id="{{ $id }}" name="config[{{ $name }}]" value="{{ $value }}" dir="ltr">
                            @endif
                        @endif
                        @if (\Illuminate\Support\Facades\Lang::has($helpKey))
                            <div class="form-text">{!! __($helpKey) !!}</div>
                        @endif
                    </div>
                @endforeach

                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked(old('is_active', $destination->is_active))>
                    <label class="form-check-label" for="is_active">{{ __('ui.active') }}</label>
                </div>
            </div>
            <div class="card-footer bg-transparent d-flex gap-2">
                <button class="btn aw-btn-primary px-4" type="submit"><i class="bi bi-check2 me-1"></i>{{ __('ui.save') }}</button>
                <a class="btn btn-light border" href="{{ route('destinations.index') }}">{{ __('ui.cancel') }}</a>
            </div>
        </form>
    </div>
    <div class="col-lg-5">
        <div class="card aw-card">
            <div class="card-header aw-card-header"><i class="bi bi-info-circle text-violet me-2"></i>{{ __('ui.how_it_works') }}</div>
            <div class="card-body small aw-help-text">{!! __('publishers.'.$key.'.guide') !!}</div>
        </div>
    </div>
</div>
@endsection
