@extends('layouts.app')
@section('title', __('ui.dashboard'))

@section('content')
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0 me-auto"><i class="bi bi-grid-1x2 text-violet me-2"></i>{{ __('ui.hello', ['name' => auth()->user()->name]) }}</h1>
    <a href="{{ route('services.create') }}" class="btn aw-btn-primary"><i class="bi bi-plus-lg me-1"></i>{{ __('ui.new_service') }}</a>
</div>

<div class="row g-3 mb-4">
    @foreach ([
        ['bi-lightning-charge', 'amber', $stats['active_services'].' / '.$stats['services'], __('ui.active_services'), route('services.index')],
        ['bi-file-earmark-check', 'mint', $stats['articles'], __('ui.ready_articles'), route('articles.index', ['status' => 'ready'])],
        ['bi-card-list', 'sky', $stats['titles_left'], __('ui.unused_titles'), route('title-lists.index')],
        ['bi-wallet2', 'peach', money(auth()->user()->wallet_balance), __('ui.balance'), route('wallet')],
        ['bi-exclamation-octagon', 'rose', $stats['failed'], __('ui.failed_7d'), route('articles.index', ['status' => 'failed'])],
        ['bi-send-x', 'violet', $stats['deliveries_failed'], __('ui.failed_deliveries_7d'), route('articles.index')],
    ] as [$ico, $tone, $value, $label, $link])
        <div class="col-6 col-md-4 col-xl-2">
            <a href="{{ $link }}" class="card aw-card aw-card-link aw-stat h-100 text-decoration-none">
                <div class="card-body">
                    <span class="aw-ico-box tone-{{ $tone }} mb-2"><i class="bi {{ $ico }}"></i></span>
                    <div class="aw-stat-value">{{ $value }}</div>
                    <div class="small text-muted">{{ $label }}</div>
                </div>
            </a>
        </div>
    @endforeach
</div>

@if ($admin)
    <div class="card aw-card mb-4">
        <div class="card-body d-flex flex-wrap gap-4 align-items-center">
            <span class="aw-ico-box tone-rose"><i class="bi bi-shield-check"></i></span>
            <div><div class="small text-muted">{{ __('ui.customers') }}</div><div class="fw-semibold fs-5">{{ $admin['customers'] }}</div></div>
            <div><div class="small text-muted">{{ __('ui.articles_today') }}</div><div class="fw-semibold fs-5">{{ $admin['articles_today'] }}</div></div>
            <div><div class="small text-muted">{{ __('ui.income_30d') }}</div><div class="fw-semibold fs-5">{{ money($admin['income_30d']) }}</div></div>
        </div>
    </div>
@endif

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card aw-card h-100">
            <div class="card-header aw-card-header"><i class="bi bi-alarm text-amber me-2"></i>{{ __('ui.next_runs') }}</div>
            <div class="list-group list-group-flush">
                @forelse ($upcoming as $s)
                    <a href="{{ route('services.show', $s) }}" class="list-group-item list-group-item-action d-flex align-items-center gap-2">
                        <span class="aw-ico-box sm tone-amber"><i class="bi bi-lightning-charge"></i></span>
                        <span class="flex-grow-1 min-w-0 text-truncate">{{ $s->name }}</span>
                        <span class="small text-muted aw-mono">{{ local_time($s->next_run_at) }}</span>
                    </a>
                @empty
                    <div class="list-group-item text-muted small py-4 text-center">{{ __('ui.no_upcoming') }}</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card aw-card h-100">
            <div class="card-header aw-card-header"><i class="bi bi-clock-history text-sky me-2"></i>{{ __('ui.recent_articles') }}</div>
            <div class="list-group list-group-flush">
                @forelse ($recent as $a)
                    @include('articles._row', ['article' => $a])
                @empty
                    <div class="list-group-item text-muted small py-4 text-center">{{ __('ui.no_articles') }}</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
