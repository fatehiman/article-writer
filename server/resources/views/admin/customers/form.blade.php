@extends('layouts.app')
@section('title', $user->exists ? $user->name : __('ui.new_customer'))

@section('content')
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0 me-auto"><i class="bi bi-person text-sky me-2"></i>{{ $user->exists ? $user->name : __('ui.new_customer') }}</h1>
    @if ($user->exists)
        <span class="badge aw-badge tone-peach fs-6"><i class="bi bi-wallet2 me-1"></i>{{ money($user->wallet_balance) }}</span>
        {{ icon_btn('wallet', __('ui.add_money'), 'green', 'data-bs-toggle="modal" data-bs-target="#walletModal"') }}
    @endif
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <form method="post" action="{{ $user->exists ? route('admin.customers.update', $user) : route('admin.customers.store') }}" class="card aw-card" autocomplete="off">
            @csrf
            @if ($user->exists) @method('PUT') @endif
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6"><label class="form-label" for="name">{{ __('ui.full_name') }}</label>
                        <input class="form-control" id="name" name="name" value="{{ old('name', $user->name) }}" required></div>
                    <div class="col-sm-6"><label class="form-label" for="username">{{ __('ui.username') }}</label>
                        <input class="form-control aw-mono" id="username" name="username" value="{{ old('username', $user->username) }}" required pattern="[a-zA-Z0-9._\-]+" dir="ltr" autocapitalize="none"></div>
                    <div class="col-sm-6"><label class="form-label" for="email">{{ __('ui.email') }}</label>
                        <input class="form-control" type="email" id="email" name="email" value="{{ old('email', $user->email) }}" dir="ltr"></div>
                    <div class="col-sm-6"><label class="form-label" for="password">{{ __('ui.password') }}</label>
                        <input class="form-control" type="password" id="password" name="password" {{ $user->exists ? '' : 'required' }} autocomplete="new-password" dir="ltr"
                               placeholder="{{ $user->exists ? __('ui.password_keep') : '' }}"></div>
                    <div class="col-sm-4"><label class="form-label" for="role">{{ __('ui.role') }}</label>
                        <select class="form-select" id="role" name="role">
                            @foreach (['customer', 'admin'] as $r)
                                <option value="{{ $r }}" @selected(old('role', $user->role) === $r)>{{ __('ui.role_'.$r) }}</option>
                            @endforeach
                        </select></div>
                    <div class="col-sm-4"><label class="form-label" for="locale">{{ __('ui.ui_language') }}</label>
                        <select class="form-select" id="locale" name="locale">
                            @foreach (config('languages.ui') as $code => $l)
                                <option value="{{ $code }}" @selected(old('locale', $user->locale) === $code)>{{ $l['native'] }}</option>
                            @endforeach
                        </select></div>
                    <div class="col-sm-4"><label class="form-label" for="timezone">{{ __('ui.timezone') }}</label>
                        <select class="form-select" id="timezone" name="timezone">
                            @foreach (DateTimeZone::listIdentifiers() as $tz)
                                <option value="{{ $tz }}" @selected(old('timezone', $user->timezone ?: 'UTC') === $tz)>{{ $tz }}</option>
                            @endforeach
                        </select></div>
                    <div class="col-12"><label class="form-label" for="notes">{{ __('ui.notes') }}</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2">{{ old('notes', $user->notes) }}</textarea></div>
                    <div class="col-12"><div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked(old('is_active', $user->is_active))>
                        <label class="form-check-label" for="is_active">{{ __('ui.can_login') }}</label></div></div>
                </div>
            </div>
            <div class="card-footer bg-transparent d-flex gap-2">
                <button class="btn aw-btn-primary px-4" type="submit"><i class="bi bi-check2 me-1"></i>{{ __('ui.save') }}</button>
                <a class="btn btn-light border" href="{{ route('admin.customers.index') }}">{{ __('ui.cancel') }}</a>
            </div>
        </form>
    </div>
    @if ($user->exists)
        <div class="col-lg-5">
            <div class="card aw-card">
                <div class="card-header aw-card-header"><i class="bi bi-receipt text-mint me-2"></i>{{ __('ui.transactions') }}</div>
                <div class="table-responsive aw-scroll">
                    <table class="table table-sm aw-table mb-0 small">
                        <tbody>
                        @forelse ($transactions as $t)
                            <tr>
                                <td class="text-nowrap aw-mono">{{ local_time($t->created_at, 'm-d H:i') }}</td>
                                <td><span class="badge aw-badge tone-{{ $t->amount >= 0 ? 'mint' : 'rose' }}">{{ __('ui.tx_'.$t->type) }}</span> {{ \Illuminate\Support\Str::limit($t->description, 40) }}</td>
                                <td class="text-end aw-mono" dir="ltr">{{ $t->amount >= 0 ? '+' : '' }}{{ money($t->amount) }}</td>
                            </tr>
                        @empty
                            <tr><td class="text-center text-muted py-3">{{ __('ui.no_transactions') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@push('modals')
    @if ($user->exists) @include('admin.customers._wallet-modal') @endif
@endpush
