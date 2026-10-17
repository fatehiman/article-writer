@extends('layouts.app')
@section('title', __('ui.title_lists'))

@section('content')
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0 me-auto"><i class="bi bi-card-list text-mint me-2"></i>{{ __('ui.title_lists') }}</h1>
    {{ icon_btn('plus', __('ui.new_title_list'), 'green', 'href="'.route('title-lists.create').'"', 'a') }}
</div>
<p class="text-muted small">{{ __('ui.title_lists_help') }}</p>

@if ($lists->isEmpty())
    <div class="card aw-card">
        <div class="card-body text-center py-5">
            <div class="aw-empty tone-mint mb-3"><i class="bi bi-card-list"></i></div>
            <p class="mb-3">{{ __('ui.no_title_lists') }}</p>
            <a href="{{ route('title-lists.create') }}" class="btn aw-btn-primary"><i class="bi bi-plus-lg me-1"></i>{{ __('ui.new_title_list') }}</a>
        </div>
    </div>
@else
    <div class="card aw-card">
        <div class="table-responsive">
            <table class="table aw-table align-middle mb-0">
                <thead><tr>
                    <th>{{ __('ui.name') }}</th><th class="text-center">{{ __('ui.titles') }}</th>
                    <th class="text-center">{{ __('ui.unused') }}</th><th class="text-center">{{ __('ui.services') }}</th><th></th>
                </tr></thead>
                <tbody>
                @foreach ($lists as $list)
                    <tr>
                        <td><span class="aw-ico-box sm tone-mint me-2"><i class="bi bi-card-list"></i></span><a href="{{ route('title-lists.edit', $list) }}" class="fw-medium text-body">{{ $list->name }}</a></td>
                        <td class="text-center">{{ $list->titles_count }}</td>
                        <td class="text-center"><span class="badge aw-badge tone-{{ $list->unused_count > 0 ? 'mint' : 'rose' }}">{{ $list->unused_count }}</span></td>
                        <td class="text-center">{{ $list->services_count }}</td>
                        <td class="text-end text-nowrap">
                            {{ icon_btn('plus', __('ui.new_service_with_list'), 'green', 'href="'.route('services.create', ['title_list' => $list->id]).'"', 'a', 'sm') }}
                            {{ icon_btn('pencil', __('ui.edit'), 'amber', 'href="'.route('title-lists.edit', $list).'"', 'a', 'sm') }}
                            <form method="post" action="{{ route('title-lists.destroy', $list) }}" class="d-inline" data-confirm="{{ __('ui.confirm_delete') }}">
                                @csrf @method('DELETE')
                                {{ icon_btn('trash', __('ui.delete'), 'red', 'type="submit"', 'button', 'sm') }}
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
