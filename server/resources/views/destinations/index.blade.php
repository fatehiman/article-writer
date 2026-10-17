@extends('layouts.app')
@section('title', __('ui.destinations'))

@section('content')
<h1 class="h4 mb-2"><i class="bi bi-send text-rose me-2"></i>{{ __('ui.destinations') }}</h1>
<p class="text-muted small mb-3">{{ __('ui.destinations_help') }}</p>

<h2 class="h6 text-muted mb-2">{{ __('ui.add_destination') }}</h2>
<div class="row g-2 mb-4">
    @foreach ($publishers as $key => $p)
        <div class="col-6 col-md-4 col-xl-2">
            <a href="{{ route('destinations.create', ['driver' => $key]) }}" class="card aw-card aw-card-link text-decoration-none h-100">
                <div class="card-body text-center py-3">
                    <span class="aw-ico-box tone-{{ $p->tone() }} mb-2"><i class="bi {{ $p->icon() }}"></i></span>
                    <div class="fw-medium text-body small">{{ __('publishers.'.$key.'.name') }}</div>
                </div>
            </a>
        </div>
    @endforeach
</div>

@if ($destinations->isNotEmpty())
    <div class="card aw-card">
        <div class="table-responsive">
            <table class="table aw-table align-middle mb-0">
                <thead><tr><th>{{ __('ui.name') }}</th><th>{{ __('ui.type') }}</th><th>{{ __('ui.target') }}</th><th class="text-center">{{ __('ui.services') }}</th><th></th></tr></thead>
                <tbody>
                @foreach ($destinations as $d)
                    @php $p = $publishers[$d->driver] ?? null; @endphp
                    <tr class="{{ $d->is_active ? '' : 'opacity-50' }}">
                        <td><span class="aw-ico-box sm tone-{{ $p?->tone() ?? 'gray' }} me-2"><i class="bi {{ $p?->icon() ?? 'bi-question' }}"></i></span>{{ $d->name }}</td>
                        <td>{{ __('publishers.'.$d->driver.'.name') }}</td>
                        <td class="small text-muted text-break" dir="ltr">{{ $p?->target($d->config ?? []) }}</td>
                        <td class="text-center">{{ $d->services_count }}</td>
                        <td class="text-end text-nowrap">
                            {{ icon_btn('pencil', __('ui.edit'), 'amber', 'href="'.route('destinations.edit', $d).'"', 'a', 'sm') }}
                            <form method="post" action="{{ route('destinations.destroy', $d) }}" class="d-inline" data-confirm="{{ __('ui.confirm_delete') }}">
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
@endif
@endsection
