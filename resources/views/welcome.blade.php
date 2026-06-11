@php
    $theme = app('activeTheme') ?? 'stealth';
    if (!view()->exists('themes.' . $theme . '.welcome')) {
        $theme = 'stealth';
    }
@endphp

@include('themes.' . $theme . '.welcome')
