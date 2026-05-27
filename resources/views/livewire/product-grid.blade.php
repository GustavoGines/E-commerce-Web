<?php

use Livewire\Volt\Component;
use App\Models\Product;
use App\Models\Category;

new class extends Component {
    use Livewire\WithPagination;

    public $selectedCategory = null;
    public $categories = [];

    public function mount()
    {
        $this->categories = Category::has('products')->withCount('products')->get();
    }

    public function setCategory($categoryId)
    {
        $this->selectedCategory = $categoryId;
        $this->resetPage();
    }

    public function with()
    {
        $query = Product::query();

        if ($this->selectedCategory) {
            $query->where('category_id', $this->selectedCategory);
        }

        return [
            'products' => $query->paginate(12),
            'isMayorista' => auth()->check() && auth()->user()->role === 'mayorista'
        ];
    }
}; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-24" x-show="showGrid" x-cloak x-transition:enter="transition ease-out duration-700 delay-200" x-transition:enter-start="opacity-0 translate-y-10" x-transition:enter-end="opacity-100 translate-y-0">
    
    <!-- Category Filters -->
    <div class="mb-12 flex flex-wrap justify-center gap-3">
        <button wire:click="setCategory(null)" class="px-5 py-2.5 rounded-full text-sm font-bold transition-all {{ $selectedCategory === null ? 'text-white shadow-lg shadow-[var(--color-primary-glow)] bg-[var(--color-primary)]' : 'bg-gray-200 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-700' }}">
            Todos los Productos
        </button>
        @foreach($categories as $category)
            <button wire:click="setCategory({{ $category->id }})" class="px-5 py-2.5 rounded-full text-sm font-bold transition-all {{ $selectedCategory == $category->id ? 'text-white shadow-lg shadow-[var(--color-primary-glow)] bg-[var(--color-primary)]' : 'bg-white dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700/50 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 backdrop-blur-sm' }}">
                {{ $category->name }} <span class="ml-1 opacity-60 font-normal text-xs">({{ $category->products_count }})</span>
            </button>
        @endforeach
    </div>

    <!-- Products Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
        @forelse ($products as $index => $product)
            <div wire:key="product-{{ $product->id }}" class="group relative bg-white dark:bg-gray-800/40 backdrop-blur-md border border-gray-200 dark:border-gray-700/50 rounded-2xl overflow-hidden hover:-translate-y-1 transition-all duration-300 card-hover flex flex-col shadow-sm dark:shadow-none hover:shadow-xl" style="animation-delay: {{ $index * 50 }}ms;">
                
                <!-- Image Container -->
                <a href="{{ route('product.detail', $product->slug) }}" wire:navigate class="relative aspect-video bg-gray-100 dark:bg-gray-900/80 overflow-hidden border-b border-gray-200 dark:border-gray-700/50 block">
                    @if($product->image_url)
                        <img src="{{ asset('storage/' . $product->image_url) }}" alt="{{ $product->name }}" class="object-cover w-full h-full transform group-hover:scale-105 transition-transform duration-500">
                    @else
                        <div class="w-full h-full flex items-center justify-center">
                            <svg class="h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    @endif
                    <!-- Stock Overlay -->
                    @if($product->stock > 0)
                        <span class="absolute top-3 right-3 bg-white/80 dark:bg-gray-900/60 text-green-600 dark:text-green-400 border border-green-200 dark:border-green-500/30 text-[10px] uppercase tracking-widest font-bold px-2 py-1 rounded-full backdrop-blur-md shadow-sm">Stock: {{ $product->stock }}</span>
                    @else
                        <span class="absolute top-3 right-3 bg-white/80 dark:bg-gray-900/60 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-500/30 text-[10px] uppercase tracking-widest font-bold px-2 py-1 rounded-full backdrop-blur-md shadow-sm">Agotado</span>
                    @endif
                </a>

                <!-- Content -->
                <div class="p-6 flex flex-col flex-grow justify-between">
                    <div>
                        <a href="{{ route('product.detail', $product->slug) }}" wire:navigate>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 tracking-tight leading-snug group-hover:text-[var(--color-primary)] dark:group-hover:text-white transition-colors">{{ $product->name }}</h3>
                        </a>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 line-clamp-2 leading-relaxed">{{ $product->description }}</p>
                    </div>
                    
                    <div class="mt-6 space-y-3">
                        @if($isMayorista)
                            <!-- Vista Mayorista -->
                            <div class="flex justify-between items-center opacity-60">
                                <span class="text-xs uppercase tracking-wider font-semibold text-gray-500 dark:text-gray-400 line-through">Retail</span>
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400 line-through">${{ number_format($product->retail_price, 2) }}</span>
                            </div>
                            <div class="flex justify-between items-end">
                                <span class="text-xs font-bold uppercase tracking-wider text-[var(--color-primary)]">Mayorista</span>
                                <span class="text-3xl font-black tracking-tighter text-gray-900 dark:text-white">${{ number_format($product->wholesale_price, 2) }}</span>
                            </div>
                        @else
                            <!-- Vista Minorista -->
                            <div class="flex justify-between items-end">
                                <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Precio</span>
                                <span class="text-2xl font-black tracking-tighter text-gray-900 dark:text-white">${{ number_format($product->retail_price, 2) }}</span>
                            </div>
                            <div class="flex justify-between items-center mt-3 pt-3 border-t border-gray-100 dark:border-gray-700/50">
                                <span class="text-[10px] font-bold text-green-700 dark:text-green-400 bg-green-100 dark:bg-green-500/20 border border-green-200 dark:border-green-500/30 px-2 py-1 rounded backdrop-blur-sm uppercase tracking-wider">Mayorista</span>
                                <span class="text-sm font-bold text-green-600 dark:text-green-400">${{ number_format($product->wholesale_price, 2) }}</span>
                            </div>
                        @endif
                        
                        <!-- Add to Cart Button -->
                        <livewire:add-to-cart :product="$product" wire:key="add-cart-{{ $product->id }}" />
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full py-20 text-center">
                <svg class="mx-auto h-16 w-16 text-gray-300 dark:text-gray-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Sin productos</h3>
                <p class="mt-1 text-gray-500 dark:text-gray-400">No hay productos disponibles en esta categoría actualmente.</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-12">
        {{ $products->links() }}
    </div>
</div>
