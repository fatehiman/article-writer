<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
@if (ui_dir() === 'rtl')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/vazirmatn@33.0.3/Vazirmatn-font-face.css">
@else
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
@endif
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('css/base.css') }}?v=3">
<link rel="stylesheet" href="{{ asset('css/theme-'.ui_theme().'.css') }}?v=3">
