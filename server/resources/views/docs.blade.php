@extends('layouts.app')
@section('title', __('ui.docs'))

@php $apiBase = rtrim(config('app.url'), '/').'/api/v1'; @endphp

@section('content')
<h1 class="h4 mb-3"><i class="bi bi-book text-violet me-2"></i>{{ __('ui.docs') }}</h1>

<ul class="nav nav-pills aw-pills mb-3" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tabFlow" type="button">{{ __('ui.docs_flow') }}</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tabWebhook" type="button">{{ __('ui.webhook') }}</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tabApi" type="button">API</button></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="tabFlow">
        <div class="card aw-card"><div class="card-body">
            <ol class="aw-doc-steps mb-0">
                <li>{!! __('ui.flow1', ['url' => route('title-lists.index')]) !!}</li>
                <li>{!! __('ui.flow2', ['url' => route('destinations.index')]) !!}</li>
                <li>{!! __('ui.flow3', ['url' => route('services.create')]) !!}</li>
                <li>{!! __('ui.flow4') !!}</li>
                <li>{!! __('ui.flow5') !!}</li>
            </ol>
        </div></div>
    </div>

    <div class="tab-pane fade" id="tabWebhook">
        <div class="card aw-card"><div class="card-body">
            @include('docs._webhook')
        </div></div>
    </div>

    <div class="tab-pane fade" id="tabApi">
        <div class="card aw-card"><div class="card-body">
            <p>{!! __('ui.api_intro') !!}</p>
            @if ($service)
                <div class="alert aw-alert-info small">{{ __('ui.api_for_service', ['name' => $service->name]) }}
                    <div class="input-group mt-2" dir="ltr">
                        <input class="form-control aw-mono" type="password" value="{{ $service->api_token }}" readonly id="docToken">
                        <button class="btn icon tone-purple" type="button" data-toggle-secret="#docToken" title="{{ __('ui.show') }}" aria-label="{{ __('ui.show') }}">{{ icon('eye') }}</button>
                        <button class="btn icon tone-blue" type="button" data-copy="#docToken" title="{{ __('ui.copy') }}" aria-label="{{ __('ui.copy') }}">{{ icon('copy') }}</button>
                    </div>
                </div>
            @endif
            <table class="table table-sm aw-table small" dir="ltr">
                <thead><tr><th>Method</th><th>URL</th><th>{{ __('ui.description') }}</th></tr></thead>
                <tbody>
                <tr><td><code>GET</code></td><td class="text-break"><code>{{ $apiBase }}/articles</code></td><td>{{ __('ui.api_list') }}</td></tr>
                <tr><td><code>GET</code></td><td class="text-break"><code>{{ $apiBase }}/articles/{id}</code></td><td>{{ __('ui.api_one') }}</td></tr>
                <tr><td><code>GET</code></td><td class="text-break"><code>{{ $apiBase }}/service</code></td><td>{{ __('ui.api_service') }}</td></tr>
                </tbody>
            </table>
            <h6>{{ __('ui.api_params') }}</h6>
            <table class="table table-sm aw-table small" dir="ltr">
                <tbody>
                <tr><td><code>since</code></td><td>ISO 8601 date/time, e.g. <code>2026-10-01T00:00:00Z</code></td></tr>
                <tr><td><code>status</code></td><td><code>ready</code> (default), <code>failed</code>, <code>pending</code>, <code>generating</code>, <code>all</code></td></tr>
                <tr><td><code>category</code></td><td>exact category name</td></tr>
                <tr><td><code>order</code></td><td><code>desc</code> (default) / <code>asc</code></td></tr>
                <tr><td><code>fields</code></td><td><code>full</code> (default) / <code>summary</code> (no body)</td></tr>
                <tr><td><code>page</code>, <code>per_page</code></td><td>per_page max 100 (default 20)</td></tr>
                <tr><td><code>embed_images</code></td><td>only for <code>/articles/{id}</code>: add base64 data</td></tr>
                </tbody>
            </table>
            <h6>{{ __('ui.example') }}</h6>
            <pre class="aw-pre" dir="ltr">curl -H "Authorization: Bearer aw_xxxxxxxx" \
  "{{ $apiBase }}/articles?since=2026-10-01T00:00:00Z&amp;per_page=50"

# {{ __('ui.api_answer') }}
{ "data": [ { "id": "…", "title": "…", "body_html": "…", "images": [ … ] } ],
  "meta": { "page": 1, "per_page": 50, "total": 120, "last_page": 3 } }</pre>
            <p class="mb-0"><a href="{{ route('api.openapi') }}" target="_blank"><i class="bi bi-filetype-json"></i> {{ __('ui.openapi_file') }}</a> <span class="text-muted small" dir="ltr">({{ route('api.openapi') }})</span></p>
        </div></div>
    </div>
</div>
@endsection
