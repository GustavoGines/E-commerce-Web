<?php

use App\Models\Category;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] class extends Component {
    public $cat_id = null;
    public $cat_name = '';
    public $categories = [];
    public $showModal = false;

    public function mount()
    {
        $this->loadCategories();
    }

    public function loadCategories()
    {
        $this->categories = Category::withCount('products')->get();
    }

    public function createCategory()
    {
        $this->reset('cat_id', 'cat_name');
        $this->showModal = true;
    }

    public function editCategory($id)
    {
        $cat = Category::findOrFail($id);
        $this->cat_id = $cat->id;
        $this->cat_name = $cat->name;
        $this->showModal = true;
    }

    public function saveCategory()
    {
        $this->validate([
            'cat_name' => 'required|string|max:255|unique:categories,name,' . $this->cat_id
        ]);

        Category::updateOrCreate(
            ['id' => $this->cat_id],
            ['name' => $this->cat_name]
        );

        $this->loadCategories();
        $this->showModal = false;
        
        session()->flash('message', 'Categoría guardada exitosamente.');
    }

    public function deleteCategory($id)
    {
        $cat = Category::findOrFail($id);
        if ($cat->products()->count() > 0) {
            session()->flash('error', 'No se puede eliminar la categoría porque tiene productos.');
            return;
        }
        $cat->delete();
        $this->loadCategories();
        session()->flash('message', 'Categoría eliminada exitosamente.');
    }
}
?>

<div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">Gestión de Categorías</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Agrega, edita o elimina las categorías de tu tienda.</p>
        </div>
        <button wire:click="createCategory" class="text-white font-bold py-2.5 px-6 rounded-full shadow-lg transition-all hover:opacity-90 flex items-center space-x-2" style="background-color: var(--color-primary); box-shadow: 0 4px 14px 0 var(--color-primary-glow);">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            <span>Nueva Categoría</span>
        </button>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
            {{ session('message') }}
        </div>
    @endif
    
    @if (session()->has('error'))
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 shadow-xl rounded-2xl overflow-hidden border border-gray-200 dark:border-gray-700">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nombre</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Productos</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($categories as $category)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $category->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-[var(--color-primary)] font-bold">{{ $category->products_count }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-right space-x-3">
                            <button wire:click="editCategory({{ $category->id }})" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">Editar</button>
                            <button wire:click="deleteCategory({{ $category->id }})" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">Eliminar</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No hay categorías registradas.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Editar/Crear Categoría -->
    @if($showModal)
    <div class="fixed z-[60] inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" wire:click="$set('showModal', false)"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                <div class="px-8 pt-8 pb-4">
                    <h3 class="text-2xl leading-6 font-bold text-gray-900 dark:text-white mb-6 tracking-tight">
                        {{ $cat_id ? 'Editar Categoría' : 'Nueva Categoría' }}
                    </h3>
                    <form wire:submit="saveCategory">
                        <div class="mb-6">
                            <label class="block text-gray-700 dark:text-gray-400 text-xs font-bold mb-2 uppercase">Nombre</label>
                            <input wire:model="cat_name" type="text" class="w-full py-3 px-4 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--color-primary)]">
                            @error('cat_name') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div class="flex items-center justify-end bg-gray-50 dark:bg-gray-900/50 -mx-8 -mb-4 px-8 py-5 border-t border-gray-200 dark:border-gray-800">
                            <button type="button" wire:click="$set('showModal', false)" class="text-gray-600 dark:text-gray-400 font-bold py-2.5 px-5 hover:text-gray-900 dark:hover:text-white transition-colors mr-3">Cancelar</button>
                            <button type="submit" class="text-white font-bold py-2.5 px-8 rounded-full shadow-lg hover:-translate-y-0.5 transition-all" style="background-color: var(--color-primary);">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
