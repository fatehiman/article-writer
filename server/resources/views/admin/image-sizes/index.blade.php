@extends('layouts.app')
@section('title', __('ui.image_sizes'))

@section('content')
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0 me-auto"><i class="bi bi-aspect-ratio text-rose me-2"></i>{{ __('ui.image_sizes') }}</h1>
    {{ icon_btn('plus', __('ui.new_image_size'), 'green', 'href="'.route('admin.image-sizes.create').'"', 'a') }}
</div>

<div class="row g-3">
    @foreach ($sizes as $s)
        <div class="col-6 col-md-4 col-xl-3">
            <div class="card aw-card h-100 {{ $s->is_active ? '' : 'opacity-50' }}">
                <div class="card-body d-flex flex-column">
                    <div class="aw-ratio mb-2"><span style="aspect-ratio: {{ $s->width }} / {{ $s->height }}"></span></div>
                    <div class="fw-medium">{{ $s->name }}</div>
                    <div class="small text-muted aw-mono" dir="ltr">{{ $s->width }}×{{ $s->height }}</div>
                    <div class="d-flex align-items-center gap-1 mt-auto pt-2">
                        <span class="badge aw-badge tone-{{ $s->group === 'instagram' ? 'rose' : 'sky' }} me-auto">{{ $s->group }}</span>
                        {{ icon_btn('pencil', __('ui.edit'), 'amber', 'href="'.route('admin.image-sizes.edit', $s).'"', 'a', 'sm') }}
                        <form method="post" action="{{ route('admin.image-sizes.destroy', $s) }}" class="d-inline" data-confirm="{{ __('ui.confirm_delete') }}">
                            @csrf @method('DELETE')
                            {{ icon_btn('trash', __('ui.delete'), 'red', 'type="submit"', 'button', 'sm') }}
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
