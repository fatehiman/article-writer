<?php

use App\Models\Setting;
use Illuminate\Support\HtmlString;

if (! function_exists('money')) {
    function money(float|string|null $amount): string
    {
        $amount = (float) $amount;
        $decimals = abs($amount) > 0 && abs($amount) < 0.01 ? 4 : 2;

        return number_format($amount, $decimals).' '.Setting::get('currency');
    }
}

if (! function_exists('icon')) {
    /** Inline SVG icon (24px grid, stroke 2, currentColor). */
    function icon(string $name, int $size = 16): HtmlString
    {
        $paths = [
            'plus' => '<path d="M12 5v14M5 12h14"/>',
            'pencil' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
            'trash' => '<path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/>',
            'refresh' => '<path d="M21 12a9 9 0 1 1-3-6.7"/><path d="M21 3v6h-6"/>',
            'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
            'copy' => '<rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
            'send' => '<path d="M22 2 11 13"/><path d="M22 2l-7 20-4-9-9-4z"/>',
            'download' => '<path d="M12 3v12"/><path d="M7 10l5 5 5-5"/><path d="M5 21h14"/>',
            'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/>',
            'eye' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
            'play' => '<path d="M6 4l14 8-14 8z"/>',
            'key' => '<circle cx="7.5" cy="15.5" r="4.5"/><path d="M10.7 12.3 21 2M16 7l3 3M18 5l2 2"/>',
            'wallet' => '<path d="M20 7H5a2 2 0 0 1 0-4h13v4"/><path d="M3 5v14a2 2 0 0 0 2 2h15V7"/><path d="M16 14h.01"/>',
            'help' => '<circle cx="12" cy="12" r="9"/><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 3-3 3"/><path d="M12 17h.01"/>',
            'check' => '<path d="M20 6 9 17l-5-5"/>',
        ];

        return new HtmlString('<svg class="ico" viewBox="0 0 24 24" width="'.$size.'" height="'.$size.'" aria-hidden="true">'
            .($paths[$name] ?? '').'</svg>');
    }
}

if (! function_exists('icon_btn')) {
    /**
     * Icon-only button with a tooltip and a soft tone.
     * Tones: blue, green, amber, red, purple, teal, gray. $attrs must be safe HTML attributes.
     */
    function icon_btn(string $icon, string $tip, string $tone = 'gray', string $attrs = '', string $tag = 'button', string $class = ''): HtmlString
    {
        $t = e($tip);
        $type = $tag === 'button' && ! str_contains($attrs, 'type=') ? ' type="button"' : '';

        return new HtmlString("<{$tag} class=\"btn icon tone-{$tone} {$class}\" title=\"{$t}\" aria-label=\"{$t}\"{$type} {$attrs}>"
            .icon($icon)."</{$tag}>");
    }
}

if (! function_exists('ui_dir')) {
    function ui_dir(): string
    {
        return config('languages.ui.'.app()->getLocale().'.dir', 'ltr');
    }
}

if (! function_exists('ui_theme')) {
    function ui_theme(): string
    {
        return config('languages.ui.'.app()->getLocale().'.theme', 'ltr');
    }
}

if (! function_exists('app_menu')) {
    /** Menu of the panel: ['main' => [...], 'admin' => [...]]. Each item: route, match, icon, label, tone. */
    function app_menu(): array
    {
        $main = [
            ['dashboard', 'dashboard', 'bi-grid-1x2', 'ui.dashboard', 'violet'],
            ['services.index', 'services.*', 'bi-lightning-charge', 'ui.services', 'amber'],
            ['title-lists.index', 'title-lists.*', 'bi-card-list', 'ui.title_lists', 'mint'],
            ['articles.index', 'articles.*', 'bi-file-earmark-text', 'ui.articles', 'sky'],
            ['destinations.index', 'destinations.*', 'bi-send', 'ui.destinations', 'rose'],
            ['wallet', 'wallet', 'bi-wallet2', 'ui.wallet', 'peach'],
            ['docs', 'docs', 'bi-book', 'ui.docs', 'violet'],
        ];
        $admin = [
            ['admin.customers.index', 'admin.customers.*', 'bi-people', 'ui.customers', 'sky'],
            ['admin.agents.index', 'admin.agents.*', 'bi-robot', 'ui.ai_agents', 'violet'],
            ['admin.providers.index', 'admin.providers.*', 'bi-cpu', 'ui.ai_providers', 'mint'],
            ['admin.image-sizes.index', 'admin.image-sizes.*', 'bi-aspect-ratio', 'ui.image_sizes', 'rose'],
            ['admin.tones.index', 'admin.tones.*', 'bi-mic', 'ui.tones', 'amber'],
            ['admin.settings', 'admin.settings*', 'bi-gear', 'ui.settings', 'peach'],
        ];
        $map = fn ($rows) => array_map(fn ($r) => array_combine(['route', 'match', 'icon', 'label', 'tone'], $r), $rows);

        return ['main' => $map($main), 'admin' => auth()->user()?->isAdmin() ? $map($admin) : []];
    }
}

if (! function_exists('status_tone')) {
    function status_tone(string $status): string
    {
        return match ($status) {
            'ready', 'success' => 'mint',
            'failed' => 'rose',
            'generating', 'sending' => 'sky',
            default => 'amber',
        };
    }
}

if (! function_exists('local_time')) {
    /** Date/time in the viewer's time zone. */
    function local_time($date, string $format = 'Y-m-d H:i'): string
    {
        return $date ? \Illuminate\Support\Carbon::parse($date)->setTimezone(auth()->user()?->timezone ?: 'UTC')->format($format) : '—';
    }
}

if (! function_exists('article_language')) {
    function article_language(?string $code): string
    {
        $lang = config('languages.articles.'.$code);

        return $lang ? ($lang[0] === $lang[1] ? $lang[0] : "{$lang[1]} ({$lang[0]})") : (string) $code;
    }
}
