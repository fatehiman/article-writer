<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="ltr">
<head>
    @include('partials.head')
    <title>{{ __('ui.sign_in') }} · {{ config('app.name') }}</title>
</head>
<body class="theme-ltr aw-center aw-login-bg">
<div class="card aw-card aw-login shadow-sm">
    <div class="card-body p-4 p-sm-5">
        <div class="text-center mb-4">
            <span class="aw-logo aw-logo-lg"><i class="bi bi-feather"></i></span>
            <h1 class="h4 fw-semibold mt-3 mb-1">{{ config('app.name') }}</h1>
            <p class="text-muted small mb-0">{{ __('ui.login_hint') }}</p>
        </div>
        @include('auth._form')
        <div class="text-center mt-4 small">@include('partials.locale-switch', ['class' => 'text-muted text-decoration-none'])</div>
    </div>
</div>
</body>
</html>
