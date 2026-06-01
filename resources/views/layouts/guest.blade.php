@php
    $settings = \App\Models\StoreSetting::first();
    $primaryColor = $settings && $settings->primary_color != '#111827' ? $settings->primary_color : '#3b82f6';
    $logoUrl = $settings ? $settings->logo_url : null;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800,900&display=swap" rel="stylesheet" />

        <!-- Tema: aplicar antes de Alpine (anti-flash) -->
        <script>
            (function () {
                var dark = localStorage.theme === 'dark' ||
                    (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.toggle('dark', dark);
            })();
        </script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            :root {
                --color-primary: {{ $primaryColor }};
                --color-primary-glow: color-mix(in srgb, var(--color-primary) 30%, transparent);
            }
            body {
                font-family: 'Inter', sans-serif;
            }
            [x-cloak] { display: none !important; }
        </style>
    </head>
        <body class="antialiased min-h-screen flex flex-col relative overflow-x-hidden selection:bg-[var(--color-primary)] selection:text-white bg-gray-50 text-gray-900 dark:bg-[#0b0f19] dark:text-gray-100 transition-colors duration-300">
        
        <!-- Subtle Background Glow (Dark Mode Only) -->
        <div x-data="{}" x-show="$store.theme.dark" x-transition.opacity.duration.500ms class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[500px] opacity-20 pointer-events-none" style="background: radial-gradient(circle, var(--color-primary-glow) 0%, transparent 70%);"></div>

        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 relative z-10 w-full">
            <div class="absolute top-6 right-6">
                <!-- Theme Toggle Button -->
                <button @click="$store.theme.toggle()" class="p-2 rounded-full bg-gray-200 dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors focus:outline-none shadow-sm">
                    <svg x-show="!$store.theme.dark" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
                    <svg x-show="$store.theme.dark" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                </button>
            </div>
            
            <div class="flex flex-col items-center w-full animate-fade-in-up">
                <a href="/" wire:navigate class="mb-6 block transition-transform hover:scale-105 duration-300">
                    @if($logoUrl)
                        <img src="{{ asset('storage/' . $logoUrl) }}" alt="Logo" class="w-24 h-24 object-contain drop-shadow-md" />
                    @else
                        <x-application-logo class="w-20 h-20 fill-current text-gray-800 dark:text-white transition-colors" />
                    @endif
                </a>

                <div class="w-full sm:max-w-md px-8 py-10 bg-white/80 dark:bg-gray-800/40 backdrop-blur-xl border border-gray-200 dark:border-gray-700/50 shadow-xl dark:shadow-2xl overflow-hidden sm:rounded-3xl transition-all duration-300" :style="darkMode ? 'box-shadow: 0 20px 40px -10px var(--color-primary-glow);' : ''">
                    {{ $slot }}
                </div>
            </div>
        </div>

        <!-- Slide-over Cart Panel -->
        <livewire:cart-panel />
    </body>
</html>
