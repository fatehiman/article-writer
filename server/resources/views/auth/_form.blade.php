@if ($errors->any())
    <div class="alert aw-alert-err py-2 small"><i class="bi bi-exclamation-triangle me-1"></i>{{ $errors->first() }}</div>
@endif

<form method="post" action="{{ url('/mainadmin') }}" autocomplete="on">
    @csrf
    {{-- Honeypot field for robots. Hidden from people. --}}
    <div class="aw-hp" aria-hidden="true">
        <label for="website">Website</label>
        <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
    </div>

    <div class="mb-3">
        <label class="form-label small text-muted" for="username">{{ __('ui.username') }}</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-person"></i></span>
            <input class="form-control" id="username" name="username" value="{{ old('username') }}" required autofocus
                   autocomplete="username" autocapitalize="none" dir="ltr">
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label small text-muted" for="password">{{ __('ui.password') }}</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input class="form-control" id="password" name="password" type="password" required autocomplete="current-password" dir="ltr">
        </div>
    </div>
    <div class="form-check mb-4">
        <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1">
        <label class="form-check-label small" for="remember">{{ __('ui.remember_me') }}</label>
    </div>
    <button class="btn aw-btn-primary w-100" type="submit"><i class="bi bi-box-arrow-in-right me-1"></i>{{ __('ui.sign_in') }}</button>
</form>
