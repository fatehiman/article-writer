@extends('layouts.app')
@section('title', __('ui.customers'))

@section('content')
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0 me-auto"><i class="bi bi-people text-sky me-2"></i>{{ __('ui.customers') }}</h1>
    {{ icon_btn('plus', __('ui.new_customer'), 'green', 'href="'.route('admin.customers.create').'"', 'a') }}
</div>

<form method="get" class="mb-3">
    <div class="input-group aw-search">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input class="form-control" name="q" value="{{ $q }}" placeholder="{{ __('ui.search_users') }}">
    </div>
</form>

<div class="card aw-card">
    <div class="table-responsive">
        <table class="table aw-table align-middle mb-0">
            <thead><tr>
                <th>{{ __('ui.name') }}</th><th>{{ __('ui.role') }}</th><th class="text-center">{{ __('ui.services') }}</th>
                <th class="text-center">{{ __('ui.articles') }}</th><th class="text-end">{{ __('ui.balance') }}</th><th>{{ __('ui.last_login') }}</th><th></th>
            </tr></thead>
            <tbody>
            @foreach ($users as $u)
                <tr class="{{ $u->is_active ? '' : 'opacity-50' }}">
                    <td><div class="fw-medium">{{ $u->name }}</div><div class="small text-muted aw-mono">{{ $u->username }}</div></td>
                    <td><span class="badge aw-badge tone-{{ $u->isAdmin() ? 'rose' : 'sky' }}">{{ __('ui.role_'.$u->role) }}</span></td>
                    <td class="text-center">{{ $u->services_count }}</td>
                    <td class="text-center">{{ $u->articles_count }}</td>
                    <td class="text-end text-nowrap aw-mono" dir="ltr">{{ money($u->wallet_balance) }}</td>
                    <td class="small text-muted text-nowrap">{{ local_time($u->last_login_at) }}</td>
                    <td class="text-end text-nowrap">
                        {{ icon_btn('wallet', __('ui.add_money'), 'green', 'data-bs-toggle="modal" data-bs-target="#walletModal" data-wallet-url="'.route('admin.customers.wallet', $u).'" data-wallet-name="'.e($u->name).'"', 'button', 'sm') }}
                        {{ icon_btn('pencil', __('ui.edit'), 'amber', 'href="'.route('admin.customers.edit', $u).'"', 'a', 'sm') }}
                        @unless ($u->is(auth()->user()))
                            <form method="post" action="{{ route('admin.customers.destroy', $u) }}" class="d-inline" data-confirm="{{ __('ui.confirm_delete_user') }}">
                                @csrf @method('DELETE')
                                {{ icon_btn('trash', __('ui.delete'), 'red', 'type="submit"', 'button', 'sm') }}
                            </form>
                        @endunless
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $users->links() }}</div>
@endsection

@push('modals')
    @include('admin.customers._wallet-modal')
@endpush
