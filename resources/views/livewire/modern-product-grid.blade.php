<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Url;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;

new class extends Component {
    use Livewire\WithPagination;

    #[Url(as: 'categoria')]
    public $selectedCategory = null;

    #[Url(as: 'marca')]
    public $selectedBrand = null;
    
    #[Url(as: 'q')]
    public $search = '';
    
    #[Url(as: 'min')]
    public $minPrice = null;
    
    #[Url(as: 'max')]
    public $maxPrice = null;

    #[Url(as: 'sort')]
    public $sort = 'default';

    public $categories = [];
    public $brands = [];

    public function mount()
    {
        $this->categories = Category::has('products')->withCount('products')->get();
        $this->brands = Brand::has('products')->withCount('products')->get();
        
        if ($this->selectedCategory && !is_numeric($this->selectedCategory)) {
            $cat = Category::where('name', 'like', '%' . $this->selectedCategory . '%')->first();
            if ($cat) {
                $this->selectedCategory = $cat->id;
            } else {
                $this->selectedCategory = null;
            }
        }
    }

    public function setCategory($categoryId)
    {
        $this->selectedCategory = $categoryId;
        $this->resetPage();
    }
    
    public function setBrand($brandId)
    {
        $this->selectedBrand = $brandId;
        $this->resetPage();
    }
    
    public function setTag($tag)
    {
        $this->search = $tag;
        $this->resetPage();
    }

    public function with()
    {
        $query = Product::query();

        if ($this->selectedCategory) {
            $query->where('category_id', $this->selectedCategory);
        }
        
        if ($this->selectedBrand) {
            $query->where('brand_id', $this->selectedBrand);
        }
        
        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('sku', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }
        
        if ($this->minPrice) {
            $query->where('retail_price', '>=', $this->minPrice);
        }
        
        if ($this->maxPrice) {
            $query->where('retail_price', '<=', $this->maxPrice);
        }

        if ($this->sort === 'price_asc') {
            $query->orderBy('retail_price', 'asc');
        } elseif ($this->sort === 'price_desc') {
            $query->orderBy('retail_price', 'desc');
        } elseif ($this->sort === 'recent') {
            $query->latest();
        }
        
        $recentlyViewedIds = session()->get('recently_viewed', []);
        $recentlyViewedProducts = collect();
        if (count($recentlyViewedIds) > 0) {
            $recentlyViewedProducts = Product::whereIn('id', $recentlyViewedIds)
                                              ->get()
                                              ->sortBy(function($model) use ($recentlyViewedIds) {
                                                  return array_search($model->id, $recentlyViewedIds);
                                              });
        }

        return [
            'products' => $query->paginate(12),
            'popularProducts' => Product::latest()->take(3)->get(),
            'recentlyViewedProducts' => $recentlyViewedProducts
        ];
    }
}; ?>

