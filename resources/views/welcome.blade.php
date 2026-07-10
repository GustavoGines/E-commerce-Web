@php
    $theme = app('activeTheme') ?? 'stealth';
    $theme = app('activeTheme') ?? 'stealth';
@endphp

@include('themes.' . $theme . '.welcome')
