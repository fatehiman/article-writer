<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="rtl">
<head>
    @include('partials.head')
    <title>@yield('title', config('app.name')) · {{ config('app.name') }}</title>
</head>
<body class="theme-rtl">
@php $menu = app_menu(); @endphp

{{-- RTL theme: a side menu on the right (an off-canvas drawer on small screens). --}}
<aside class="offcanvas-lg offcanvas-end rt-side" tabindex="-1" id="rtSide" aria-labelledby="rtSideTitle">
    <div class="rt-side-inner">
        <div class="d-flex align-items-center justify-content-between px-3 pt-3 pb-2">
            <a class="d-flex align-items-center gap-2 text-decoration-none" href="{{ route('dashboard') }}" id="rtSideTitle">
                <span class="rt-logo"><i class="bi bi-feather"></i></span>
                <span class="rt-brand">{{ config('app.name') }}</span>
            </a>
            <button type="button" class="btn-close d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#rtSide" aria-label="{{ __('ui.close') }}"></button>
        </div>

        <div class="rt-user mx-3 mb-3">
            <div class="fw-semibold">{{ auth()->user()->name }}</div>
            <a href="{{ route('wallet') }}" class="small text-decoration-none rt-balance"><i class="bi bi-wallet2"></i> {{ money(auth()->user()->wallet_balance) }}</a>
        </div>

        <nav class="px-2">
            @foreach ($menu['main'] as $item)
                <a class="rt-link tone-{{ $item['tone'] }} {{ request()->routeIs($item['match']) ? 'active' : '' }}" href="{{ route($item['route']) }}">
                    <span class="rt-ico"><i class="bi {{ $item['icon'] }}"></i></span>{{ __($item['label']) }}
                </a>
            @endforeach
            @if ($menu['admin'])
                <div class="rt-group">{{ __('ui.admin') }}</div>
                @foreach ($menu['admin'] as $item)
                    <a class="rt-link tone-{{ $item['tone'] }} {{ request()->routeIs($item['match']) ? 'active' : '' }}" href="{{ route($item['route']) }}">
                        <span class="rt-ico"><i class="bi {{ $item['icon'] }}"></i></span>{{ __($item['label']) }}
                    </a>
                @endforeach
            @endif
            <div class="rt-group">{{ __('ui.account') }}</div>
            <a class="rt-link tone-gray {{ request()->routeIs('profile') ? 'active' : '' }}" href="{{ route('profile') }}">
                <span class="rt-ico"><i class="bi bi-person-gear"></i></span>{{ __('ui.profile') }}
            </a>
            @include('partials.locale-switch', ['class' => 'rt-link tone-sky', 'box' => 'rt-ico'])
            <form method="post" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button class="rt-link tone-rose w-100" type="submit"><span class="rt-ico"><i class="bi bi-box-arrow-left"></i></span>{{ __('ui.logout') }}</button>
            </form>
        </nav>
    </div>
</aside>

<div class="rt-main">
    <header class="rt-top d-lg-none">
        <button class="btn rt-burger" type="button" data-bs-toggle="offcanvas" data-bs-target="#rtSide" aria-controls="rtSide" aria-label="{{ __('ui.menu') }}">
            <i class="bi bi-list"></i>
        </button>
        <span class="rt-brand">{{ config('app.name') }}</span>
        <span class="rt-logo rt-logo-sm"><i class="bi bi-feather"></i></span>
    </header>

    <main class="container-fluid rt-content py-4">
        @include('partials.alerts')
        @yield('content')
    </main>
</div>

@stack('modals')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/app.js') }}?v=1"></script>
@stack('scripts')
</body>
</html>
