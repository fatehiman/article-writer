<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="rtl">
<head>
    @include('partials.head')
    <title>{{ __('ui.sign_in') }} · {{ config('app.name') }}</title>
</head>
<body class="theme-rtl rt-login-page">
<div class="rt-login">
    <div class="rt-login-art d-none d-md-flex">
        <span class="rt-logo rt-logo-xl"><i class="bi bi-feather"></i></span>
        <h2 class="mt-4 mb-2">{{ config('app.name') }}</h2>
        <p class="mb-0">{{ __('ui.login_tagline') }}</p>
    </div>
    <div class="rt-login-form">
        <div class="d-md-none text-center mb-3"><span class="rt-logo rt-logo-lg"><i class="bi bi-feather"></i></span></div>
        <h1 class="h4 fw-bold mb-1">{{ __('ui.sign_in') }}</h1>
        <p class="text-muted small mb-4">{{ __('ui.login_hint') }}</p>
        @include('auth._form')
        <div class="mt-4 small">@include('partials.locale-switch', ['class' => 'text-muted text-decoration-none'])</div>
    </div>
</div>
</body>
</html>
