@extends('layouts.app')
@section('title', $service->name)

@php
    $weekdays = [__('ui.sun'), __('ui.mon'), __('ui.tue'), __('ui.wed'), __('ui.thu'), __('ui.fri'), __('ui.sat')];
    $days = $service->schedule_days ?? [];
    $apiBase = rtrim(config('app.url'), '/').'/api/v1';
@endphp

@section('content')
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0 me-auto text-truncate"><i class="bi bi-lightning-charge text-amber me-2"></i>{{ $service->name }}
        <span class="badge aw-badge tone-{{ $service->is_active ? 'mint' : 'gray' }} align-middle fs-6">{{ $service->is_active ? __('ui.active') : __('ui.paused') }}</span>
    </h1>
    <form method="post" action="{{ route('services.run', $service) }}" class="m-0">
        @csrf
        {{ icon_btn('play', __('ui.generate_now'), 'teal', 'type="submit" data-busy') }}
    </form>
    <form method="post" action="{{ route('services.toggle', $service) }}" class="m-0">
        @csrf
        <button class="btn icon tone-{{ $service->is_active ? 'gray' : 'green' }}" type="submit"
                title="{{ $service->is_active ? __('ui.pause') : __('ui.activate') }}" aria-label="{{ $service->is_active ? __('ui.pause') : __('ui.activate') }}">
            <i class="bi {{ $service->is_active ? 'bi-pause-fill' : 'bi-play-circle' }}"></i>
        </button>
    </form>
    {{ icon_btn('pencil', __('ui.edit'), 'amber', 'href="'.route('services.edit', $service).'"', 'a') }}
    <form method="post" action="{{ route('services.duplicate', $service) }}" class="m-0">
        @csrf
        {{ icon_btn('copy', __('ui.duplicate'), 'green', 'type="submit"') }}
    </form>
    <form method="post" action="{{ route('services.destroy', $service) }}" class="m-0" data-confirm="{{ __('ui.confirm_delete_service') }}">
        @csrf @method('DELETE')
        {{ icon_btn('trash', __('ui.delete'), 'red', 'type="submit"') }}
    </form>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card aw-card mb-3">
            <div class="card-body">
                <dl class="row aw-dl mb-0">
                    <dt class="col-sm-4">{{ __('ui.title_list') }}</dt>
                    <dd class="col-sm-8">
                        @if ($service->titleList)
                            <a href="{{ route('title-lists.edit', $service->titleList) }}">{{ $service->titleList->name }}</a>
                            <span class="badge aw-badge tone-{{ $titlesLeft > 0 ? 'mint' : 'rose' }}">{{ __('ui.n_active_titles', ['n' => $titlesLeft]) }}</span>
                        @else — @endif
                    </dd>
                    <dt class="col-sm-4">{{ __('ui.categories') }}</dt>
                    <dd class="col-sm-8">
                        @forelse ($service->categories ?? [] as $c)
                            <span class="badge aw-badge tone-violet">{{ $c }}</span>
                        @empty — @endforelse
                    </dd>
                    <dt class="col-sm-4">{{ __('ui.article') }}</dt>
                    <dd class="col-sm-8">{{ article_language($service->language) }} · {{ __('ui.n_words', ['n' => $service->word_count]) }} · {{ $service->tone?->label() ?? __('ui.tone_default') }}</dd>
                    <dt class="col-sm-4">{{ __('ui.text_agent') }}</dt>
                    <dd class="col-sm-8">{{ $service->textAgent?->name ?? '—' }} <span class="small text-muted">{{ $service->textAgent?->priceLabel() }}</span></dd>
                    <dt class="col-sm-4">{{ __('ui.images') }}</dt>
                    <dd class="col-sm-8">
                        @forelse ($service->imageSizeIds() as $i => $id)
                            <span class="badge aw-badge tone-rose">{{ $i + 1 }}. {{ $sizes->get($id)?->label() ?? '?' }}</span>
                        @empty {{ __('ui.no_images') }} @endforelse
                        @if ($service->imageSizeIds())
                            <div class="small text-muted mt-1">{{ $service->imageAgent?->name }} · {{ $service->imageAgent?->priceLabel() }}</div>
                        @endif
                    </dd>
                    <dt class="col-sm-4">{{ __('ui.schedule') }}</dt>
                    <dd class="col-sm-8">
                        <span class="aw-mono">{{ implode(', ', $service->schedule_times ?? []) }}</span> ·
                        {{ __('ui.repeat_'.$service->schedule_type) }}
                        @if ($service->schedule_type === 'weekly')
                            ({{ collect($days)->sort()->map(fn ($d) => $weekdays[$d] ?? $d)->implode(', ') }})
                        @elseif ($service->schedule_type === 'monthly')
                            ({{ collect($days)->sort()->implode(', ') }})
                        @endif
                        · {{ $service->timezone }}
                        <div class="small mt-1"><i class="bi bi-alarm text-amber"></i> {{ __('ui.next_run') }}: <strong>{{ local_time($service->next_run_at) }}</strong>
                            @if ($service->last_run_at) · {{ __('ui.last_run') }}: {{ local_time($service->last_run_at) }} @endif
                        </div>
                    </dd>
                    <dt class="col-sm-4">{{ __('ui.destinations') }}</dt>
                    <dd class="col-sm-8">
                        @forelse ($service->destinations as $d)
                            <span class="badge aw-badge tone-peach"><i class="bi {{ $d->publisher()->icon() }} me-1"></i>{{ $d->name }}</span>
                        @empty — @endforelse
                    </dd>
                    <dt class="col-sm-4">{{ __('ui.webhook_url') }}</dt>
                    <dd class="col-sm-8 text-break" dir="ltr">{{ $service->webhook_url ?: '—' }}</dd>
                    <dt class="col-sm-4">{{ __('ui.retries') }}</dt>
                    <dd class="col-sm-8">{{ __('ui.retry_summary', ['n' => $service->retry_limit, 'm' => $service->retry_delay]) }}</dd>
                </dl>
            </div>
        </div>

        <div class="card aw-card">
            <div class="card-header aw-card-header d-flex align-items-center">
                <span class="me-auto"><i class="bi bi-clock-history text-sky me-2"></i>{{ __('ui.recent_articles') }}</span>
                <a class="small" href="{{ route('articles.index', ['service' => $service->id]) }}">{{ __('ui.see_all') }}</a>
            </div>
            <div class="list-group list-group-flush">
                @forelse ($articles as $a)
                    @include('articles._row', ['article' => $a])
                @empty
                    <div class="list-group-item text-muted small py-4 text-center">{{ __('ui.no_articles') }}</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card aw-card mb-3">
            <div class="card-header aw-card-header"><i class="bi bi-key text-amber me-2"></i>{{ __('ui.api_token') }}</div>
            <div class="card-body">
                <p class="small text-muted">{{ __('ui.api_token_help') }}</p>
                <div class="input-group mb-2" dir="ltr">
                    <input class="form-control aw-mono" type="password" value="{{ $service->api_token }}" readonly id="apiToken">
                    <button class="btn icon tone-purple" type="button" data-toggle-secret="#apiToken" title="{{ __('ui.show') }}" aria-label="{{ __('ui.show') }}">{{ icon('eye') }}</button>
                    <button class="btn icon tone-blue" type="button" data-copy="#apiToken" title="{{ __('ui.copy') }}" aria-label="{{ __('ui.copy') }}">{{ icon('copy') }}</button>
                </div>
                <form method="post" action="{{ route('services.token', $service) }}" class="m-0 mb-3" data-confirm="{{ __('ui.confirm_rotate') }}">
                    @csrf
                    <button class="btn btn-sm aw-btn-soft tone-amber" type="submit"><i class="bi bi-arrow-repeat me-1"></i>{{ __('ui.rotate_token') }}</button>
                </form>
                <label class="form-label small">{{ __('ui.endpoints') }}</label>
                <div class="aw-pre small mb-2" dir="ltr">GET {{ $apiBase }}/articles
