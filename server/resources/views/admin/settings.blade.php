@extends('layouts.app')
@section('title', __('ui.settings'))

@section('content')
<h1 class="h4 mb-3"><i class="bi bi-gear text-peach me-2"></i>{{ __('ui.settings') }}</h1>

<div class="row"><div class="col-lg-6">
<form method="post" action="{{ route('admin.settings.update') }}" class="card aw-card">
    @csrf @method('PUT')
    <div class="card-body">
        <label class="form-label" for="currency">{{ __('ui.currency') }}</label>
        <input class="form-control mb-1" id="currency" name="currency" value="{{ old('currency', \App\Models\Setting::get('currency')) }}" required maxlength="12" dir="ltr">
        <div class="form-text mb-3">{{ __('ui.currency_help') }}</div>
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" id="low_balance_block" name="low_balance_block" value="1" @checked(\App\Models\Setting::get('low_balance_block') === '1')>
            <label class="form-check-label" for="low_balance_block">{{ __('ui.low_balance_block') }}</label>
        </div>
    </div>
    <div class="card-footer bg-transparent">
        <button class="btn aw-btn-primary px-4" type="submit"><i class="bi bi-check2 me-1"></i>{{ __('ui.save') }}</button>
    </div>
</form>
</div></div>
@endsection
