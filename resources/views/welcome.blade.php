@php
    $settings = \App\Models\StoreSetting::first();
    // Defaulting to a vibrant color so it pops on dark mode if they haven't set one
    $primaryColor = $settings && $settings->primary_color != '#111827' ? $settings->primary_color : '#3b82f6';
    $storeName = $settings ? $settings->store_name : 'E-commerce Web';
    $products = \App\Models\Product::all();
    $isMayorista = auth()->check() && auth()->user()->role === 'mayorista';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $storeName }}</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800,900&display=swap" rel="stylesheet" />
    
    <script>
        function applyTheme() {
            if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark')
            } else {
                document.documentElement.classList.remove('dark')
            }
        }
        applyTheme();
        document.addEventListener('livewire:navigated', applyTheme);
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
        
        .card-hover:hover {
            border-color: color-mix(in srgb, var(--color-primary) 50%, transparent);
        }
        .dark .card-hover:hover {
            box-shadow: 0 20px 40px -10px var(--color-primary-glow);
            border-color: color-mix(in srgb, var(--color-primary) 50%, #374151);
        }
    </style>
</head>
<body class="antialiased min-h-screen flex flex-col relative overflow-x-hidden selection:bg-[var(--color-primary)] selection:text-white bg-gray-50 text-gray-900 dark:bg-[#0b0f19] dark:text-gray-100 transition-colors duration-300" x-data="{ darkMode: localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches) }" @toggle-theme.window="darkMode = !darkMode; if(darkMode) { document.documentElement.classList.add('dark'); localStorage.theme = 'dark'; } else { document.documentElement.classList.remove('dark'); localStorage.theme = 'light'; }">
    
    <!-- Subtle Background Glow -->
    <div x-show="darkMode" x-transition.opacity.duration.500ms class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[500px] opacity-30 pointer-events-none" style="background: radial-gradient(circle, var(--color-primary-glow) 0%, transparent 70%);"></div>

    <!-- Navbar -->
    <header class="relative z-50 border-b border-gray-200 dark:border-gray-800/60 bg-white/80 dark:bg-[#0b0f19]/80 backdrop-blur-lg transition-colors duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                @if($settings && $settings->logo_url)
                    <img src="{{ asset('storage/' . $settings->logo_url) }}" alt="Logo" class="h-10 object-contain drop-shadow-md">
                @else
                    <h1 class="text-2xl font-black tracking-tighter text-gray-900 dark:text-white">{{ $storeName }}</h1>
                @endif
            </div>
            <nav class="flex items-center space-x-4 sm:space-x-6 text-sm font-medium tracking-wide">
                
                <!-- Cart Icon -->
                <livewire:cart-icon />

                <button @click="$dispatch('toggle-theme')" class="p-2 rounded-full bg-gray-200 dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors focus:outline-none">
                    <svg x-show="!darkMode" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
                    <svg x-show="darkMode" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                </button>

                @if (Route::has('login'))
                    @auth
                        <a href="{{ route('admin.products') }}" class="transition-colors hover:text-gray-900 dark:hover:text-white px-4 py-2 rounded-full border border-gray-300 dark:border-gray-700 bg-gray-100 dark:bg-gray-800/50 hover:bg-gray-200 dark:hover:bg-gray-700/50 text-gray-700 dark:text-gray-300">Panel de Control</a>
                    @else
                        <a href="{{ route('login') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">Ingresar</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="px-5 py-2 rounded-full text-white shadow-lg transition-all hover:opacity-90" style="background-color: var(--color-primary); box-shadow: 0 4px 14px 0 var(--color-primary-glow);">Registrarse</a>
                        @endif
                    @endauth
                @endif
            </nav>
        </div>
    </header>

    <livewire:layout.navigation />
        
    <!-- Hero Banner Carousel -->
    <div class="relative w-full h-[400px] sm:h-[500px] bg-gray-900 overflow-hidden group">
        <!-- Background Image -->
        <div class="absolute inset-0">
            <img src="{{ asset('storage/banners/hero_banner.png') }}" class="w-full h-full object-cover opacity-60 mix-blend-overlay">
            <div class="absolute inset-0 bg-gradient-to-r from-black/80 via-black/50 to-transparent"></div>
        </div>
        
        <!-- Content -->
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-full flex flex-col justify-center">
            <div class="max-w-2xl transform transition-all duration-700 translate-y-0 opacity-100">
                <span class="inline-block py-1 px-3 rounded-full bg-[var(--color-primary)]/20 text-[var(--color-primary)] border border-[var(--color-primary)]/30 text-xs font-bold uppercase tracking-widest mb-4 backdrop-blur-sm shadow-[0_0_15px_var(--color-primary-glow)]">Nueva Generación</span>
                <h1 class="text-4xl sm:text-6xl font-black text-white tracking-tight leading-tight mb-4 drop-shadow-lg">
                    Potencia absoluta.<br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-[var(--color-primary)] to-blue-500">Hardware Premium.</span>
                </h1>
                <p class="text-lg sm:text-xl text-gray-300 mb-8 max-w-xl font-light">
                    Encuentra los componentes más avanzados del mercado, verifica compatibilidades y arma el equipo de tus sueños.
                </p>
                <div class="flex flex-wrap gap-4">
                    <button onclick="window.scrollTo({top: document.getElementById('catalog').offsetTop, behavior: 'smooth'})" class="px-8 py-4 bg-[var(--color-primary)] hover:bg-opacity-90 text-white rounded-full font-bold shadow-[0_0_20px_var(--color-primary-glow)] transition-all hover:-translate-y-1">
                        Explorar Catálogo
                    </button>
                    <button class="px-8 py-4 bg-white/10 hover:bg-white/20 text-white border border-white/20 rounded-full font-bold backdrop-blur-sm transition-all hover:-translate-y-1">
                        Ofertas del Mes
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Decorative Elements -->
        <div class="absolute bottom-0 left-0 w-full h-24 bg-gradient-to-t from-gray-50 dark:from-gray-900 to-transparent"></div>
    </div>

    <main id="catalog" class="relative z-10 flex-grow pt-12" x-data="{ showGrid: false }" x-init="setTimeout(() => showGrid = true, 300)">        <!-- Products Grid (Livewire Component) -->
        <livewire:product-grid />
    </main>

    <!-- Slide-over Cart Panel -->
    <livewire:cart-panel />
</body>
</html>