GET {{ $apiBase }}/articles/{id}
GET {{ $apiBase }}/service</div>
                <div class="aw-pre small mb-2" dir="ltr">curl -H "Authorization: Bearer $TOKEN" \
  "{{ $apiBase }}/articles?since=2026-01-01&amp;per_page=20"</div>
                <div class="d-flex gap-2 small">
                    <a href="{{ route('docs', ['service' => $service->id]) }}"><i class="bi bi-book"></i> {{ __('ui.docs') }}</a>
                    <a href="{{ route('api.openapi') }}" target="_blank"><i class="bi bi-filetype-json"></i> openapi.json</a>
                </div>
            </div>
        </div>

        <div class="card aw-card">
            <div class="card-header aw-card-header d-flex align-items-center gap-2">
                <i class="bi bi-shield-lock text-violet"></i><span class="me-auto">{{ __('ui.webhook_secret') }}</span>
                <button type="button" class="btn btn-link p-0 aw-help" data-bs-toggle="modal" data-bs-target="#webhookHelp" title="{{ __('ui.webhook_help_title') }}" aria-label="{{ __('ui.webhook_help_title') }}">{{ icon('help', 18) }}</button>
            </div>
            <div class="card-body">
                <p class="small text-muted">{{ __('ui.webhook_secret_help') }}</p>
                <div class="input-group mb-2" dir="ltr">
                    <input class="form-control aw-mono" type="password" value="{{ $service->webhook_secret }}" readonly id="whSecret">
                    <button class="btn icon tone-purple" type="button" data-toggle-secret="#whSecret" title="{{ __('ui.show') }}" aria-label="{{ __('ui.show') }}">{{ icon('eye') }}</button>
                    <button class="btn icon tone-blue" type="button" data-copy="#whSecret" title="{{ __('ui.copy') }}" aria-label="{{ __('ui.copy') }}">{{ icon('copy') }}</button>
                </div>
                <form method="post" action="{{ route('services.secret', $service) }}" class="m-0" data-confirm="{{ __('ui.confirm_rotate') }}">
                    @csrf
                    <button class="btn btn-sm aw-btn-soft tone-amber" type="submit"><i class="bi bi-arrow-repeat me-1"></i>{{ __('ui.rotate_secret') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('modals')
    @include('services._webhook-help')
@endpush
