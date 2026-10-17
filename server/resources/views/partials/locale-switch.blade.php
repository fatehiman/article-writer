{{-- Language links. $class is added to each link; $box (optional) wraps the icon, e.g. "rt-ico". --}}
@foreach (config('languages.ui') as $code => $lang)
    @if ($code !== app()->getLocale())
        <a class="{{ $class ?? '' }}" href="{{ route('locale', $code) }}" lang="{{ $code }}" dir="{{ $lang['dir'] }}">
            @if (! empty($box))<span class="{{ $box }}"><i class="bi bi-translate"></i></span>@else<i class="bi bi-translate"></i>@endif
            {{ $lang['native'] }}
        </a>
    @endif
@endforeach
