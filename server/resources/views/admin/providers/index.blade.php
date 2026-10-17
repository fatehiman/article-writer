@extends('layouts.app')
@section('title', __('ui.ai_providers'))

@section('content')
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0 me-auto"><i class="bi bi-cpu text-mint me-2"></i>{{ __('ui.ai_providers') }}</h1>
    {{ icon_btn('plus', __('ui.new_provider'), 'green', 'href="'.route('admin.providers.create').'"', 'a') }}
</div>
<p class="text-muted small">{{ __('ui.providers_help') }}</p>

<div class="card aw-card">
    <div class="table-responsive">
        <table class="table aw-table align-middle mb-0">
            <thead><tr><th>{{ __('ui.name') }}</th><th>{{ __('ui.driver') }}</th><th>{{ __('ui.api_key') }}</th><th class="text-center">{{ __('ui.ai_agents') }}</th><th></th></tr></thead>
            <tbody>
            @forelse ($providers as $p)
                <tr class="{{ $p->is_active ? '' : 'opacity-50' }}">
                    <td><div class="fw-medium">{{ $p->name }}</div><div class="small text-muted" dir="ltr">{{ $p->base_url }}</div></td>
                    <td class="small">{{ $p->driverLabel() }}</td>
                    <td>@if ($p->api_key) <span class="aw-mono small" dir="ltr">{{ $p->maskedKey() }}</span> @else <span class="badge aw-badge tone-rose">{{ __('ui.missing') }}</span> @endif</td>
                    <td class="text-center">{{ $p->agents_count }}</td>
                    <td class="text-end text-nowrap">
                        {{ icon_btn('pencil', __('ui.edit'), 'amber', 'href="'.route('admin.providers.edit', $p).'"', 'a', 'sm') }}
                        <form method="post" action="{{ route('admin.providers.destroy', $p) }}" class="d-inline" data-confirm="{{ __('ui.confirm_delete_provider') }}">
                            @csrf @method('DELETE')
                            {{ icon_btn('trash', __('ui.delete'), 'red', 'type="submit"', 'button', 'sm') }}
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">{{ __('ui.nothing_yet') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
