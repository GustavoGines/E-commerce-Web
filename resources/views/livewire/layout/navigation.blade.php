<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();
        $this->redirect(route('home'), navigate: true);
    }
}; ?>

<nav x-data="{ open: false }"
     class="bg-white/90 dark:bg-slate-900/90 backdrop-blur-xl
            border-b border-slate-200/60 dark:border-slate-800/60
            sticky top-0 z-50 transition-colors duration-300 shadow-sm dark:shadow-none">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">

            {{-- ── Izquierda: Logo + Links ── --}}
            <div class="flex items-center gap-8">
                {{-- Logo --}}
                <a href="{{ url('/') }}" wire:navigate class="shrink-0 flex items-center">
                    @php $settings = \App\Models\StoreSetting::first(); @endphp
                    @if($settings && $settings->logo_url)
                        <img src="{{ asset('storage/' . $settings->logo_url) }}"
                             alt="Logo" class="h-9 w-auto object-contain drop-shadow-md">
                    @else
                        <x-application-logo class="block h-9 w-auto fill-current text-slate-800 dark:text-slate-200 transition-colors"/>
                    @endif
                </a>

                {{-- Nav links desktop --}}
                <div class="hidden sm:flex items-center gap-1">
                    <x-nav-link :href="route('home')" :active="request()->routeIs('home')" wire:navigate>
                        {{ __('Tienda') }}
                    </x-nav-link>
                    @if(auth()->check() && optional(auth()->user())->role === 'admin')
                        <x-nav-link :href="route('admin.products')" :active="request()->routeIs('admin.products')" wire:navigate>
                            {{ __('Productos') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            {{-- ── Centro: Buscador ── --}}
            <div class="hidden sm:flex flex-1 items-center justify-center px-6">
                <div class="w-full max-w-lg">
                    <livewire:search-bar />
                </div>
            </div>

            {{-- ── Derecha: Acciones desktop ── --}}
            <div class="hidden sm:flex items-center gap-2">

                {{-- Carrito --}}
                <livewire:cart-icon />

                {{-- Toggle Tema — onclick puro, siempre funciona --}}
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

                {{-- Usuario --}}
                @auth
                    <x-dropdown align="right" width="56">
                        <x-slot name="trigger">
                            <button class="flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-medium
                                           text-slate-600 dark:text-slate-300
                                           hover:bg-slate-100 dark:hover:bg-slate-800
                                           hover:text-slate-900 dark:hover:text-white
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
                                <x-dropdown-link :href="route('admin.products')" wire:navigate>🗃️ &nbsp;Catálogo de Productos</x-dropdown-link>
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
                       class="px-4 py-2 text-sm font-semibold text-slate-700 dark:text-slate-300
                              hover:text-slate-900 dark:hover:text-white transition-colors">
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

            {{-- ── Móvil: Carrito + Tema + Hamburger ── --}}
            <div class="flex items-center gap-1 sm:hidden">
                <livewire:cart-icon />

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
         class="hidden sm:hidden bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 transition-colors duration-300">
        <div class="pt-2 pb-3 space-y-1 px-4">
            <x-responsive-nav-link :href="url('/')" wire:navigate>🏠 Ir a la Tienda</x-responsive-nav-link>
            @if(auth()->check() && optional(auth()->user())->role === 'admin')
                <x-responsive-nav-link :href="route('admin.products')" :active="request()->routeIs('admin.products')" wire:navigate>
                    Productos
                </x-responsive-nav-link>
            @endif
        </div>

        @auth
        <div class="pt-4 pb-3 border-t border-slate-200 dark:border-slate-800">
            <div class="px-4 mb-3">
                <div class="font-bold text-slate-800 dark:text-slate-200"
                     x-data="{{ json_encode(['name' => optional(auth()->user())->name ?? 'Usuario']) }}"
                     x-text="name"
                     x-on:profile-updated.window="name = $event.detail.name"></div>
                <div class="text-sm text-slate-500 dark:text-slate-400">{{ optional(auth()->user())->email }}</div>
            </div>
            <div class="space-y-1 px-4">
                @if(optional(auth()->user())->role === 'admin')
                    <x-responsive-nav-link :href="route('admin.products')" wire:navigate>Catálogo de Productos</x-responsive-nav-link>
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
        <div class="pt-4 pb-3 border-t border-slate-200 dark:border-slate-800 space-y-1 px-4">
            <x-responsive-nav-link :href="route('login')" wire:navigate>Iniciar Sesión</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('register')" wire:navigate>Registrarse</x-responsive-nav-link>
        </div>
        @endauth
    </div>

</nav>
