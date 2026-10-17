@extends('layouts.app')
@section('title', __('ui.articles'))

@section('content')
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0 me-auto"><i class="bi bi-file-earmark-text text-sky me-2"></i>{{ __('ui.articles') }}</h1>
</div>

<form method="get" class="row g-2 mb-3">
    <div class="col-md-5">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input class="form-control" name="q" value="{{ request('q') }}" placeholder="{{ __('ui.search_title') }}">
        </div>
    </div>
    <div class="col-6 col-md-3">
        <select class="form-select" name="service" data-autosubmit>
            <option value="">{{ __('ui.all_services') }}</option>
            @foreach ($services as $id => $name)
                <option value="{{ $id }}" @selected((int) request('service') === $id)>{{ $name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-6 col-md-3">
        <select class="form-select" name="status" data-autosubmit>
            <option value="">{{ __('ui.all_statuses') }}</option>
            @foreach (['ready', 'failed', 'pending', 'generating'] as $s)
                <option value="{{ $s }}" @selected(request('status') === $s)>{{ __('ui.status_'.$s) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-1 d-grid">
        <button class="btn icon tone-blue" type="submit" title="{{ __('ui.search') }}" aria-label="{{ __('ui.search') }}"><i class="bi bi-funnel"></i></button>
    </div>
</form>

<div class="card aw-card">
    <div class="list-group list-group-flush">
        @forelse ($articles as $a)
            @include('articles._row', ['article' => $a])
        @empty
            <div class="list-group-item text-center py-5">
                <div class="aw-empty tone-sky mb-3"><i class="bi bi-file-earmark-text"></i></div>
                <div class="text-muted">{{ __('ui.no_articles') }}</div>
            </div>
        @endforelse
    </div>
</div>
<div class="mt-3">{{ $articles->links() }}</div>
@endsection
