@extends('layouts.app')
@section('title', __('ui.profile'))

@php $u = auth()->user(); @endphp

@section('content')
<h1 class="h4 mb-3"><i class="bi bi-person-gear text-violet me-2"></i>{{ __('ui.profile') }}</h1>

<form method="post" action="{{ route('profile.update') }}" class="row g-3">
    @csrf @method('PUT')
    <div class="col-lg-6">
        <div class="card aw-card h-100"><div class="card-body">
            <div class="mb-3"><label class="form-label">{{ __('ui.username') }}</label><input class="form-control aw-mono" value="{{ $u->username }}" disabled dir="ltr"></div>
            <div class="mb-3"><label class="form-label" for="name">{{ __('ui.full_name') }}</label><input class="form-control" id="name" name="name" value="{{ old('name', $u->name) }}" required></div>
            <div class="mb-3"><label class="form-label" for="email">{{ __('ui.email') }}</label><input class="form-control" type="email" id="email" name="email" value="{{ old('email', $u->email) }}" dir="ltr"></div>
            <div class="row g-3">
                <div class="col-sm-6">
                    <label class="form-label" for="locale">{{ __('ui.ui_language') }}</label>
                    <select class="form-select" id="locale" name="locale">
                        @foreach (config('languages.ui') as $code => $l)
                            <option value="{{ $code }}" @selected(old('locale', $u->locale) === $code)>{{ $l['native'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="timezone">{{ __('ui.timezone') }}</label>
                    <select class="form-select" id="timezone" name="timezone">
                        @foreach (DateTimeZone::listIdentifiers() as $tz)
                            <option value="{{ $tz }}" @selected(old('timezone', $u->timezone) === $tz)>{{ $tz }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div></div>
    </div>
    <div class="col-lg-6">
        <div class="card aw-card h-100"><div class="card-body">
            <h2 class="h6 mb-3"><i class="bi bi-shield-lock text-rose me-1"></i>{{ __('ui.change_password') }}</h2>
            <div class="mb-3"><label class="form-label" for="current_password">{{ __('ui.current_password') }}</label><input class="form-control" type="password" id="current_password" name="current_password" autocomplete="current-password" dir="ltr"></div>
            <div class="mb-3"><label class="form-label" for="password">{{ __('ui.new_password') }}</label><input class="form-control" type="password" id="password" name="password" autocomplete="new-password" dir="ltr"></div>
            <div class="mb-0"><label class="form-label" for="password_confirmation">{{ __('ui.repeat_password') }}</label><input class="form-control" type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" dir="ltr"></div>
            <div class="form-text">{{ __('ui.password_keep') }}</div>
        </div></div>
    </div>
    <div class="col-12"><button class="btn aw-btn-primary px-4" type="submit"><i class="bi bi-check2 me-1"></i>{{ __('ui.save') }}</button></div>
</form>
@endsection
