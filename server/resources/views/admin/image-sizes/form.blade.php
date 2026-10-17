@extends('layouts.app')
@section('title', $size->exists ? $size->name : __('ui.new_image_size'))

@section('content')
<h1 class="h4 mb-3"><i class="bi bi-aspect-ratio text-rose me-2"></i>{{ $size->exists ? $size->name : __('ui.new_image_size') }}</h1>

<div class="row"><div class="col-lg-6">
<form method="post" action="{{ $size->exists ? route('admin.image-sizes.update', $size) : route('admin.image-sizes.store') }}" class="card aw-card">
    @csrf
    @if ($size->exists) @method('PUT') @endif
    <div class="card-body">
        <div class="row g-3">
            <div class="col-sm-7"><label class="form-label" for="name">{{ __('ui.name') }}</label>
                <input class="form-control" id="name" name="name" value="{{ old('name', $size->name) }}" required placeholder="Instagram square"></div>
            <div class="col-sm-5"><label class="form-label" for="group">{{ __('ui.group') }}</label>
                <input class="form-control" id="group" name="group" value="{{ old('group', $size->group) }}" required list="groups" dir="ltr">
                <datalist id="groups"><option value="general"><option value="instagram"><option value="facebook"><option value="linkedin"><option value="telegram"></datalist></div>
            <div class="col-4"><label class="form-label" for="width">{{ __('ui.width') }}</label>
                <input class="form-control" type="number" id="width" name="width" min="64" max="4096" value="{{ old('width', $size->width) }}" required dir="ltr"></div>
            <div class="col-4"><label class="form-label" for="height">{{ __('ui.height') }}</label>
                <input class="form-control" type="number" id="height" name="height" min="64" max="4096" value="{{ old('height', $size->height) }}" required dir="ltr"></div>
            <div class="col-4"><label class="form-label" for="sort">{{ __('ui.sort') }}</label>
                <input class="form-control" type="number" id="sort" name="sort" value="{{ old('sort', $size->sort) }}" dir="ltr"></div>
            <div class="col-12"><div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked(old('is_active', $size->is_active))>
                <label class="form-check-label" for="is_active">{{ __('ui.active') }}</label></div></div>
        </div>
    </div>
    <div class="card-footer bg-transparent d-flex gap-2">
        <button class="btn aw-btn-primary px-4" type="submit"><i class="bi bi-check2 me-1"></i>{{ __('ui.save') }}</button>
        <a class="btn btn-light border" href="{{ route('admin.image-sizes.index') }}">{{ __('ui.cancel') }}</a>
    </div>
</form>
</div></div>
@endsection
