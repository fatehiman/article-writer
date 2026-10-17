<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ ui_dir() }}">
<head>
    @include('partials.head')
    <title>{{ __('ui.under_construction') }}</title>
</head>
<body class="theme-{{ ui_theme() }} aw-center">
<div class="text-center p-4">
    <div class="aw-construction mb-4"><i class="bi bi-cone-striped"></i></div>
    <h1 class="h3 fw-semibold mb-2">{{ __('ui.under_construction') }}</h1>
    <p class="text-muted mb-0">{{ __('ui.under_construction_text') }}</p>
</div>
</body>
</html>
