<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="ltr">
<head>
    @include('partials.head')
    <title>@yield('title', config('app.name')) · {{ config('app.name') }}</title>
</head>
<body class="theme-ltr">
@php $menu = app_menu(); @endphp
<nav class="navbar navbar-expand-xl aw-nav sticky-top">
    <div class="container-xxl">
        <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('dashboard') }}">
            <span class="aw-logo"><i class="bi bi-feather"></i></span>
            <span class="fw-semibold">{{ config('app.name') }}</span>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#awMenu"
                aria-controls="awMenu" aria-expanded="false" aria-label="{{ __('ui.menu') }}">
            <i class="bi bi-list fs-2"></i>
        </button>
        <div class="collapse navbar-collapse" id="awMenu">
            <ul class="navbar-nav ms-auto gap-xl-1 py-2 py-xl-0">
                @foreach ($menu['main'] as $item)
                    <li class="nav-item">
                        <a class="nav-link aw-link tone-{{ $item['tone'] }} {{ request()->routeIs($item['match']) ? 'active' : '' }}" href="{{ route($item['route']) }}">
                            <span class="aw-ico"><i class="bi {{ $item['icon'] }}"></i></span>{{ __($item['label']) }}
                        </a>
                    </li>
                @endforeach
                @if ($menu['admin'])
                    <li class="nav-item dropdown">
                        <a class="nav-link aw-link tone-rose dropdown-toggle {{ request()->routeIs('admin.*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="aw-ico"><i class="bi bi-shield-check"></i></span>{{ __('ui.admin') }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end aw-dropdown">
                            @foreach ($menu['admin'] as $item)
                                <li><a class="dropdown-item aw-link tone-{{ $item['tone'] }} {{ request()->routeIs($item['match']) ? 'active' : '' }}" href="{{ route($item['route']) }}">
                                    <span class="aw-ico"><i class="bi {{ $item['icon'] }}"></i></span>{{ __($item['label']) }}</a></li>
                            @endforeach
                        </ul>
                    </li>
                @endif
                <li class="nav-item dropdown">
                    <a class="nav-link aw-link tone-gray dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="aw-ico"><i class="bi bi-person-circle"></i></span><span class="d-xl-none">{{ auth()->user()->name }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end aw-dropdown">
                        <li class="px-3 py-2 small text-muted">{{ auth()->user()->name }}<br><span class="aw-mono">{{ auth()->user()->username }}</span></li>
                        <li><a class="dropdown-item" href="{{ route('profile') }}"><i class="bi bi-person-gear me-2"></i>{{ __('ui.profile') }}</a></li>
                        <li>@include('partials.locale-switch', ['class' => 'dropdown-item'])</li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="post" action="{{ route('logout') }}" class="m-0">
                                @csrf
                                <button class="dropdown-item" type="submit"><i class="bi bi-box-arrow-right me-2"></i>{{ __('ui.logout') }}</button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<main class="container-xxl py-4">
    @include('partials.alerts')
    @yield('content')
</main>

<footer class="container-xxl pb-4 text-center small text-muted">
    {{ config('app.name') }} · {{ __('ui.wallet') }}: <a href="{{ route('wallet') }}" class="text-reset fw-semibold">{{ money(auth()->user()->wallet_balance) }}</a>
</footer>

@stack('modals')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/app.js') }}?v=2"></script>
@stack('scripts')
</body>
</html>
