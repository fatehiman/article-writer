@extends('layouts.app')
@section('title', $article->title ?: $article->source_title)

@php $images = $article->readyImages(); @endphp

@section('content')
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0 me-auto min-w-0" dir="auto"><i class="bi bi-file-earmark-text text-sky me-2"></i>{{ $article->title ?: $article->source_title }}</h1>
    @if (in_array($article->status, ['pending', 'generating'], true))
        {{ icon_btn('refresh', __('ui.refresh'), 'blue', 'href="'.url()->current().'"', 'a') }}
    @endif
    @if ($article->status === 'failed' && $article->source_title)
        <form method="post" action="{{ route('articles.retry', $article) }}" class="m-0">
            @csrf
            {{ icon_btn('refresh', __('ui.try_again'), 'blue', 'type="submit" data-busy') }}
        </form>
    @endif
    <form method="post" action="{{ route('articles.destroy', $article) }}" class="m-0" data-confirm="{{ __('ui.confirm_delete') }}">
        @csrf @method('DELETE')
        {{ icon_btn('trash', __('ui.delete'), 'red', 'type="submit"') }}
    </form>
</div>

<div class="d-flex flex-wrap gap-1 mb-3 small">
    <span class="badge aw-badge tone-{{ status_tone($article->status) }}">{{ __('ui.status_'.$article->status) }}</span>
    @if ($article->service)
        <a href="{{ route('services.show', $article->service) }}" class="badge aw-badge tone-amber text-decoration-none"><i class="bi bi-lightning-charge me-1"></i>{{ $article->service->name }}</a>
    @endif
    @if ($article->category) <span class="badge aw-badge tone-violet"><i class="bi bi-tag me-1"></i>{{ $article->category }}</span> @endif
    <span class="badge aw-badge tone-sky"><i class="bi bi-translate me-1"></i>{{ article_language($article->language) }}</span>
    @if ($article->tone) <span class="badge aw-badge tone-peach"><i class="bi bi-mic me-1"></i>{{ $article->tone }}</span> @endif
    @if ($article->word_count) <span class="badge aw-badge tone-mint">{{ __('ui.n_words', ['n' => $article->word_count]) }}</span> @endif
    @if ((float) $article->cost > 0) <span class="badge aw-badge tone-rose"><i class="bi bi-wallet2 me-1"></i>{{ money($article->cost) }}</span> @endif
    <span class="badge aw-badge tone-gray"><i class="bi bi-clock me-1"></i>{{ local_time($article->created_at) }}</span>
</div>

@if ($article->error)
    <div class="alert aw-alert-{{ $article->status === 'failed' ? 'err' : 'warn' }}"><i class="bi bi-exclamation-triangle me-1"></i>{{ $article->error }}</div>
@endif
@if (in_array($article->status, ['pending', 'generating'], true))
    <div class="alert aw-alert-info"><span class="spinner-border spinner-border-sm me-2"></span>{{ __('ui.generating_wait') }}</div>
@endif

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card aw-card">
            @if ($images->isNotEmpty())
                <img src="{{ $images->first()->url() }}" alt="{{ $images->first()->alt }}" class="aw-cover">
            @endif
            <div class="card-body aw-article" dir="{{ $article->isRtl() ? 'rtl' : 'ltr' }}" lang="{{ $article->language }}">
                @if ($article->excerpt) <p class="lead">{{ $article->excerpt }}</p> @endif
                {!! $article->status === 'ready' ? $article->bodyWithImages() : $article->body !!}
                @if (! $article->body) <p class="text-muted">{{ __('ui.no_body_yet') }}</p> @endif
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card aw-card mb-3">
            <div class="card-header aw-card-header"><i class="bi bi-send text-rose me-2"></i>{{ __('ui.deliveries') }}</div>
            <div class="list-group list-group-flush">
                @forelse ($article->deliveries as $d)
                    <div class="list-group-item">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge aw-badge tone-{{ status_tone($d->status) }}">{{ __('ui.status_'.$d->status) }}</span>
                            <span class="small text-truncate flex-grow-1" title="{{ $d->target }}">{{ $d->driver }} · {{ $d->target }}</span>
                            @if ($d->status !== 'sending')
                                <form method="post" action="{{ route('deliveries.resend', $d) }}" class="m-0">
                                    @csrf
                                    {{ icon_btn('send', __('ui.send_again'), 'teal', 'type="submit" data-busy', 'button', 'sm') }}
                                </form>
                            @endif
                        </div>
                        <div class="small text-muted mt-1">
                            {{ __('ui.attempts') }}: {{ $d->attempts }}/{{ $d->max_attempts }}
                            @if ($d->response_code) · HTTP {{ $d->response_code }} @endif
                            @if ($d->next_attempt_at) · {{ __('ui.next_try') }} {{ local_time($d->next_attempt_at, 'H:i') }} @endif
                            @if ($d->remote_url) · <a href="{{ $d->remote_url }}" target="_blank" rel="noopener">{{ __('ui.open') }}</a> @endif
                        </div>
                        @if ($d->error) <div class="small text-danger-emphasis mt-1 text-break">{{ \Illuminate\Support\Str::limit($d->error, 300) }}</div> @endif
                    </div>
                @empty
                    <div class="list-group-item small text-muted">{{ __('ui.no_deliveries') }}</div>
                @endforelse
            </div>
        </div>

        @if ($article->images->isNotEmpty())
            <div class="card aw-card mb-3">
                <div class="card-header aw-card-header"><i class="bi bi-images text-rose me-2"></i>{{ __('ui.images') }}</div>
                <div class="card-body">
                    <div class="row g-2">
                        @foreach ($article->images as $img)
                            <div class="col-6">
                                @if ($img->path)
                                    <a href="{{ $img->url() }}" target="_blank"><img src="{{ $img->url() }}" alt="{{ $img->alt }}" class="img-fluid rounded border" loading="lazy"></a>
                                @else
                                    <div class="aw-thumb-empty tone-rose rounded p-3 small text-center" title="{{ $img->error }}"><i class="bi bi-image-alt"></i><br>{{ __('ui.image_failed') }}</div>
                                @endif
                                <div class="small text-muted mt-1">{{ $img->position }}. {{ $img->size_name }} · {{ $img->width }}×{{ $img->height }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <div class="card aw-card">
            <div class="card-header aw-card-header"><i class="bi bi-info-circle text-violet me-2"></i>{{ __('ui.details') }}</div>
            <div class="card-body small">
                <dl class="aw-dl mb-0">
                    <dt>{{ __('ui.source_title') }}</dt><dd dir="auto">{{ $article->source_title ?: '—' }}</dd>
                    <dt>{{ __('ui.meta_title') }}</dt><dd dir="auto">{{ $article->meta_title ?: '—' }}</dd>
                    <dt>{{ __('ui.meta_description') }}</dt><dd dir="auto">{{ $article->meta_description ?: '—' }}</dd>
                    <dt>{{ __('ui.keywords') }}</dt><dd dir="auto">{{ implode(', ', $article->keywords ?? []) ?: '—' }}</dd>
                    <dt>{{ __('ui.slug') }}</dt><dd class="aw-mono" dir="ltr">{{ $article->slug ?: '—' }}</dd>
                    <dt>{{ __('ui.ai') }}</dt><dd>{{ $article->textAgent?->name ?? '—' }}{{ $article->imageAgent ? ' · '.$article->imageAgent->name : '' }}</dd>
                    <dt>ID</dt><dd class="aw-mono" dir="ltr">{{ $article->uuid }}</dd>
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection
