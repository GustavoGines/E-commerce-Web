<?php

use App\Models\StoreSetting;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public $store_name = '';
    public $primary_color = '#3b82f6';
    public $logo;
    public $current_logo_url;

    public function mount()
    {
        $settings = StoreSetting::first();
        if ($settings) {
            $this->store_name = $settings->store_name;
            $this->primary_color = $settings->primary_color;
            $this->current_logo_url = $settings->logo_url;
        }
    }

    public function save()
    {
        $this->validate([
            'store_name' => 'required|string|max:255',
            'primary_color' => 'required|string|max:7',
            'logo' => 'nullable|image|max:2048', // max 2MB
        ]);

        $settings = StoreSetting::first() ?? new StoreSetting();
        $settings->store_name = $this->store_name;
        $settings->primary_color = $this->primary_color;

        if ($this->logo) {
            // Guarda en storage/app/public/logos
            $path = $this->logo->store('logos', 'public');
            $settings->logo_url = $path;
            $this->current_logo_url = $path;
        }

        $settings->save();

        session()->flash('message', 'Configuración guardada exitosamente.');
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-900 dark:text-gray-100 leading-tight">
            {{ __('Configuración de la Tienda') }}
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
        <div class="bg-white/80 dark:bg-gray-800/40 backdrop-blur-md border border-gray-200 dark:border-gray-700/50 shadow-xl dark:shadow-2xl overflow-hidden sm:rounded-3xl p-8 transition-colors duration-300" :style="darkMode ? 'box-shadow: 0 10px 30px -10px var(--color-primary-glow);' : ''">
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
                    <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 uppercase tracking-wider transition-colors" for="primary_color">
                        Color Primario
                    </label>
                    <input wire:model="primary_color" id="primary_color" type="color" class="w-full h-14 p-1 bg-gray-50 dark:bg-gray-900/80 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-gray-100 leading-tight focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent transition-all cursor-pointer shadow-sm dark:shadow-none">
                    @error('primary_color') <span class="text-red-500 dark:text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="mb-8">
                    <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 uppercase tracking-wider transition-colors" for="logo">
                        Logo de la Tienda
                    </label>
                    @if($current_logo_url)
                        <div class="mb-3">
                            <img src="{{ asset('storage/' . $current_logo_url) }}" alt="Logo Actual" class="h-16 object-contain rounded-xl bg-gray-100 dark:bg-gray-900/80 p-2 border border-gray-300 dark:border-gray-700 transition-colors">
                        </div>
                    @endif
                    <input wire:model="logo" id="logo" type="file" accept="image/*" class="w-full py-3 px-4 bg-gray-50 dark:bg-gray-900/80 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-700 dark:text-gray-300 leading-tight focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] transition-all shadow-sm dark:shadow-none file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-gray-200 dark:file:bg-gray-800 file:text-gray-700 dark:file:text-gray-300 hover:file:bg-gray-300 dark:hover:file:bg-gray-700 cursor-pointer">
                    @error('logo') <span class="text-red-500 dark:text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    <div wire:loading wire:target="logo" class="text-sm text-[var(--color-primary)] mt-2 font-medium">Cargando imagen...</div>
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
