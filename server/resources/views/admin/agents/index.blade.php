@extends('layouts.app')
@section('title', __('ui.ai_agents'))

@section('content')
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0 me-auto"><i class="bi bi-robot text-violet me-2"></i>{{ __('ui.ai_agents') }}</h1>
    {{ icon_btn('plus', __('ui.new_agent'), 'green', 'href="'.route('admin.agents.create').'"', 'a') }}
</div>
<p class="text-muted small">{{ __('ui.agents_help') }}</p>

<div class="card aw-card">
    <div class="table-responsive">
        <table class="table aw-table align-middle mb-0">
            <thead><tr><th>{{ __('ui.name') }}</th><th>{{ __('ui.kind') }}</th><th>{{ __('ui.model') }}</th><th>{{ __('ui.provider') }}</th><th class="text-end">{{ __('ui.price') }}</th><th></th></tr></thead>
            <tbody>
            @forelse ($agents as $a)
                <tr class="{{ $a->is_active ? '' : 'opacity-50' }}">
                    <td><div class="fw-medium">{{ $a->name }}</div><div class="small text-muted">{{ $a->description }}</div></td>
                    <td><span class="badge aw-badge tone-{{ ['text' => 'sky', 'image' => 'rose', 'video' => 'amber'][$a->kind] ?? 'gray' }}">{{ __('ui.kind_'.$a->kind) }}</span></td>
                    <td class="small aw-mono" dir="ltr">{{ $a->model }}</td>
                    <td class="small">{{ $a->provider?->name }}</td>
                    <td class="text-end small text-nowrap">{{ $a->priceLabel() }}</td>
                    <td class="text-end text-nowrap">
                        {{ icon_btn('pencil', __('ui.edit'), 'amber', 'href="'.route('admin.agents.edit', $a).'"', 'a', 'sm') }}
                        <form method="post" action="{{ route('admin.agents.destroy', $a) }}" class="d-inline" data-confirm="{{ __('ui.confirm_delete') }}">
                            @csrf @method('DELETE')
                            {{ icon_btn('trash', __('ui.delete'), 'red', 'type="submit"', 'button', 'sm') }}
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">{{ __('ui.nothing_yet') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
