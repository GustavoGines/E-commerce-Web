<?php

use App\Models\Product;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public $products;
    
    public $product_id = null;
    public $category_id = '';
    public $name = '';
    public $description = '';
    public $retail_price = 0;
    public $wholesale_price = 0;
    public $stock = 0;
    public $image;
    public $current_image_url;
    
    public $categories = [];
    
    public $showModal = false;

    public function mount()
    {
        $this->loadProducts();
        $this->categories = \App\Models\Category::all();
    }

    public function loadProducts()
    {
        $this->products = Product::all();
    }

    public function create()
    {
        $this->resetFields();
        $this->showModal = true;
    }

    public function edit($id)
    {
        $product = Product::find($id);
        $this->product_id = $product->id;
        $this->category_id = $product->category_id;
        $this->name = $product->name;
        $this->description = $product->description;
        $this->retail_price = $product->retail_price;
        $this->wholesale_price = $product->wholesale_price;
        $this->stock = $product->stock;
        $this->current_image_url = $product->image_url;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'retail_price' => 'required|numeric|min:0',
            'wholesale_price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|image|max:2048',
        ]);

        $data = [
            'name' => $this->name,
            'category_id' => $this->category_id,
            'description' => $this->description,
            'retail_price' => $this->retail_price,
            'wholesale_price' => $this->wholesale_price,
            'stock' => $this->stock,
        ];

        if ($this->image) {
            $data['image_url'] = $this->image->store('products', 'public');
        }

        Product::updateOrCreate(
            ['id' => $this->product_id],
            $data
        );

        $this->showModal = false;
        $this->loadProducts();
        $this->resetFields();
    }

    public function delete($id)
    {
        Product::find($id)->delete();
        $this->loadProducts();
    }

    public function resetFields()
    {
        $this->product_id = null;
        $this->category_id = '';
        $this->name = '';
        $this->description = '';
        $this->retail_price = 0;
        $this->wholesale_price = 0;
        $this->stock = 0;
        $this->image = null;
        $this->current_image_url = null;
    }
}; ?>

