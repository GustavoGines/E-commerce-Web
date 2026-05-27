<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<nav x-data="{ open: false }" class="bg-white/80 dark:bg-[#0b0f19]/80 backdrop-blur-lg border-b border-gray-200 dark:border-gray-800/60 sticky top-0 z-50 transition-colors duration-300">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ url('/') }}" wire:navigate>
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200 transition-colors" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('home')" :active="request()->routeIs('home')" wire:navigate>
                        {{ __('Tienda') }}
                    </x-nav-link>
                    @if(auth()->check() && auth()->user()->role === 'mayorista')
                        <x-nav-link :href="route('admin.products')" :active="request()->routeIs('admin.products')" wire:navigate>
                            {{ __('Productos') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Search Bar -->
            <div class="hidden sm:flex flex-1 items-center justify-center px-8">
                <div class="w-full max-w-lg">
                    <livewire:search-bar />
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6 space-x-4">
                
                <!-- Cart Icon -->
                <livewire:cart-icon />

                <!-- Theme Toggle Button -->
                <button @click="$dispatch('toggle-theme')" class="p-2 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors focus:outline-none shadow-sm">
                    <svg x-show="!darkMode" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
                    <svg x-show="darkMode" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                </button>

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-600 dark:text-gray-300 bg-transparent hover:text-gray-900 dark:hover:text-white focus:outline-none transition ease-in-out duration-150">
                            <div x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        @if(auth()->user()->role === 'mayorista')
                            <x-dropdown-link :href="route('admin.settings')" wire:navigate>
                                {{ __('Configuración') }}
                            </x-dropdown-link>
                            <x-dropdown-link :href="route('admin.categories')" wire:navigate>
                                {{ __('Categorías') }}
                            </x-dropdown-link>
                            <x-dropdown-link :href="route('admin.orders')" wire:navigate>
                                {{ __('Gestión de Órdenes') }}
                            </x-dropdown-link>
                            <div class="border-t border-gray-200 dark:border-gray-700/50"></div>
                        @endif

                        <x-dropdown-link :href="route('my-orders')" wire:navigate>
                            {{ __('Mis Órdenes') }}
                        </x-dropdown-link>

                        <x-dropdown-link :href="route('profile')" wire:navigate>
                            {{ __('Mi Perfil') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <button wire:click="logout" class="w-full text-start">
                            <x-dropdown-link>
                                {{ __('Cerrar Sesión') }}
                            </x-dropdown-link>
                        </button>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden space-x-2">
                <!-- Cart Icon for Mobile -->
                <div class="mr-2">
                    <livewire:cart-icon />
                </div>

                <!-- Theme Toggle for Mobile -->
                <button @click="$dispatch('toggle-theme')" class="p-2 rounded-md text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 focus:outline-none transition duration-150">
                    <svg x-show="!darkMode" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
                    <svg x-show="darkMode" x-cloak class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                </button>

                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 dark:hover:text-gray-300 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-800 focus:text-gray-500 dark:focus:text-gray-300 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden bg-white dark:bg-[#0b0f19] border-b border-gray-200 dark:border-gray-800 transition-colors duration-300">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="url('/')" wire:navigate>
                {{ __('Ir a la Tienda') }}
            </x-responsive-nav-link>
            @if(auth()->check() && auth()->user()->role === 'mayorista')
                <x-responsive-nav-link :href="route('admin.products')" :active="request()->routeIs('admin.products')" wire:navigate>
                    {{ __('Productos') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-800">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800 dark:text-gray-200" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                <div class="font-medium text-sm text-gray-500 dark:text-gray-400">{{ auth()->user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                @if(auth()->user()->role === 'mayorista')
                    <x-responsive-nav-link :href="route('admin.settings')" wire:navigate>
                        {{ __('Configuración') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.categories')" wire:navigate>
                        {{ __('Categorías') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.orders')" wire:navigate>
                        {{ __('Gestión de Órdenes') }}
                    </x-responsive-nav-link>
                @endif
                <x-responsive-nav-link :href="route('my-orders')" wire:navigate>
                    {{ __('Mis Órdenes') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('profile')" wire:navigate>
                    {{ __('Mi Perfil') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <button wire:click="logout" class="w-full text-start">
                    <x-responsive-nav-link>
                        {{ __('Cerrar Sesión') }}
                    </x-responsive-nav-link>
                </button>
            </div>
        </div>
    </div>
</nav>
