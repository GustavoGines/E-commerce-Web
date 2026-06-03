<?php

use App\Models\StoreSetting;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public $store_name = '';
    public $theme_name = 'stealth';
    public $logo;
    public $current_logo_url;

    public function mount()
    {
        $settings = StoreSetting::first();
        if ($settings) {
            $this->store_name = $settings->store_name;
            $this->theme_name = $settings->theme_name ?? 'stealth';
            $this->current_logo_url = $settings->logo_url;
        }
    }

    public function removeLogo()
    {
        $settings = StoreSetting::first();
        if ($settings && $settings->logo_url) {
            Storage::disk('public')->delete($settings->logo_url);
            $settings->logo_url = null;
            $settings->save();
        }
        $this->current_logo_url = null;
        $this->logo = null;
        session()->flash('message', 'Logo eliminado correctamente.');
    }

    public function save()
    {
        $this->validate([
            'store_name'    => 'required|string|max:255',
            'theme_name'    => 'required|string|in:stealth,luxury,modern-light',
            'logo'          => 'nullable|image|max:10240',
        ]);

        $settings = StoreSetting::first() ?? new StoreSetting();
        $settings->store_name    = $this->store_name;
        $settings->theme_name    = $this->theme_name;

        if ($this->logo) {
            // Eliminar el logo viejo del disco antes de subir el nuevo
            if ($settings->logo_url) {
                Storage::disk('public')->delete($settings->logo_url);
            }
            $path = $this->logo->store('logos', 'public');
            $settings->logo_url = $path;
            $this->current_logo_url = $path;
            $this->logo = null;
        }

        $settings->save();
        session()->flash('message', 'Configuración guardada exitosamente.');
        
        // Redirigir (recargar) la página sin 'navigate' para que los cambios de layout se apliquen instantáneamente.
        $this->redirect(route('admin.settings'), navigate: false);
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-900 dark:text-gray-100 leading-tight">
            {{ __('Configuración de la Tienda') }}
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
        <div class="bg-white/80 dark:bg-gray-800/40 backdrop-blur-md border border-gray-200 dark:border-gray-700/50 shadow-xl dark:shadow-2xl overflow-hidden sm:rounded-3xl p-8 transition-colors duration-300" :style="$store.theme.dark ? 'box-shadow: 0 10px 30px -10px var(--color-primary-glow);' : ''">
            @if (session()->has('message'))
                <div class="mb-6 bg-green-100 dark:bg-green-500/20 border border-green-400 dark:border-green-500/30 text-green-700 dark:text-green-400 px-4 py-3 rounded relative backdrop-blur-sm transition-colors duration-300">
                    {{ session('message') }}
                </div>
            @endif

            <form wire:submit="save">
                <div class="mb-5">
                    <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 uppercase tracking-wider transition-colors" for="store_name">
                        Nombre de la Tienda
                    </label>
                    <input wire:model="store_name" id="store_name" type="text" class="w-full py-3 px-4 bg-gray-50 dark:bg-gray-900/80 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-gray-100 leading-tight focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent transition-all shadow-sm dark:shadow-none">
                    @error('store_name') <span class="text-red-500 dark:text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="mb-5">
                    <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 uppercase tracking-wider transition-colors" for="theme_name">
                        Tema de la Tienda
                    </label>
                    <select wire:model="theme_name" id="theme_name" class="w-full py-3 px-4 bg-gray-50 dark:bg-gray-900/80 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-gray-100 leading-tight focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent transition-all shadow-sm dark:shadow-none">
                        <option value="stealth">Stealth (Tema Claro Predeterminado)</option>
                        <option value="luxury">Luxury (Tema Premium Oscuro)</option>
                        <option value="modern-light">Modern Light (Tema Limpio y Claro)</option>
                    </select>
                    @error('theme_name') <span class="text-red-500 dark:text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                {{-- SECCIÓN LOGO --}}
                <div class="mb-8">
                    <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-3 uppercase tracking-wider transition-colors">
                        Logo de la Tienda
                    </label>

                    {{-- Logo actual --}}
                    @if($current_logo_url)
                        <div class="flex items-center gap-4 mb-4 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-200 dark:border-gray-700">
                            <img src="{{ asset('storage/' . $current_logo_url) }}"
                                 alt="Logo Actual"
                                 class="h-16 w-auto object-contain rounded-lg bg-white dark:bg-gray-800 p-1 border border-gray-200 dark:border-gray-600">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Logo actual</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Seleccioná un nuevo archivo para reemplazarlo</p>
                            </div>
                            <button type="button"
                                    wire:click="removeLogo"
                                    wire:confirm="¿Estás seguro de que querés eliminar el logo?"
                                    wire:loading.attr="disabled"
                                    class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 border border-red-200 dark:border-red-500/30 rounded-lg transition-all">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Quitar logo
                            </button>
                        </div>
                    @endif

                    {{-- Preview del nuevo logo seleccionado (antes de guardar) --}}
                    @if($logo)
                        <div class="flex items-center gap-3 mb-3 p-3 bg-blue-50 dark:bg-blue-500/10 rounded-xl border border-blue-200 dark:border-blue-500/30">
                            <img src="{{ $logo->temporaryUrl() }}"
                                 alt="Preview nuevo logo"
                                 class="h-14 w-auto object-contain rounded-lg bg-white dark:bg-gray-800 p-1">
                            <div>
                                <p class="text-sm font-medium text-blue-700 dark:text-blue-400">Nuevo logo listo para guardar</p>
                                <p class="text-xs text-blue-500 dark:text-blue-500 mt-0.5">Hacé clic en "Guardar" para confirmar el cambio</p>
                            </div>
                        </div>
                    @endif

                    {{-- Input file --}}
                    <input wire:model="logo"
                           id="logo"
                           type="file"
                           accept="image/*"
                           class="w-full py-3 px-4 bg-gray-50 dark:bg-gray-900/80 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-700 dark:text-gray-300 leading-tight focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] transition-all shadow-sm dark:shadow-none file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-gray-200 dark:file:bg-gray-800 file:text-gray-700 dark:file:text-gray-300 hover:file:bg-gray-300 dark:hover:file:bg-gray-700 cursor-pointer">
                    @error('logo') <span class="text-red-500 dark:text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    <div wire:loading wire:target="logo" class="text-sm text-gray-500 dark:text-gray-400 mt-2 flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Procesando imagen...
                    </div>
                </div>

                <div class="flex items-center justify-end">
                    <button class="text-white font-bold py-3 px-8 rounded-full transition-all hover:opacity-90 shadow-lg hover:shadow-xl hover:-translate-y-0.5" style="background-color: var(--color-primary); box-shadow: 0 4px 14px 0 var(--color-primary-glow);" type="submit">
                        Guardar Configuración
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
