@extends('layouts.app')
@section('title', $list->exists ? $list->name : __('ui.new_title_list'))

@section('content')
<h1 class="h4 mb-3"><i class="bi bi-card-list text-mint me-2"></i>{{ $list->exists ? __('ui.edit_title_list') : __('ui.new_title_list') }}</h1>

<div class="row g-3">
    <div class="col-lg-7">
        <form method="post" action="{{ $list->exists ? route('title-lists.update', $list) : route('title-lists.store') }}" class="card aw-card">
            @csrf
            @if ($list->exists) @method('PUT') @endif
            <div class="card-body">
                <label class="form-label" for="name">{{ __('ui.name') }}</label>
                <input class="form-control mb-3" id="name" name="name" value="{{ old('name', $list->name) }}" required maxlength="120" placeholder="{{ __('ui.title_list_name_ph') }}">
                <label class="form-label" for="titles">{{ __('ui.titles') }}</label>
                <textarea class="form-control" id="titles" name="titles" rows="18" required dir="auto" placeholder="{{ __('ui.titles_ph') }}" data-count-lines="#lineCount">{{ old('titles', $text) }}</textarea>
                <div class="form-text">{{ __('ui.titles_help') }} <span id="lineCount">0</span></div>
                @if ($list->exists)
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" id="reset_usage" name="reset_usage" value="1">
                        <label class="form-check-label" for="reset_usage">{{ __('ui.reset_usage') }}</label>
                    </div>
                @endif
            </div>
            <div class="card-footer bg-transparent d-flex gap-2">
                <button class="btn aw-btn-primary px-4" type="submit"><i class="bi bi-check2 me-1"></i>{{ __('ui.save') }}</button>
                <a class="btn btn-light border" href="{{ route('title-lists.index') }}">{{ __('ui.cancel') }}</a>
            </div>
        </form>
    </div>
    @if ($list->exists)
        <div class="col-lg-5">
            <div class="card aw-card">
                <div class="card-header aw-card-header"><i class="bi bi-bar-chart text-sky me-2"></i>{{ __('ui.usage') }}</div>
                <div class="aw-scroll">
                    <table class="table table-sm aw-table mb-0 small">
                        <thead><tr><th>{{ __('ui.title') }}</th><th class="text-center">{{ __('ui.used') }}</th></tr></thead>
                        <tbody>
                        @foreach ($titles as $t)
                            <tr><td dir="auto">{{ $t->title }}</td>
                                <td class="text-center"><span class="badge aw-badge tone-{{ $t->used_count ? 'sky' : 'mint' }}">{{ $t->used_count }}</span></td></tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
