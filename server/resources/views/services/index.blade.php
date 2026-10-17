@extends('layouts.app')
@section('title', __('ui.services'))

@section('content')
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0 me-auto"><i class="bi bi-lightning-charge text-amber me-2"></i>{{ __('ui.services') }}</h1>
    {{ icon_btn('plus', __('ui.new_service'), 'green', 'href="'.route('services.create').'"', 'a') }}
</div>

@if ($services->isEmpty())
    <div class="card aw-card">
        <div class="card-body text-center py-5">
            <div class="aw-empty tone-amber mb-3"><i class="bi bi-lightning-charge"></i></div>
            <p class="mb-1">{{ __('ui.no_services') }}</p>
            <p class="text-muted small mb-3">{{ __('ui.no_services_hint') }}</p>
            <a href="{{ route('services.create') }}" class="btn aw-btn-primary"><i class="bi bi-plus-lg me-1"></i>{{ __('ui.new_service') }}</a>
        </div>
    </div>
@else
    <div class="row g-3">
        @foreach ($services as $service)
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card aw-card aw-card-link h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-start gap-2">
                            <span class="aw-ico-box tone-{{ $service->is_active ? 'amber' : 'gray' }}"><i class="bi bi-lightning-charge"></i></span>
                            <div class="flex-grow-1 min-w-0">
                                <a href="{{ route('services.show', $service) }}" class="fw-semibold text-body text-truncate d-block text-decoration-none stretched-link">{{ $service->name }}</a>
                                <div class="small text-muted text-truncate">
                                    <i class="bi bi-card-list"></i> {{ $service->titleList?->name ?? '—' }}
                                    · <i class="bi bi-robot"></i> {{ $service->textAgent?->name ?? '—' }}
                                </div>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-1 mt-3 small">
                            <span class="badge aw-badge tone-{{ $service->is_active ? 'mint' : 'gray' }}">{{ $service->is_active ? __('ui.active') : __('ui.paused') }}</span>
                            <span class="badge aw-badge tone-sky"><i class="bi bi-translate me-1"></i>{{ $service->language }}</span>
                            <span class="badge aw-badge tone-violet"><i class="bi bi-file-earmark-check me-1"></i>{{ $service->ready_count }}</span>
                            <span class="badge aw-badge tone-rose"><i class="bi bi-images me-1"></i>{{ count($service->imageSizeIds()) }}</span>
                            @foreach ($service->destinations as $d)
                                <span class="badge aw-badge tone-peach"><i class="bi {{ $d->publisher()->icon() }} me-1"></i>{{ $d->name }}</span>
                            @endforeach
                            @if ($service->webhook_url)
                                <span class="badge aw-badge tone-peach"><i class="bi bi-broadcast me-1"></i>webhook</span>
                            @endif
                        </div>
                        <div class="d-flex align-items-center mt-auto pt-3 gap-1">
                            <span class="small text-muted me-auto"><i class="bi bi-alarm"></i> {{ local_time($service->next_run_at) }}</span>
                            <form method="post" action="{{ route('services.run', $service) }}" class="m-0 aw-above">
                                @csrf
                                {{ icon_btn('play', __('ui.generate_now'), 'teal', 'type="submit" data-busy') }}
                            </form>
                            {{ icon_btn('pencil', __('ui.edit'), 'amber', 'href="'.route('services.edit', $service).'"', 'a', 'aw-above') }}
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