<div x-data="{ modalOpen: @entangle('showModal') }">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-900 dark:text-gray-100 leading-tight">
            {{ __('Gestión de Productos') }}
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
        <div class="bg-white/80 dark:bg-gray-800/40 backdrop-blur-md border border-gray-200 dark:border-gray-700/50 shadow-xl dark:shadow-2xl overflow-hidden sm:rounded-3xl p-6 transition-colors duration-300" :style="darkMode ? 'box-shadow: 0 10px 30px -10px var(--color-primary-glow);' : ''">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 tracking-tight">Catálogo de Hardware</h3>
                <button wire:click="create" class="text-white font-bold py-2.5 px-6 rounded-full transition-all hover:opacity-90 shadow-lg hover:shadow-xl hover:-translate-y-0.5" style="background-color: var(--color-primary); box-shadow: 0 4px 14px 0 var(--color-primary-glow);">
                    + Nuevo Producto
                </button>
            </div>

            <div class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-700/50 shadow-sm dark:shadow-none transition-colors">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700/50 text-left">
                    <thead class="bg-gray-50 dark:bg-gray-900/50 transition-colors">
                        <tr>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Imagen</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nombre</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Precio Lista</th>
                            <th class="px-6 py-4 text-xs font-bold text-[var(--color-primary)] uppercase tracking-wider">Mayorista</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Stock</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-transparent divide-y divide-gray-200 dark:divide-gray-700/50 transition-colors">
                        @foreach($products as $product)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($product->image_url)
                                    <img src="{{ asset('storage/' . $product->image_url) }}" class="h-12 w-12 rounded-lg object-cover border border-gray-200 dark:border-gray-600 shadow-sm">
                                @else
                                    <div class="h-12 w-12 rounded-lg bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 flex items-center justify-center text-gray-400 dark:text-gray-500 text-[10px] uppercase font-bold tracking-tighter">Sin img</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-gray-200">{{ $product->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${{ number_format($product->retail_price, 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-[var(--color-primary)]">${{ number_format($product->wholesale_price, 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($product->stock > 0)
                                    <span class="bg-green-100 dark:bg-green-500/20 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-500/30 px-3 py-1 rounded-full text-xs font-bold shadow-sm dark:shadow-none">{{ $product->stock }} un.</span>
                                @else
                                    <span class="bg-red-100 dark:bg-red-500/20 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-500/30 px-3 py-1 rounded-full text-xs font-bold shadow-sm dark:shadow-none">Agotado</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-right space-x-3">
                                <button wire:click="edit({{ $product->id }})" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors">Editar</button>
                                <button wire:click="delete({{ $product->id }})" class="text-red-600 dark:text-red-500 hover:text-red-800 dark:hover:text-red-400 transition-colors">Eliminar</button>
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
                 class="inline-block align-bottom bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full" :style="darkMode ? 'box-shadow: 0 25px 50px -12px var(--color-primary-glow);' : ''">
                <div class="px-8 pt-8 pb-4">
                    <h3 class="text-2xl leading-6 font-bold text-gray-900 dark:text-white mb-8 tracking-tight">
                        {{ $product_id ? 'Editar Producto' : 'Nuevo Producto' }}
                    </h3>
                    <form wire:submit="save">
                        <div class="grid grid-cols-2 gap-5 mb-5">
                            <div>
                                <label class="block text-gray-700 dark:text-gray-400 text-xs font-bold mb-2 uppercase tracking-wider">Nombre</label>
                                <input wire:model="name" type="text" class="w-full py-3 px-4 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent transition-all shadow-sm dark:shadow-none">
                                @error('name') <span class="text-red-500 dark:text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-gray-700 dark:text-gray-400 text-xs font-bold mb-2 uppercase tracking-wider">Categoría</label>
                                <select wire:model="category_id" class="w-full py-3 px-4 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent transition-all shadow-sm dark:shadow-none">
                                    <option value="">Seleccione una categoría</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                @error('category_id') <span class="text-red-500 dark:text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-5 mb-5">
                            <div>
                                <label class="block text-gray-700 dark:text-gray-400 text-xs font-bold mb-2 uppercase tracking-wider">Precio Lista</label>
                                <input wire:model="retail_price" type="number" step="0.01" class="w-full py-3 px-4 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent transition-all shadow-sm dark:shadow-none">
                                @error('retail_price') <span class="text-red-500 dark:text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-[var(--color-primary)] text-xs font-bold mb-2 uppercase tracking-wider">Precio Mayorista</label>
                                <input wire:model="wholesale_price" type="number" step="0.01" class="w-full py-3 px-4 bg-blue-50/50 dark:bg-gray-800 border border-[var(--color-primary)]/40 dark:border-[var(--color-primary)]/50 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] transition-all shadow-sm dark:shadow-[0_0_10px_var(--color-primary-glow)]">
                                @error('wholesale_price') <span class="text-red-500 dark:text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="mb-5">
                            <label class="block text-gray-700 dark:text-gray-400 text-xs font-bold mb-2 uppercase tracking-wider">Stock</label>
                            <input wire:model="stock" type="number" class="w-full py-3 px-4 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent transition-all shadow-sm dark:shadow-none">
                            @error('stock') <span class="text-red-500 dark:text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-8">
                            <label class="block text-gray-700 dark:text-gray-400 text-xs font-bold mb-2 uppercase tracking-wider">Imagen del Producto</label>
                            @if($current_image_url)
                                <div class="mb-3">
                                    <img src="{{ asset('storage/' . $current_image_url) }}" alt="Imagen actual" class="h-20 w-20 object-cover rounded-xl border border-gray-300 dark:border-gray-700 shadow-sm">
                                </div>
                            @endif
                            <input wire:model="image" type="file" accept="image/*" class="w-full py-2.5 px-4 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] transition-all file:mr-4 file:py-1.5 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-gray-200 dark:file:bg-gray-700 file:text-gray-700 dark:file:text-gray-300 hover:file:bg-gray-300 dark:hover:file:bg-gray-600 shadow-sm dark:shadow-none cursor-pointer">
                            @error('image') <span class="text-red-500 dark:text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                            <div wire:loading wire:target="image" class="text-sm text-[var(--color-primary)] mt-2 font-medium">Cargando imagen...</div>
                        </div>
                        <div class="flex items-center justify-end bg-gray-50 dark:bg-gray-900/50 -mx-8 -mb-4 px-8 py-5 border-t border-gray-200 dark:border-gray-800">
                            <button type="button" wire:click="$set('showModal', false)" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white font-bold py-2.5 px-5 rounded-full transition-colors mr-3">Cancelar</button>
                            <button type="submit" class="text-white font-bold py-2.5 px-8 rounded-full shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all duration-300" style="background-color: var(--color-primary); box-shadow: 0 4px 14px 0 var(--color-primary-glow);">Guardar Producto</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