<div id="catalog" class="w-full relative z-10 py-12 lg:py-16 bg-gray-50" x-data="{ intersecting: false, sidebarOpen: false }" x-intersect.once="intersecting = true">
    
    {{-- Header Banner (Mini-Hero) --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-8 transition-all duration-1000 transform" :class="intersecting ? 'translate-y-0 opacity-100' : 'translate-y-12 opacity-0'">
        <div class="relative w-full h-auto py-12 md:h-64 md:py-0 rounded-3xl overflow-hidden bg-white border border-gray-200 shadow-sm flex items-center group">
            
            {{-- Abstract Shapes --}}
            <div class="absolute top-[-50%] right-[-10%] w-[50%] h-[200%] rounded-full opacity-10 blur-3xl pointer-events-none transition-all duration-1000 group-hover:scale-110" style="background-color: var(--color-primary);"></div>
            <div class="absolute bottom-[-50%] left-[-10%] w-[30%] h-[200%] rounded-full bg-red-200 opacity-50 blur-3xl pointer-events-none"></div>
            
            {{-- Text Content --}}
            <div class="relative z-30 px-8 md:px-16 w-full max-w-2xl">
                <div class="mb-3 inline-flex items-center gap-2 px-3 py-1 rounded-full border border-[var(--color-primary)]/20 bg-red-50 text-[var(--color-primary)] shadow-sm">
                    <span class="text-[10px] font-bold uppercase tracking-widest flex items-center gap-1">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                        Novedad: Precios Mayoristas
                    </span>
                </div>
                
                <h2 class="text-3xl md:text-5xl font-black text-gray-900 tracking-tight mb-3">
                    Llevá más, pagá menos.
                </h2>
                <p class="text-gray-600 text-sm md:text-base max-w-md font-medium leading-relaxed">
                    Si es tu <strong>primera compra</strong>, llevá 10 unidades o más para acceder al precio mayorista.
                    <br>
                    <span class="text-[var(--color-primary)] font-bold">¡Desde tu segunda compra, podés comprar por unidad y mantener el mismo descuento!</span>
                </p>
            </div>
        </div>
    </div>



    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 transition-all duration-1000 delay-300 transform" :class="intersecting ? 'translate-y-0 opacity-100' : 'translate-y-12 opacity-0'">
        
        {{-- Mobile Filter Trigger --}}
        <div class="lg:hidden flex justify-between items-center mb-6">
            <span class="text-gray-900 font-bold text-lg">Catálogo ({{ $products->total() }})</span>
            <button @click="sidebarOpen = true" class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 shadow-sm rounded-xl text-gray-700 text-sm hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                Filtros
            </button>
        </div>

        <div class="flex flex-col lg:flex-row gap-8">
            
            {{-- SIDEBAR --}}
            <aside class="w-full lg:w-1/4 shrink-0 fixed lg:relative inset-y-0 left-0 z-50 lg:z-0 bg-white lg:bg-transparent border-r lg:border-none border-gray-200 transform lg:transform-none transition-transform duration-300 overflow-y-auto lg:overflow-visible p-6 lg:p-0 shadow-2xl lg:shadow-none -translate-x-full lg:translate-x-0"
                   :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
                   x-cloak>
                
                {{-- Close Button Mobile --}}
                <div class="flex justify-between items-center lg:hidden mb-8">
                    <h3 class="text-gray-900 font-bold text-xl tracking-wide">Filtros</h3>
                    <button @click="sidebarOpen = false" class="p-2 text-gray-400 hover:text-gray-900 rounded-full bg-gray-100">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="space-y-10 lg:bg-white lg:p-6 lg:rounded-2xl lg:border lg:border-gray-200 lg:shadow-sm">
                    {{-- Search Input --}}
                    <div>
                        <div class="relative">
                            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar..." class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm text-gray-900 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] placeholder-gray-400 transition-all outline-none">
                            @if($search)
                                <button wire:click="$set('search', '')" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            @else
                                <svg class="w-4 h-4 text-gray-400 absolute right-4 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            @endif
                        </div>
                    </div>

                    {{-- Categories --}}
                    <div>
                        <h4 class="text-gray-900 font-bold text-sm tracking-widest uppercase mb-4 pb-4 border-b border-gray-100">Categorías</h4>
                        <ul class="space-y-3">
                            <li>
                                <button wire:click="setCategory(null)" class="w-full flex items-center justify-between text-sm transition-colors {{ $selectedCategory === null ? 'text-[var(--color-primary)] font-bold' : 'text-gray-600 hover:text-gray-900' }}">
                                    <span>Todas</span>
                                    <span class="text-[10px] bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full font-bold">{{ $products->total() }}</span>
                                </button>
                            </li>
                            @foreach($categories as $category)
                                <li>
                                    <button wire:click="setCategory({{ $category->id }})" class="w-full flex items-center justify-between text-sm transition-colors {{ $selectedCategory == $category->id ? 'text-[var(--color-primary)] font-bold' : 'text-gray-600 hover:text-gray-900' }}">
                                        <span>{{ $category->name }}</span>
                                        <span class="text-[10px] bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full font-bold">{{ $category->products_count }}</span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    {{-- Brands --}}
                    @if(count($brands) > 0)
                    <div>
                        <h4 class="text-gray-900 font-bold text-sm tracking-widest uppercase mb-4 pb-4 border-b border-gray-100">Marcas</h4>
                        <ul class="space-y-3">
                            <li>
                                <button wire:click="setBrand(null)" class="w-full flex items-center justify-between text-sm transition-colors {{ $selectedBrand === null ? 'text-[var(--color-primary)] font-bold' : 'text-gray-600 hover:text-gray-900' }}">
                                    <span>Todas</span>
                                </button>
                            </li>
                            @foreach($brands as $brand)
                                <li>
                                    <button wire:click="setBrand({{ $brand->id }})" class="w-full flex items-center justify-between text-sm transition-colors {{ $selectedBrand == $brand->id ? 'text-[var(--color-primary)] font-bold' : 'text-gray-600 hover:text-gray-900' }}">
                                        <span>{{ $brand->name }}</span>
                                        <span class="text-[10px] bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full font-bold">{{ $brand->products_count }}</span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    {{-- Functional Price Range --}}
                    <div>
                        <h4 class="text-gray-900 font-bold text-sm tracking-widest uppercase mb-4 pb-4 border-b border-gray-100">Rango de Precio</h4>
                        <div class="px-1" x-data="{ minPrice: $wire.entangle('minPrice').live, maxPrice: $wire.entangle('maxPrice').live }">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-1/2 relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-bold">$</span>
                                    <input type="number" x-model.debounce.500ms="minPrice" placeholder="Min" class="w-full bg-gray-50 border border-gray-200 rounded-xl pl-7 pr-2 py-2 text-sm text-gray-900 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] outline-none">
                                </div>
                                <span class="text-gray-400 font-bold">-</span>
                                <div class="w-1/2 relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-bold">$</span>
                                    <input type="number" x-model.debounce.500ms="maxPrice" placeholder="Max" class="w-full bg-gray-50 border border-gray-200 rounded-xl pl-7 pr-2 py-2 text-sm text-gray-900 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] outline-none">
                                </div>
                            </div>
                        </div>
                    </div>



                </div>
            </aside>
            
            {{-- Mobile Sidebar Backdrop --}}
            <div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm z-40 lg:hidden" x-transition.opacity></div>

            {{-- PRODUCTS GRID --}}
            <div class="w-full lg:w-3/4">
                
                {{-- Desktop Top Bar (Sorting/Results) --}}
                <div class="hidden lg:flex justify-between items-center mb-6">
                    <span class="text-gray-500 text-sm font-medium">Mostrando <strong class="text-gray-900">{{ $products->count() }}</strong> de {{ $products->total() }} productos</span>
                    <select wire:model.live="sort" class="bg-white border border-gray-200 text-gray-700 font-semibold text-sm rounded-xl focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] block p-2.5 shadow-sm">
                        <option value="default">Relevancia</option>
                        <option value="price_asc">Precio: Menor a Mayor</option>
                        <option value="price_desc">Precio: Mayor a Menor</option>
                        <option value="recent">Novedades</option>
                    </select>
                </div>

                {{-- The Grid --}}
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3 sm:gap-4">
                    @forelse ($products as $index => $product)
                        <article wire:key="product-{{ $product->id }}"
                                 class="group relative flex flex-col bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm hover:shadow hover:-translate-y-0.5 transition-all duration-300">
                            
                            {{-- Contenedor de la Imagen (Más compacto) --}}
                            <a href="{{ route('product.detail', $product->slug) }}" wire:navigate class="relative block aspect-square bg-gray-50 overflow-hidden p-3 border-b border-gray-100 flex items-center justify-center">
                                @if($product->image_url)
                                    <img src="{{ asset('storage/' . $product->image_url) }}"
                                         alt="{{ $product->name }}"
                                         class="w-full h-full object-contain transition-transform duration-500 group-hover:scale-105 drop-shadow-md mix-blend-multiply"
                                         onerror="this.src='https://images.unsplash.com/photo-1591488320449-011701bb6704?q=80&w=400&auto=format&fit=crop'">
                                @else
                                    <svg class="h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                @endif
                                
                                {{-- Stock Badge --}}
                                @if($product->stock <= 0)
                                    <span class="absolute top-2 right-2 text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-red-100 text-red-700">
                                        Agotado
                                    </span>
                                @endif
                            </a>

                            {{-- Contenido de la Tarjeta (Más compacto) --}}
                            <div class="flex flex-col flex-grow p-3 bg-white">
                                <div class="flex-grow">
                                    @if($product->category)
                                        <span class="text-[9px] font-bold uppercase tracking-widest text-gray-400 mb-1 block truncate">
                                            {{ $product->category->name }}
                                        </span>
                                    @endif
                                    @if($product->sku)
                                        <span class="text-[9px] font-mono font-bold text-[var(--color-primary)] bg-[var(--color-primary)]/10 px-1 py-0.5 rounded inline-block mb-1.5">
                                            SKU: {{ $product->sku }}
                                        </span>
                                    @endif
                                    <a href="{{ route('product.detail', $product->slug) }}" wire:navigate>
                                        <h3 class="text-xs sm:text-sm font-bold text-gray-900 leading-tight hover:text-[var(--color-primary)] transition-colors line-clamp-2" title="{{ $product->name }}">
                                            {{ $product->name }}
                                        </h3>
                                    </a>
                                </div>

                                <div class="mt-2 pt-2 border-t border-gray-100 flex flex-col gap-2">
                                    <div class="flex items-end justify-between">
                                        <div>
                                            <p class="text-lg font-black text-[var(--color-primary)] leading-none">${{ number_format($product->retail_price, 2) }}</p>
                                        </div>
                                    </div>

                                    {{-- Botón de Añadir al Carrito --}}
                                    <div class="w-full">
                                        <livewire:add-to-cart :product="$product" :compact="true" wire:key="add-cart-{{ $product->id }}" />
                                    </div>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="col-span-full py-24 text-center bg-white rounded-2xl border border-gray-200">
                            <svg class="mx-auto h-16 w-16 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <h3 class="text-lg font-bold text-gray-900">No se encontraron productos</h3>
                            <p class="mt-1 text-gray-500 text-sm">Intenta con otra búsqueda o categoría.</p>
                        </div>
                    @endforelse
                </div>

                {{-- Pagination --}}
                <div class="mt-12">
                    {{ $products->links() }}
                </div>
            </div>
        </div>
        
    </div>
</div>
