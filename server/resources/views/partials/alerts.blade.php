@if (session('ok'))
    <div class="alert aw-alert-ok alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-1"></i>{{ session('ok') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('ui.close') }}"></button>
    </div>
@endif
@if ($errors->any())
    <div class="alert aw-alert-err" role="alert">
        <i class="bi bi-exclamation-triangle me-1"></i>
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif
