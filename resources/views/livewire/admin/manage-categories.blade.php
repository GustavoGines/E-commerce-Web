<?php

use App\Models\Category;
use Illuminate\Support\Str;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] class extends Component {
    public $categories;
    public $category_id = null;
    public $name = '';
    public $showModal = false;

    public function mount()
    {
        $this->loadCategories();
    }

    public function loadCategories()
    {
        $this->categories = Category::withCount('products')->get();
    }

    public function create()
    {
        $this->resetFields();
        $this->showModal = true;
    }

    public function edit($id)
    {
        $category = Category::find($id);
        $this->category_id = $category->id;
        $this->name = $category->name;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
        ]);

        Category::updateOrCreate(
            ['id' => $this->category_id],
            [
                'name' => $this->name,
                'slug' => Str::slug($this->name)
            ]
        );

        $this->showModal = false;
        $this->loadCategories();
        $this->resetFields();
    }

    public function delete($id)
    {
        Category::find($id)->delete();
        $this->loadCategories();
    }

    public function resetFields()
    {
        $this->category_id = null;
        $this->name = '';
    }
}; ?>

<div x-data="{ modalOpen: @entangle('showModal') }">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-900 dark:text-gray-100 leading-tight">
            {{ __('Gestión de Categorías') }}
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
        <div class="bg-white/80 dark:bg-gray-800/40 backdrop-blur-md border border-gray-200 dark:border-gray-700/50 shadow-xl dark:shadow-2xl overflow-hidden sm:rounded-3xl p-6 transition-colors duration-300" :style="darkMode ? 'box-shadow: 0 10px 30px -10px var(--color-primary-glow);' : ''">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 tracking-tight">Categorías de Hardware</h3>
                <button wire:click="create" class="text-white font-bold py-2.5 px-6 rounded-full transition-all hover:opacity-90 shadow-lg hover:shadow-xl hover:-translate-y-0.5" style="background-color: var(--color-primary); box-shadow: 0 4px 14px 0 var(--color-primary-glow);">
                    + Nueva Categoría
                </button>
            </div>

            <div class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-700/50 shadow-sm dark:shadow-none transition-colors">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700/50 text-left">
                    <thead class="bg-gray-50 dark:bg-gray-900/50 transition-colors">
                        <tr>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nombre</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">URL Slug</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Productos Totales</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-transparent divide-y divide-gray-200 dark:divide-gray-700/50 transition-colors">
                        @foreach($categories as $category)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">#{{ $category->id }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-gray-200">{{ $category->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400 dark:text-gray-500">{{ $category->slug }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-[var(--color-primary)]">{{ $category->products_count }} un.</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-right space-x-3">
                                <button wire:click="edit({{ $category->id }})" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors">Editar</button>
                                <button wire:click="delete({{ $category->id }})" class="text-red-600 dark:text-red-500 hover:text-red-800 dark:hover:text-red-400 transition-colors">Eliminar</button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal with Alpine Transitions -->
    <div x-show="modalOpen" class="fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" x-cloak>
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
            <!-- Overlay -->
            <div x-show="modalOpen" 
                 x-transition:enter="ease-out duration-300" 
                 x-transition:enter-start="opacity-0" 
                 x-transition:enter-end="opacity-100" 
                 x-transition:leave="ease-in duration-200" 
                 x-transition:leave-start="opacity-100" 
                 x-transition:leave-end="opacity-0" 
                 class="fixed inset-0 bg-gray-900/40 dark:bg-[#0b0f19]/80 backdrop-blur-sm transition-opacity" aria-hidden="true" wire:click="$set('showModal', false)"></div>
            
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <!-- Modal Content -->
            <div x-show="modalOpen" 
                 x-transition:enter="ease-out duration-300" 
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                 x-transition:leave="ease-in duration-200" 
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                 class="inline-block align-bottom bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full" :style="darkMode ? 'box-shadow: 0 25px 50px -12px var(--color-primary-glow);' : ''">
                <div class="px-8 pt-8 pb-4">
                    <h3 class="text-2xl leading-6 font-bold text-gray-900 dark:text-white mb-8 tracking-tight">
                        {{ $category_id ? 'Editar Categoría' : 'Nueva Categoría' }}
                    </h3>
                    <form wire:submit="save">
                        <div class="mb-8">
                            <label class="block text-gray-700 dark:text-gray-400 text-xs font-bold mb-2 uppercase tracking-wider">Nombre</label>
                            <input wire:model="name" type="text" class="w-full py-3 px-4 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent transition-all shadow-sm dark:shadow-none" placeholder="Ej: Procesadores">
                            @error('name') <span class="text-red-500 dark:text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div class="flex items-center justify-end bg-gray-50 dark:bg-gray-900/50 -mx-8 -mb-4 px-8 py-5 border-t border-gray-200 dark:border-gray-800">
                            <button type="button" wire:click="$set('showModal', false)" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white font-bold py-2.5 px-5 rounded-full transition-colors mr-3">Cancelar</button>
                            <button type="submit" class="text-white font-bold py-2.5 px-8 rounded-full shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all duration-300" style="background-color: var(--color-primary); box-shadow: 0 4px 14px 0 var(--color-primary-glow);">Guardar Categoría</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
