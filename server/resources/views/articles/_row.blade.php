@php $cover = $article->images->firstWhere('path', '!=', null); @endphp
<a href="{{ route('articles.show', $article) }}" class="list-group-item list-group-item-action d-flex align-items-center gap-3">
    @if ($cover)
        <img src="{{ $cover->url() }}" alt="" class="aw-thumb" loading="lazy">
    @else
        <span class="aw-thumb aw-thumb-empty tone-{{ status_tone($article->status) }}"><i class="bi bi-file-earmark-text"></i></span>
    @endif
    <span class="flex-grow-1 min-w-0">
        <span class="d-block text-truncate fw-medium" dir="auto">{{ $article->title ?: $article->source_title ?: '—' }}</span>
        <span class="d-block small text-muted text-truncate">
            {{ $article->service?->name ?? '—' }}
            @if ($article->category) · {{ $article->category }} @endif
            · {{ local_time($article->created_at) }}
        </span>
    </span>
    <span class="badge aw-badge tone-{{ status_tone($article->status) }}">{{ __('ui.status_'.$article->status) }}</span>
</a>
