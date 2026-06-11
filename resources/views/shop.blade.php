@php
    $theme = app('activeTheme') ?? 'stealth';
    if (!view()->exists('themes.' . $theme . '.shop')) {
        $theme = 'stealth';
    }
@endphp

@include('themes.' . $theme . '.shop')
