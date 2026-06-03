@php
    $settings = \App\Models\StoreSetting::first();
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

        <!-- Tema y Stores de Alpine: inicialización segura -->
        <script>
            (function () {
                var themeName = '{{ $settings->theme_name ?? 'stealth' }}';
                var isLuxury = themeName === 'luxury';
                var isModernLight = themeName === 'modern-light';
                
                if (isLuxury) {
                    document.documentElement.classList.add('dark');
                    localStorage.theme = 'dark';
                } else if (isModernLight) {
                    document.documentElement.classList.remove('dark');
                    localStorage.theme = 'light';
                } else {
                    var dark = localStorage.theme === 'dark' ||
                        (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches);
                    document.documentElement.classList.toggle('dark', dark);
                }
            })();

            // Definir stores ANTES de que Alpine arranque
            document.addEventListener('alpine:init', () => {
                Alpine.store('theme', {
                    dark: document.documentElement.classList.contains('dark'),
                    toggle() { 
                        this.dark = !this.dark; 
                        document.documentElement.classList.toggle('dark', this.dark);
                        localStorage.theme = this.dark ? 'dark' : 'light';
                    },
                    apply() { 
                        document.documentElement.classList.toggle('dark', this.dark);
                        localStorage.theme = this.dark ? 'dark' : 'light';
                    }
                });

                Alpine.store('cart', {
                    open: false,
                    show()   { this.open = true;  },
                    hide()   { this.open = false; },
                    toggle() { this.open = !this.open; }
                });
            });
        </script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            :root {
                @if(($settings->theme_name ?? 'stealth') === 'modern-light')
                    /* JCG Electrónica Primary Color: Tailwind red-600 */
                    --color-primary: #DC2626;
                    --color-primary-hover: #B91C1C; /* red-700 */
                @else
                    /* Default Blue Primary Color */
                    --color-primary: #2563EB;
                    --color-primary-hover: #1D4ED8;
                @endif
                --color-primary-glow: color-mix(in srgb, var(--color-primary) 30%, transparent);
            }
            body {
                font-family: 'Inter', sans-serif;
            }
        </style>
    </head>
    <body class="antialiased min-h-screen flex flex-col relative overflow-x-hidden selection:bg-[var(--color-primary)] selection:text-white bg-white text-gray-900 {{ ($settings->theme_name ?? 'stealth') === 'modern-light' ? '' : 'dark:bg-[#0b0f19] dark:text-gray-100' }} transition-colors duration-300">
        
        <!-- Subtle Background Glow (Dark Mode Only) -->
        <div x-data="{}" x-show="$store.theme.dark && '{{ $settings->theme_name ?? 'stealth' }}' !== 'modern-light'" x-transition.opacity.duration.500ms class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[500px] opacity-20 pointer-events-none" style="background: radial-gradient(circle, var(--color-primary-glow) 0%, transparent 70%);"></div>

        <div class="min-h-screen flex flex-col relative z-10 w-full">
            <livewire:layout.navigation />

            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-white/80 dark:bg-[#0b0f19]/80 backdrop-blur-md border-b border-gray-200 dark:border-gray-800 transition-colors duration-300">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main class="flex-grow">
                {{ $slot }}
            </main>
        </div>

        <!-- Slide-over Cart Panel -->
        <livewire:cart-panel />

        <!-- Footer -->
        <livewire:layout.footer />
    </body>
</html>
