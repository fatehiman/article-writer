@extends('layouts.app')
@section('title', __('ui.tones'))

@section('content')
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0 me-auto"><i class="bi bi-mic text-amber me-2"></i>{{ __('ui.tones') }}</h1>
    {{ icon_btn('plus', __('ui.new_tone'), 'green', 'href="'.route('admin.tones.create').'"', 'a') }}
</div>

<div class="card aw-card">
    <div class="table-responsive">
        <table class="table aw-table align-middle mb-0">
            <thead><tr><th>{{ __('ui.name') }}</th><th>{{ __('ui.ai_instruction') }}</th><th></th></tr></thead>
            <tbody>
            @foreach ($tones as $t)
                <tr class="{{ $t->is_active ? '' : 'opacity-50' }}">
                    <td><span class="aw-ico-box sm tone-amber me-2"><i class="bi bi-mic"></i></span>{{ $t->name }}
                        @foreach ($t->labels ?? [] as $l => $label) <span class="small text-muted">· {{ $label }}</span> @endforeach</td>
                    <td class="small text-muted">{{ $t->instruction }}</td>
                    <td class="text-end text-nowrap">
                        {{ icon_btn('pencil', __('ui.edit'), 'amber', 'href="'.route('admin.tones.edit', $t).'"', 'a', 'sm') }}
                        <form method="post" action="{{ route('admin.tones.destroy', $t) }}" class="d-inline" data-confirm="{{ __('ui.confirm_delete') }}">
                            @csrf @method('DELETE')
                            {{ icon_btn('trash', __('ui.delete'), 'red', 'type="submit"', 'button', 'sm') }}
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
