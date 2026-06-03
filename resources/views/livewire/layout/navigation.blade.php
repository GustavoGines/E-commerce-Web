<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public $transparent = false;

    public function logout(Logout $logout): void
    {
        $logout();
        $this->redirect(route('home'), navigate: true);
    }
}; ?>

<div class="contents">
    {{-- ── Top Announcement Bar (Luxury only, injected via a check) ── --}}
    @php
        $settings = \App\Models\StoreSetting::first();
        $themeName = $settings->theme_name ?? 'stealth';
        $isLuxury = ($themeName === 'luxury');
        $isModernLight = ($themeName === 'modern-light');
    @endphp

    @if($isLuxury)
        <div class="bg-[var(--color-primary)] text-white text-[10px] font-bold uppercase tracking-[0.2em] py-2 px-4 text-center flex justify-center items-center gap-4 relative z-[60]">
            <span class="hidden sm:inline">💎 Hardware Premium Seleccionado</span>
            <span>Envío Gratis a todo el país superando $50.000</span>
            <a href="{{ route('shop') }}" wire:navigate class="underline hover:text-white/80 transition-colors">Comprar Ahora</a>
        </div>
    @endif

<nav x-data="{ open: false, scrolled: false }"
     @scroll.window="scrolled = (window.pageYOffset > 20)"
     :class="{
         'bg-[#0a0f1c]/90 backdrop-blur-xl border-b border-white/5 shadow-none': {{ $isLuxury ? 'true' : 'false' }} && (!{{ $transparent ? 'true' : 'false' }} || scrolled),
         'bg-white/95 backdrop-blur-xl border-b border-gray-200 shadow-sm': {{ $isModernLight ? 'true' : 'false' }} && (!{{ $transparent ? 'true' : 'false' }} || scrolled),
         'bg-white/90 dark:bg-slate-900/90 backdrop-blur-xl border-b border-slate-200/60 dark:border-slate-800/60 shadow-sm dark:shadow-none': (!{{ $isLuxury ? 'true' : 'false' }} && !{{ $isModernLight ? 'true' : 'false' }}) && (!{{ $transparent ? 'true' : 'false' }} || scrolled),
         'bg-transparent border-transparent': {{ $transparent ? 'true' : 'false' }} && !scrolled
     }"
     class="sticky top-0 z-50 transition-all duration-300">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16 relative">

            @if($isLuxury)
                {{-- ════════════ LUXURY NAVBAR ════════════ --}}
                {{-- Izquierda: Links --}}
                <div class="hidden sm:flex flex-1 items-center gap-6">
                    <x-nav-link :href="route('home')" :active="request()->routeIs('home')" wire:navigate>
                        {{ __('Inicio') }}
                    </x-nav-link>
                    <x-nav-link :href="route('shop')" :active="request()->routeIs('shop')" wire:navigate>
                        {{ __('Tienda') }}
                    </x-nav-link>
                    @if(auth()->check() && optional(auth()->user())->role === 'admin')
                        <x-nav-link :href="route('admin.products')" :active="request()->routeIs('admin.products')" wire:navigate>
                            {{ __('Productos') }}
                        </x-nav-link>
                    @endif
                </div>

                {{-- Centro: Logo (Absolute Center) --}}
                <div class="hidden sm:flex absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 items-center justify-center">
                    <a href="{{ url('/') }}" wire:navigate class="shrink-0 flex items-center">
                        @if(isset($settings) && $settings->logo_url)
                            <img src="{{ asset('storage/' . $settings->logo_url) }}"
                                 alt="Logo" class="h-8 w-auto object-contain drop-shadow-md">
                        @else
                            <x-application-logo class="block h-8 w-auto fill-current text-white transition-colors"/>
                        @endif
                    </a>
                </div>
                
                {{-- Mobile Solo Logo (se ve en movil cuando links estan ocultos) --}}
                <div class="flex sm:hidden flex-1 items-center">
                    <a href="{{ url('/') }}" wire:navigate class="shrink-0 flex items-center">
                        @if(isset($settings) && $settings->logo_url)
                            <img src="{{ asset('storage/' . $settings->logo_url) }}" alt="Logo" class="h-7 w-auto object-contain">
                        @else
                            <x-application-logo class="block h-7 w-auto fill-current text-white"/>
                        @endif
                    </a>
                </div>
            @elseif($isModernLight)
                {{-- ════════════ MODERN-LIGHT NAVBAR ════════════ --}}
                {{-- Marca de agua (Watermark) --}}
                <div class="absolute inset-0 pointer-events-none opacity-[0.03]" style="background-image: url(&quot;data:image/svg+xml,%3Csvg width='150' height='80' xmlns='http://www.w3.org/2000/svg'%3E%3Ctext x='50%25' y='50%25' font-size='48' font-family='sans-serif' font-weight='900' fill='%23DC2626' dominant-baseline='middle' text-anchor='middle' transform='rotate(-15, 75, 40)'%3EGJ%3C/text%3E%3C/svg%3E&quot;);"></div>
                
                <div class="flex items-center justify-between flex-1 relative z-10">
                    {{-- ── Izquierda: Links ── --}}
                    <div class="hidden sm:flex items-center gap-6 flex-1">
                        <x-nav-link :href="route('home')" :active="request()->routeIs('home')" wire:navigate class="text-base font-bold text-gray-700 hover:text-[var(--color-primary)]">
                            {{ __('Inicio') }}
                        </x-nav-link>
                        <x-nav-link :href="route('shop')" :active="request()->routeIs('shop')" wire:navigate class="text-base font-bold text-gray-700 hover:text-[var(--color-primary)]">
                            {{ __('Tienda') }}
                        </x-nav-link>
                        @if(auth()->check() && optional(auth()->user())->role === 'admin')
                            <x-nav-link :href="route('admin.products')" :active="request()->routeIs('admin.products')" wire:navigate class="text-base font-bold text-gray-700 hover:text-[var(--color-primary)]">
                                {{ __('Productos') }}
                            </x-nav-link>
                        @endif
                    </div>

                    {{-- ── Centro: Logo (Entre links y buscador) ── --}}
                    <a href="{{ url('/') }}" wire:navigate class="shrink-0 flex items-center justify-center px-6">
                        @if(isset($settings) && $settings->logo_url)
                            <img src="{{ asset('storage/' . $settings->logo_url) }}"
                                 alt="Logo" class="h-16 w-auto object-contain drop-shadow-sm hover:scale-105 transition-transform">
                        @else
                            <div class="flex items-center gap-2 text-[var(--color-primary)]">
                                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L2 22h20L12 2zm0 4.5l6.5 13h-13L12 6.5z"/></svg>
                                <span class="text-2xl font-black tracking-tight text-gray-900">JCG <span class="text-[var(--color-primary)]">Electrónica</span></span>
                            </div>
                        @endif
                    </a>

                    {{-- ── Derecha: Buscador + Íconos ── --}}
                    <div class="hidden sm:flex items-center gap-4 flex-1 justify-end">
                        <div class="hidden sm:block w-full max-w-sm mr-2">
                            <livewire:search-bar />
                        </div>
                        <livewire:cart-icon />
                        @auth
                            <x-dropdown align="right" width="48">
                                <x-slot name="trigger">
                                    <button class="flex items-center gap-2 p-2 rounded-full hover:bg-gray-100 transition-colors">
                                        <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center font-bold text-gray-700">
                                            {{ substr(Auth::user()->name, 0, 1) }}
                                        </div>
                                    </button>
                                </x-slot>
                                <x-slot name="content">
                                    @if(optional(auth()->user())->role === 'admin')
                                        <div class="px-3 py-1.5">
                                            <p class="text-[10px] uppercase tracking-widest font-bold text-gray-500">Administración</p>
                                        </div>
                                        <x-dropdown-link :href="route('admin.orders')" wire:navigate>📦 &nbsp;Órdenes</x-dropdown-link>
                                        <x-dropdown-link :href="route('admin.settings')" wire:navigate>⚙️ &nbsp;Configuración</x-dropdown-link>
                                        <div class="my-1 border-t border-gray-100"></div>
                                    @else
                                        <x-dropdown-link :href="route('my-orders')" wire:navigate>🛍 &nbsp;Mis Órdenes</x-dropdown-link>
                                    @endif
                                    <x-dropdown-link :href="route('profile')" wire:navigate>👤 &nbsp;Mi Perfil</x-dropdown-link>
                                    <div class="my-1 border-t border-gray-100"></div>
                                    <button wire:click="logout" class="w-full text-start">
                                        <x-dropdown-link class="text-red-600 hover:text-red-700">🚪 &nbsp;Cerrar Sesión</x-dropdown-link>
                                    </button>
                                </x-slot>
                            </x-dropdown>
                        @else
                            <a href="{{ route('login') }}" wire:navigate class="text-sm font-bold text-gray-600 hover:text-[var(--color-primary)] transition-colors">
                                Iniciar Sesión
                            </a>
                            <a href="{{ route('register') }}" wire:navigate class="px-4 py-2 text-sm font-bold text-white rounded-xl transition-all shadow hover:shadow-md hover:-translate-y-0.5 bg-[var(--color-primary)] hover:bg-[var(--color-primary)]/90">
                                Registrarse
                            </a>
                        @endauth
                    </div>
                </div>
            @else
                {{-- ════════════ STEALTH NAVBAR ════════════ --}}
                {{-- ── Izquierda: Logo + Links ── --}}
                <div class="flex items-center gap-8 flex-1">
                    {{-- Logo --}}
                    <a href="{{ url('/') }}" wire:navigate class="shrink-0 flex items-center">
                        @if(isset($settings) && $settings->logo_url)
                            <img src="{{ asset('storage/' . $settings->logo_url) }}"
                                 alt="Logo" class="h-9 w-auto object-contain drop-shadow-md">
                        @else
                            <x-application-logo class="block h-9 w-auto fill-current text-slate-800 dark:text-slate-200 transition-colors"/>
                        @endif
                    </a>

                    {{-- Nav links desktop --}}
                    <div class="hidden sm:flex items-center gap-1">
                        <x-nav-link :href="route('home')" :active="request()->routeIs('home')" wire:navigate>
                            {{ __('Inicio') }}
                        </x-nav-link>
                        <x-nav-link :href="route('shop')" :active="request()->routeIs('shop')" wire:navigate>
                            {{ __('Tienda') }}
                        </x-nav-link>
                    </div>
                </div>

                {{-- ── Centro: Buscador ── --}}
                <div class="hidden sm:flex flex-1 items-center justify-center px-6">
                    <div class="w-full max-w-lg">
                        <livewire:search-bar />
                    </div>
                </div>
            @endif

            {{-- ── Derecha: Acciones desktop (Compartido, pero adaptado) ── --}}
            @if(!$isModernLight)
            <div class="hidden sm:flex {{ $isLuxury ? 'flex-1 justify-end' : '' }} items-center gap-4">

                {{-- Solo mostrar buscador compacto en Luxury --}}
                @if($isLuxury)
                    <div class="w-48 hidden lg:block">
                        <livewire:search-bar />
                    </div>
                @endif

                {{-- Carrito --}}
                <livewire:cart-icon />

                {{-- Toggle Tema — onclick puro, siempre funciona (Sólo en Stealth puro, ocultar en modern-light) --}}
                @if(!$isLuxury)
                    <button onclick="POS.toggleTheme()" id="nav-theme-btn"
                            class="relative p-2.5 rounded-xl
                                   bg-slate-100 dark:bg-slate-800
                                   text-slate-600 dark:text-slate-300
                                   hover:bg-slate-200 dark:hover:bg-slate-700
                                   hover:text-slate-900 dark:hover:text-white
                                   transition-all focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/30"
                            aria-label="Cambiar tema">
                        {{-- Ícono: Sol (visible en dark, clic → light) --}}
                        <svg id="icon-sun" class="w-5 h-5 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        {{-- Ícono: Luna (visible en light, clic → dark) --}}
                        <svg id="icon-moon" class="w-5 h-5 block dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                    </button>
                @endif

                {{-- Usuario --}}
                @auth
                    <x-dropdown align="right" width="56" :contentClasses="$isLuxury ? 'py-1 bg-[#0a0f1c] border border-white/5' : 'py-1 bg-white dark:bg-gray-800'">
                        <x-slot name="trigger">
                            <button class="flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-medium
                                           text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white
                                           focus:outline-none transition-all">
                                <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-xs font-bold shadow-sm"
                                     style="background: linear-gradient(135deg, var(--color-primary), color-mix(in srgb, var(--color-primary) 60%, #7c3aed))">
                                    {{ strtoupper(substr(optional(auth()->user())->name ?? 'U', 0, 1)) }}
                                </div>
                                <span x-data="{{ json_encode(['name' => optional(auth()->user())->name ?? 'Usuario']) }}"
                                      x-text="name"
                                      x-on:profile-updated.window="name = $event.detail.name"
                                      class="max-w-[100px] truncate"></span>
                                <svg class="w-3.5 h-3.5 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            @if(optional(auth()->user())->role === 'admin')
                                <div class="px-3 py-1.5">
                                    <p class="text-[10px] uppercase tracking-widest font-bold text-slate-400 dark:text-slate-500">Administración</p>
                                </div>
                                <x-dropdown-link :href="route('admin.orders')" wire:navigate>📦 &nbsp;Órdenes</x-dropdown-link>
                                <x-dropdown-link :href="route('admin.settings')" wire:navigate>⚙️ &nbsp;Configuración</x-dropdown-link>
                                <div class="my-1 border-t border-slate-100 dark:border-slate-700/60"></div>
                            @else
                                <x-dropdown-link :href="route('my-orders')" wire:navigate>🛍 &nbsp;Mis Órdenes</x-dropdown-link>
                            @endif
                            <x-dropdown-link :href="route('profile')" wire:navigate>👤 &nbsp;Mi Perfil</x-dropdown-link>
                            <div class="my-1 border-t border-slate-100 dark:border-slate-700/60"></div>
                            <button wire:click="logout" class="w-full text-start">
                                <x-dropdown-link>🚪 &nbsp;Cerrar Sesión</x-dropdown-link>
                            </button>
                        </x-slot>
                    </x-dropdown>
                @else
                    <a href="{{ route('login') }}" wire:navigate
                       class="px-4 py-2 text-sm font-semibold text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition-colors">
                        Iniciar Sesión
                    </a>
                    <a href="{{ route('register') }}" wire:navigate
                       class="px-4 py-2 text-sm font-bold text-white rounded-xl transition-all
                              hover:opacity-90 hover:-translate-y-0.5 shadow-md"
                       style="background: linear-gradient(135deg, var(--color-primary), color-mix(in srgb, var(--color-primary) 60%, #7c3aed))">
                        Registrarse
                    </a>
                @endauth
            </div>
            @endif

            {{-- ── Móvil: Carrito + Tema + Hamburger ── --}}
            <div class="flex items-center gap-1 sm:hidden">
                <livewire:cart-icon />

                @if(!$isLuxury && !$isModernLight)
                    <button onclick="POS.toggleTheme()"
                            class="p-2 rounded-xl text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all"
                            aria-label="Tema">
                        <svg class="w-5 h-5 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <svg class="w-5 h-5 block dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                    </button>
                @endif

                <button @click="open = !open"
                        class="p-2 rounded-xl text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all"
                        aria-label="Menú">
                    <svg class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': !open}" class="inline-flex"
                              stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        <path :class="{'hidden': !open, 'inline-flex': open}" class="hidden"
                              stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

        </div>
    </div>

    {{-- ── Menú Móvil desplegable ── --}}
    <div :class="{'block': open, 'hidden': !open}"
         class="hidden sm:hidden transition-colors duration-300 {{ $isLuxury ? 'bg-[#0a0f1c] border-t border-white/5' : 'bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800' }}">
        <div class="pt-2 pb-3 space-y-1 px-4">
            <x-responsive-nav-link :href="route('home')" :active="request()->routeIs('home')" wire:navigate>🏠 Inicio</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('shop')" :active="request()->routeIs('shop')" wire:navigate>🛍️ Tienda</x-responsive-nav-link>
            @if(auth()->check() && optional(auth()->user())->role === 'admin')
                <x-responsive-nav-link :href="route('admin.products')" :active="request()->routeIs('admin.products')" wire:navigate>
                    Productos
                </x-responsive-nav-link>
            @endif
        </div>

        @auth
        <div class="pt-4 pb-3 {{ $isLuxury ? 'border-t border-white/5' : 'border-t border-slate-200 dark:border-slate-800' }}">
            <div class="px-4 mb-3">
                <div class="font-bold text-slate-800 dark:text-slate-200"
                     x-data="{{ json_encode(['name' => optional(auth()->user())->name ?? 'Usuario']) }}"
                     x-text="name"
                     x-on:profile-updated.window="name = $event.detail.name"></div>
                <div class="text-sm text-slate-500 dark:text-slate-400">{{ optional(auth()->user())->email }}</div>
            </div>
            <div class="space-y-1 px-4">
                @if(optional(auth()->user())->role === 'admin')
                    <x-responsive-nav-link :href="route('admin.orders')" wire:navigate>Gestión de Órdenes</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.settings')" wire:navigate>Configuración</x-responsive-nav-link>
                @else
                    <x-responsive-nav-link :href="route('my-orders')" wire:navigate>Mis Órdenes</x-responsive-nav-link>
                @endif
                <x-responsive-nav-link :href="route('profile')" wire:navigate>Mi Perfil</x-responsive-nav-link>
                <button wire:click="logout" class="w-full text-start">
                    <x-responsive-nav-link>Cerrar Sesión</x-responsive-nav-link>
                </button>
            </div>
        </div>
        @else
        <div class="pt-4 pb-3 space-y-1 px-4 {{ $isLuxury ? 'border-t border-white/5' : 'border-t border-slate-200 dark:border-slate-800' }}">
            <x-responsive-nav-link :href="route('login')" wire:navigate>Iniciar Sesión</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('register')" wire:navigate>Registrarse</x-responsive-nav-link>
        </div>
        @endauth
    </div>

</nav>
</div>
