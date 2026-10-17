@extends('layouts.app')
@section('title', $list->exists ? $list->name : __('ui.new_title_list'))

@php
    // After a validation error, show the table the user sent, not the saved one.
    $tableRows = old('rows') ? (json_decode(old('rows'), true) ?: []) : $rows;
@endphp

@section('content')
<h1 class="h4 mb-3"><i class="bi bi-card-list text-mint me-2"></i>{{ $list->exists ? __('ui.edit_title_list') : __('ui.new_title_list') }}</h1>

<form method="post" action="{{ $list->exists ? route('title-lists.update', $list) : route('title-lists.store') }}" id="titleTableForm">
    @csrf
    @if ($list->exists) @method('PUT') @endif
    <input type="hidden" name="rows" id="rowsJson">

    <div class="card aw-card mb-3">
        <div class="card-body">
            <label class="form-label" for="name">{{ __('ui.name') }}</label>
            <input class="form-control aw-name" id="name" name="name" value="{{ old('name', $list->name) }}" required maxlength="120" placeholder="{{ __('ui.title_list_name_ph') }}">
        </div>
    </div>

    <div class="card aw-card mb-3">
        <div class="card-header aw-card-header flex-wrap gap-2">
            <span class="me-auto"><i class="bi bi-list-ol text-sky me-2"></i>{{ __('ui.titles') }}
                <span class="badge aw-badge tone-sky ms-1" id="rowCount">0</span></span>
            <div class="input-group input-group-sm aw-table-search">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input class="form-control" type="search" id="rowFilter" placeholder="{{ __('ui.search_title') }}">
            </div>
            {{ icon_btn('plus', __('ui.add_row'), 'green', 'id="addRow"') }}
            <button type="button" class="btn icon tone-blue" data-bs-toggle="modal" data-bs-target="#pasteModal"
                    title="{{ __('ui.paste_many') }}" aria-label="{{ __('ui.paste_many') }}"><i class="bi bi-clipboard-plus"></i></button>
            <button type="button" class="btn icon tone-amber" data-bs-toggle="modal" data-bs-target="#weightAllModal"
                    title="{{ __('ui.set_all_weights') }}" aria-label="{{ __('ui.set_all_weights') }}"><i class="bi bi-sliders"></i></button>
        </div>
        <div class="card-body pb-0">
            <p class="small text-muted mb-2">{{ __('ui.weight_help') }}</p>
        </div>
        <div class="table-responsive aw-scroll-lg">
            <table class="table aw-table aw-title-table align-middle mb-0">
                <thead><tr>
                    <th class="text-center">#</th>
                    <th>{{ __('ui.title') }}</th>
                    <th class="text-center">{{ __('ui.weight') }}</th>
                    <th class="text-center">{{ __('ui.share') }}</th>
                    <th class="text-center">{{ __('ui.used') }}</th>
                    <th></th>
                </tr></thead>
                <tbody id="titleRows"
                        data-rows='@json($tableRows)'
                        data-delete="{{ __('ui.delete') }}"
                        data-paused="{{ __('ui.paused') }}"
                        data-title-ph="{{ __('ui.title_row_ph') }}"></tbody>
            </table>
        </div>
        <div class="card-footer bg-transparent small text-muted d-flex flex-wrap gap-3" id="rowTotals"
             data-template="{{ __('ui.weight_totals') }}"></div>
    </div>

    @if ($list->exists)
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="reset_usage" name="reset_usage" value="1">
            <label class="form-check-label" for="reset_usage">{{ __('ui.reset_usage') }}</label>
        </div>
    @endif

    <div class="aw-savebar">
        <button class="btn aw-btn-primary px-4" type="submit"><i class="bi bi-check2 me-1"></i>{{ __('ui.save') }}</button>
        <a class="btn btn-light border" href="{{ route('title-lists.index') }}">{{ __('ui.cancel') }}</a>
    </div>
</form>
@endsection

@push('modals')
<div class="modal fade" id="pasteModal" tabindex="-1" aria-labelledby="pasteModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form class="modal-content aw-modal" id="pasteForm">
            <div class="modal-header">
                <h5 class="modal-title" id="pasteModalTitle"><i class="bi bi-clipboard-plus text-sky me-2"></i>{{ __('ui.paste_many') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.close') }}"></button>
            </div>
            <div class="modal-body">
                <label class="form-label" for="pasteText">{{ __('ui.titles') }}</label>
                <textarea class="form-control" id="pasteText" rows="10" dir="auto" placeholder="{{ __('ui.titles_ph') }}" data-count-lines="#pasteCount"></textarea>
                <div class="form-text mb-3">{{ __('ui.titles_help') }} <span id="pasteCount">0</span>. {{ __('ui.paste_dupes') }}</div>
                <label class="form-label" for="pasteWeight">{{ __('ui.weight_for_all') }}</label>
                <input class="form-control aw-num" type="number" id="pasteWeight" value="1" min="0" max="{{ \App\Models\Title::MAX_WEIGHT }}" step="any">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">{{ __('ui.cancel') }}</button>
                <button type="submit" class="btn aw-btn-primary">{{ __('ui.add_rows') }}</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="weightAllModal" tabindex="-1" aria-labelledby="weightAllTitle" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <form class="modal-content aw-modal" id="weightAllForm">
            <div class="modal-header">
                <h5 class="modal-title" id="weightAllTitle">{{ __('ui.set_all_weights') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.close') }}"></button>
            </div>
            <div class="modal-body">
                <label class="form-label" for="allWeight">{{ __('ui.weight') }}</label>
                <input class="form-control aw-num" type="number" id="allWeight" value="1" min="0" max="{{ \App\Models\Title::MAX_WEIGHT }}" step="any">
                <div class="form-text">{{ __('ui.set_all_weights_help') }}</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">{{ __('ui.cancel') }}</button>
                <button type="submit" class="btn aw-btn-primary">{{ __('ui.ok') }}</button>
            </div>
        </form>
    </div>
</div>
@endpush
