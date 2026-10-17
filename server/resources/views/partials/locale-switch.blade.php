{{-- Language links. $class is added to each link. --}}
@foreach (config('languages.ui') as $code => $lang)
    @if ($code !== app()->getLocale())
        <a class="{{ $class ?? '' }}" href="{{ route('locale', $code) }}" lang="{{ $code }}" dir="{{ $lang['dir'] }}">
            <i class="bi bi-translate"></i> {{ $lang['native'] }}
        </a>
    @endif
@endforeach
