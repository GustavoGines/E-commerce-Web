@php
    $theme = app('activeTheme') ?? 'modern-light';
@endphp

@include('themes.' . $theme . '.welcome')
