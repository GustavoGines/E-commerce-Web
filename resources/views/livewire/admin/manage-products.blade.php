<?php

use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads, WithPagination;

    // WithPagination necesita que $products NO sea propiedad pública
    // La paginación se maneja automáticamente via computed / paginate()
    
    public $product_id = null;
    public $category_id = '';
    public $brand_id = '';
    public $name = '';
    public $sku = '';
    public $description = '';
    public $cost_price = 0;
    public $profit_margin = 0;
    public $wholesale_discount = 0;
    public $wholesale_min_quantity = 3;
    public $retail_price = 0;
    public $wholesale_price = 0;
    public $stock = 0;
    public $image;
    public $current_image_url;
    public $delete_image = false;
    
    public $categories = [];
    public $brands = [];
    
    public $showModal = false;

    public $sortField = 'id';
    public $sortDirection = 'desc';
    public $search = '';
    public $perPage = 50;

    // Selections and Visibility
    public $selectedProducts = [];
    public $selectAll = false;
    
    public $visibleColumns = [
        'image' => true,
        'name' => true,
        'category' => true,
        'brand' => true,
        'cost' => true,
        'retail' => true,
        'wholesale' => true,
        'stock' => true,
    ];

    public function mount()
    {
        $user = auth()->user();
        if ($user && $user->table_preferences) {
            $this->visibleColumns = array_merge($this->visibleColumns, $user->table_preferences);
        }

        $this->loadCategoriesWithCount();
        $this->loadBrandsWithCount();
        $this->loadProducts();
    }

    public function updatedVisibleColumns()
    {
        $user = auth()->user();
        if ($user) {
            $user->table_preferences = $this->visibleColumns;
            $user->save();
        }
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            // Solo selecciona los de la página actual
            $this->selectedProducts = $this->getProductsProperty()->pluck('id')->map(fn($id) => (string)$id)->toArray();
        } else {
            $this->selectedProducts = [];
        }
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage(); // PERF-02: vuelve a página 1 al reordenar
    }

    #[Livewire\Attributes\On('products-imported')]
    public function loadProducts()
    {
        $this->resetPage();
    }

    public function with(): array
    {
        // PERF-01: Ordena desde SQL con JOIN en lugar de cargar todo en PHP.
        $query = Product::query()->with(['category', 'brand']);

        // Filtro de búsqueda por nombre
        if (!empty($this->search)) {
            $query->where('products.name', 'like', '%' . $this->search . '%');
        }

        // Ordenamiento: category y brand usan LEFT JOIN para operar en SQL
        if ($this->sortField === 'category_name') {
            $query->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                  ->orderBy('categories.name', $this->sortDirection)
                  ->select('products.*');
        } elseif ($this->sortField === 'brand_name') {
            $query->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
                  ->orderBy('brands.name', $this->sortDirection)
                  ->select('products.*');
        } else {
            $query->orderBy('products.' . $this->sortField, $this->sortDirection);
        }

        // PERF-02: Paginación — solo carga $perPage registros a la vez
        return [
            'products' => $query->paginate($this->perPage)
        ];
    }

    // --- CRUD DE PRODUCTOS ---
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
        $this->brand_id = $product->brand_id;
        $this->name = $product->name;
        $this->sku = $product->sku;
        $this->description = $product->description;
        $this->cost_price = $product->cost_price;
        $this->profit_margin = $product->profit_margin;
        $this->wholesale_discount = $product->wholesale_discount;
        $this->wholesale_min_quantity = $product->wholesale_min_quantity ?? 3;
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
            'brand_id' => 'nullable|exists:brands,id',
            'cost_price' => 'required|numeric|min:0',
            'profit_margin' => 'required|integer|min:0',
            'wholesale_discount' => 'required|integer|min:0|max:100',
            'wholesale_min_quantity' => 'required|integer|min:1',
            'retail_price' => 'required|numeric|min:0',
            'wholesale_price' => 'required|numeric|min:0|lte:retail_price',
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|image|max:2048',
        ]);

        $data = [
            'name' => $this->name,
            'sku' => empty($this->sku) ? null : $this->sku,
            'category_id' => $this->category_id,
            'brand_id' => $this->brand_id ?: null,
            'description' => $this->description,
            'cost_price' => $this->cost_price,
            'profit_margin' => $this->profit_margin,
            'wholesale_discount' => $this->wholesale_discount,
            'wholesale_min_quantity' => $this->wholesale_min_quantity,
            'retail_price' => $this->retail_price,
            'wholesale_price' => $this->wholesale_price,
            'stock' => $this->stock,
        ];

        if ($this->delete_image && $this->product_id) {
            $p = Product::find($this->product_id);
            if ($p && $p->image_url) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($p->image_url);
            }
            $data['image_url'] = null;
        }

        if ($this->image) {
            $data['image_url'] = $this->image->store('products', 'public');
        }

        Product::updateOrCreate(['id' => $this->product_id], $data);

        $this->showModal = false;
        $this->loadProducts();
        $this->resetFields();
    }

    public function delete($id)
    {
        Product::findOrFail($id)->delete();
        $this->loadProducts();
    }

    public function resetFields()
    {
        $this->product_id = null;
        $this->category_id = '';
        $this->brand_id = '';
        $this->name = '';
        $this->sku = '';
        $this->description = '';
        $this->cost_price = 0;
        $this->profit_margin = 0;
        $this->wholesale_discount = 0;
        $this->wholesale_min_quantity = 3;
        $this->retail_price = 0;
        $this->wholesale_price = 0;
        $this->stock = 0;
        $this->image = null;
        $this->current_image_url = null;
        $this->delete_image = false;
    }

    public function removeImage()
    {
        $this->current_image_url = null;
        $this->image = null;
        $this->delete_image = true;
    }

    public function updatedCostPrice() { $this->calculatePrices(); }
    public function updatedProfitMargin() { $this->calculatePrices(); }
    public function updatedWholesaleDiscount() { $this->calculatePrices(); }

    public function calculatePrices()
    {
        $cost = (float) $this->cost_price;
        $profit = (float) $this->profit_margin;
        $discount = (float) $this->wholesale_discount;

        if ($cost >= 0 && $profit >= 0) {
            $this->retail_price = round($cost * (1 + ($profit / 100)), 2);
            if ($discount >= 0) {
                $this->wholesale_price = round($this->retail_price * (1 - ($discount / 100)), 2);
            }
        }
    }

    // --- ACCIONES MASIVAS SIMPLES ---
    public function deleteSelected()
    {
        if (count($this->selectedProducts) > 0) {
            Product::whereIn('id', $this->selectedProducts)->delete();
            $this->selectedProducts = [];
            $this->selectAll = false;
            $this->loadProducts();
        }
    }

    // --- LÓGICA DE CATEGORÍAS ---
    public $showCategoryListModal = false;
    public $showCategoryEditModal = false;
    public $cat_id = null;
    public $cat_name = '';

    public function openCategoryList()
    {
        $this->loadCategoriesWithCount();
        $this->showCategoryListModal = true;
    }

    public function loadCategoriesWithCount()
    {
        $this->categories = Category::withCount('products')->get();
    }

    public function createCategory()
    {
        $this->cat_id = null;
        $this->cat_name = '';
        $this->showCategoryEditModal = true;
    }

    public function editCategory($id)
    {
        $category = Category::find($id);
        $this->cat_id = $category->id;
        $this->cat_name = $category->name;
        $this->showCategoryEditModal = true;
    }

    public function saveCategory()
    {
        $this->validate(['cat_name' => 'required|string|max:255']);
        $cat = Category::updateOrCreate(
            ['id' => $this->cat_id],
            ['name' => $this->cat_name, 'slug' => \Illuminate\Support\Str::slug($this->cat_name)]
        );
        $this->showCategoryEditModal = false;
        $this->loadCategoriesWithCount();
        if ($this->showModal) { $this->category_id = $cat->id; }
    }

    public function deleteCategory($id)
    {
        Category::findOrFail($id)->delete(); // BAD-02 FIX
        $this->loadCategoriesWithCount();
    }

    // --- LÓGICA DE MARCAS ---
    public $showBrandListModal = false;
    public $showBrandEditModal = false;
    public $b_id = null;
    public $b_name = '';

    public function openBrandList()
    {
        $this->loadBrandsWithCount();
        $this->showBrandListModal = true;
    }

    public function loadBrandsWithCount()
    {
        $this->brands = Brand::withCount('products')->get();
    }

    public function createBrand()
    {
        $this->b_id = null;
        $this->b_name = '';
        $this->showBrandEditModal = true;
    }

    public function editBrand($id)
    {
        $brand = Brand::find($id);
        $this->b_id = $brand->id;
        $this->b_name = $brand->name;
        $this->showBrandEditModal = true;
    }

    public function saveBrand()
    {
        $this->validate(['b_name' => 'required|string|max:255']);
        $brand = Brand::updateOrCreate(
            ['id' => $this->b_id],
            ['name' => $this->b_name, 'slug' => \Illuminate\Support\Str::slug($this->b_name)]
        );
        $this->showBrandEditModal = false;
        $this->loadBrandsWithCount();
        if ($this->showModal) { $this->brand_id = $brand->id; }
    }

    public function deleteBrand($id)
    {
        Brand::findOrFail($id)->delete(); // BAD-02 FIX
        $this->loadBrandsWithCount();
    }

    // --- ACTUALIZADOR MASIVO DE PRECIOS ---
    public $showMassUpdateModal = false;
    public $massTarget = 'selected'; // selected, category, brand
    public $massCategoryId = '';
    public $massBrandId = '';
    public $massType = 'increase'; // increase, decrease
    public $massValueType = 'percent'; // percent, fixed
    public $massValue = 0;
    public $massField = 'cost_price'; // cost_price, retail_price, wholesale_price
    public $massOverride = false;

    public function openMassUpdate()
    {
        $this->showMassUpdateModal = true;
    }

    public function applyMassUpdate()
    {
        $this->validate([
            'massValue' => 'required|numeric|min:0',
        ]);

        $query = Product::query();

        if ($this->massTarget === 'selected') {
            if (empty($this->selectedProducts)) return;
            $query->whereIn('id', $this->selectedProducts);
        } elseif ($this->massTarget === 'category') {
            if (empty($this->massCategoryId)) return;
            $query->where('category_id', $this->massCategoryId);
        } elseif ($this->massTarget === 'brand') {
            if (empty($this->massBrandId)) return;
            $query->where('brand_id', $this->massBrandId);
        }

        $products = $query->get();

        foreach ($products as $product) {
            $currentValue = $product->{$this->massField};
            $adjustment = $this->massValueType === 'percent' 
                ? ($currentValue * ($this->massValue / 100))
                : $this->massValue;

            $newValue = $this->massType === 'increase' 
                ? $currentValue + $adjustment 
                : $currentValue - $adjustment;
            
            $product->{$this->massField} = max(0, $newValue);

            $override = filter_var($this->massOverride, FILTER_VALIDATE_BOOLEAN);

            // Recalculate margins if updating cost and override is FALSE
            if ($this->massField === 'cost_price' && !$override) {
                $cost = (float) $product->cost_price;
                $profit = (float) $product->profit_margin;
                $discount = (float) $product->wholesale_discount;
                
                $product->retail_price = round($cost * (1 + ($profit / 100)), 2);
                $product->wholesale_price = round($product->retail_price * (1 - ($discount / 100)), 2);
            } else {
                // Otherwise, recalculate percentage fields based on new absolute prices to maintain DB integrity.
                if ($product->cost_price > 0) {
                    $product->profit_margin = (int) round((($product->retail_price / $product->cost_price) - 1) * 100);
                } else {
                    $product->profit_margin = 0;
                }

                if ($product->retail_price > 0) {
                    $product->wholesale_discount = (int) round((1 - ($product->wholesale_price / $product->retail_price)) * 100);
                } else {
                    $product->wholesale_discount = 0;
                }
            }

            $product->save();
        }

        $this->showMassUpdateModal = false;
        $this->selectedProducts = [];
        $this->selectAll = false;
        $this->loadProducts();
    }
}; ?>

<div x-data="{ 
    modalOpen: @entangle('showModal').live, 
    catListOpen: @entangle('showCategoryListModal').live, 
    catEditOpen: @entangle('showCategoryEditModal').live,
    brandListOpen: @entangle('showBrandListModal').live,
    brandEditOpen: @entangle('showBrandEditModal').live,
    massUpdateOpen: @entangle('showMassUpdateModal').live,
    colDropdownOpen: false,
    previewImageOpen: false,
    previewImageUrl: ''
}">


    <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
        <!-- Bulk Actions Bar -->
        @if(count($selectedProducts) > 0)
        <div class="bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-200 dark:border-indigo-700/50 rounded-2xl mb-6 p-4 flex items-center justify-between shadow-sm animate-pulse-once">
            <div class="flex items-center text-indigo-800 dark:text-indigo-300 font-medium">
                <span class="bg-indigo-100 dark:bg-indigo-800 px-3 py-1 rounded-full text-indigo-700 dark:text-indigo-300 text-sm font-bold mr-3">{{ count($selectedProducts) }}</span>
                Productos seleccionados
            </div>
            <div class="flex space-x-3">
                <button wire:click="deleteSelected" wire:confirm="¿Estás seguro de eliminar los productos seleccionados?" class="text-xs font-bold bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors shadow-sm">
                    🗑️ Eliminar Seleccionados
                </button>
            </div>
        </div>
        @endif

        <div class="bg-white/80 dark:bg-gray-800/40 backdrop-blur-md border border-gray-200 dark:border-gray-700/50 shadow-xl dark:shadow-2xl dark:[box-shadow:0_10px_30px_-10px_var(--color-primary-glow)] overflow-hidden sm:rounded-3xl p-6 transition-colors duration-300">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <div class="flex items-center gap-3">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 tracking-tight">Catálogo de Productos</h3>
                    {{-- Buscador PERF-02: debounce 400ms para no disparar requests en cada tecla --}}
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
                        <input wire:model.live.debounce.400ms="search"
                               type="search"
                               placeholder="Buscar producto..."
                               id="admin-product-search"
                               class="pl-9 pr-4 py-2 text-sm bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-full text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent transition-all w-52">
                        <div wire:loading wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2">
                            <svg class="animate-spin w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        </div>
                    </div>
                </div>
                <div class="flex space-x-3 items-center">
                    
                    <button type="button" @click="massUpdateOpen = true" class="group flex items-center justify-center p-2.5 rounded-full transition-all hover:bg-gray-100 dark:hover:bg-gray-800 border border-slate-200 dark:border-gray-700 shadow-sm text-slate-700 dark:text-slate-200 font-bold">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <div class="grid grid-cols-[0fr] group-hover:grid-cols-[1fr] transition-all duration-300 ease-in-out">
                            <span class="overflow-hidden whitespace-nowrap text-sm"><span class="pl-2">Precios</span></span>
                        </div>
                    </button>

                    <button type="button" @click="brandListOpen = true" class="group flex items-center justify-center p-2.5 rounded-full transition-all hover:bg-gray-100 dark:hover:bg-gray-800 border border-slate-200 dark:border-gray-700 shadow-sm text-slate-700 dark:text-slate-200 font-bold">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                        <div class="grid grid-cols-[0fr] group-hover:grid-cols-[1fr] transition-all duration-300 ease-in-out">
                            <span class="overflow-hidden whitespace-nowrap text-sm"><span class="pl-2">Marcas</span></span>
                        </div>
                    </button>
                    
                    <button type="button" @click="catListOpen = true" class="group flex items-center justify-center p-2.5 rounded-full transition-all hover:bg-gray-100 dark:hover:bg-gray-800 border border-slate-200 dark:border-gray-700 shadow-sm text-slate-700 dark:text-slate-200 font-bold">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        <div class="grid grid-cols-[0fr] group-hover:grid-cols-[1fr] transition-all duration-300 ease-in-out">
                            <span class="overflow-hidden whitespace-nowrap text-sm"><span class="pl-2">Categorías</span></span>
                        </div>
                    </button>
                    
                    <button type="button" @click="modalOpen = true; $wire.create()" class="group flex items-center justify-center p-2.5 rounded-full transition-all shadow-lg hover:shadow-xl hover:-translate-y-0.5 text-white font-bold" style="background-color: var(--color-primary); box-shadow: 0 4px 14px 0 var(--color-primary-glow);">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        <div class="grid grid-cols-[0fr] group-hover:grid-cols-[1fr] transition-all duration-300 ease-in-out">
                            <span class="overflow-hidden whitespace-nowrap text-sm"><span class="pl-2 pr-1">Nuevo Producto</span></span>
                        </div>
                    </button>

                    <!-- Importar Excel Component -->
                    <livewire:admin.product-import />

                    <!-- Columnas Dropdown (Icon only) -->
                    <div class="relative">
                        <button @click="colDropdownOpen = !colDropdownOpen" @click.away="colDropdownOpen = false" class="group flex items-center justify-center p-2.5 rounded-full transition-all hover:bg-gray-100 dark:hover:bg-gray-800 border border-slate-200 dark:border-gray-700 shadow-sm text-slate-700 dark:text-slate-200 font-bold">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            <div class="grid grid-cols-[0fr] group-hover:grid-cols-[1fr] transition-all duration-300 ease-in-out">
                                <span class="overflow-hidden whitespace-nowrap text-sm"><span class="pl-2">Columnas</span></span>
                            </div>
                        </button>
                        <div x-show="colDropdownOpen" style="display: none;" class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 z-50 p-3">
                            <h4 class="text-xs font-bold text-gray-400 mb-2 uppercase tracking-wider">Mostrar/Ocultar</h4>
                            @foreach($visibleColumns as $key => $val)
                                <label class="flex items-center space-x-2 text-sm text-gray-700 dark:text-gray-300 mb-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 p-1 rounded">
                                    <input type="checkbox" wire:model.live="visibleColumns.{{ $key }}" class="rounded border-gray-300 text-[var(--color-primary)] focus:ring-[var(--color-primary)]">
                                    <span class="capitalize">{{ $key === 'retail' ? 'Precio Lista' : ($key === 'wholesale' ? 'Mayorista' : ($key === 'cost' ? 'Costo' : $key)) }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-700/50 shadow-sm dark:shadow-none transition-colors">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700/50 text-left">
                    <thead class="bg-gray-50 dark:bg-gray-900/50 transition-colors">
                        <tr>
                            <th class="px-6 py-4">
                                <input type="checkbox" wire:model.live="selectAll" class="rounded border-gray-300 text-[var(--color-primary)] focus:ring-[var(--color-primary)]">
                            </th>
                            @if($visibleColumns['image'])
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Imagen</th>
                            @endif
                            
                            @if($visibleColumns['name'])
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-300 select-none" wire:click="sortBy('name')">
                                Nombre @if($sortField === 'name') <span class="inline-block ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span> @endif
                            </th>
                            @endif

                            @if($visibleColumns['category'])
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-300 select-none" wire:click="sortBy('category_name')">
                                Categoría @if($sortField === 'category_name') <span class="inline-block ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span> @endif
                            </th>
                            @endif

                            @if($visibleColumns['brand'])
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-300 select-none" wire:click="sortBy('brand_name')">
                                Marca @if($sortField === 'brand_name') <span class="inline-block ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span> @endif
                            </th>
                            @endif

                            @if($visibleColumns['cost'])
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-300 select-none" wire:click="sortBy('cost_price')">
                                Costo @if($sortField === 'cost_price') <span class="inline-block ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span> @endif
                            </th>
                            @endif

                            @if($visibleColumns['retail'])
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-300 select-none" wire:click="sortBy('retail_price')">
                                Precio Lista @if($sortField === 'retail_price') <span class="inline-block ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span> @endif
                            </th>
                            @endif

                            @if($visibleColumns['wholesale'])
                            <th class="px-6 py-4 text-xs font-bold text-[var(--color-primary)] uppercase tracking-wider cursor-pointer select-none" wire:click="sortBy('wholesale_price')">
                                Mayorista @if($sortField === 'wholesale_price') <span class="inline-block ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span> @endif
                            </th>
                            @endif

                            @if($visibleColumns['stock'])
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-300 select-none" wire:click="sortBy('stock')">
                                Stock @if($sortField === 'stock') <span class="inline-block ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span> @endif
                            </th>
                            @endif

                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-transparent divide-y divide-gray-200 dark:divide-gray-700/50 transition-colors">
                        @foreach($products as $product)
                        <tr @click="modalOpen = true; $wire.edit({{ $product->id }})" class="hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-colors cursor-pointer {{ in_array($product->id, $selectedProducts) ? 'bg-indigo-50/50 dark:bg-indigo-900/10' : '' }}">
                            <td class="px-6 py-4 whitespace-nowrap" @click.stop>
                                <input type="checkbox" value="{{ $product->id }}" wire:model.live="selectedProducts" class="rounded border-gray-300 text-[var(--color-primary)] focus:ring-[var(--color-primary)]">
                            </td>
                            
                            @if($visibleColumns['image'])
                            <td class="px-6 py-4 whitespace-nowrap" @click.stop>
                                @if($product->image_url)
                                    <img @click="previewImageUrl = '{{ asset('storage/' . $product->image_url) }}'; previewImageOpen = true" src="{{ asset('storage/' . $product->image_url) }}" class="h-12 w-12 rounded-lg object-cover border border-gray-200 dark:border-gray-600 shadow-sm cursor-pointer hover:opacity-80 transition-opacity" title="Haz clic para agrandar">
                                @else
                                    <div class="h-12 w-12 rounded-lg bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 flex items-center justify-center text-gray-400 dark:text-gray-500 text-[10px] uppercase font-bold tracking-tighter">Sin img</div>
                                @endif
                            </td>
                            @endif

                            @if($visibleColumns['name'])
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-gray-200">{{ $product->name }}</td>
                            @endif

                            @if($visibleColumns['category'])
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                    {{ $product->category ? $product->category->name : 'Sin categoría' }}
                                </span>
                            </td>
                            @endif

                            @if($visibleColumns['brand'])
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-blue-50 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                    {{ $product->brand ? $product->brand->name : 'N/A' }}
                                </span>
                            </td>
                            @endif

                            @if($visibleColumns['cost'])
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${{ number_format($product->cost_price, 2) }}</td>
                            @endif

                            @if($visibleColumns['retail'])
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${{ number_format($product->retail_price, 2) }}</td>
                            @endif

                            @if($visibleColumns['wholesale'])
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-[var(--color-primary)]">${{ number_format($product->wholesale_price, 2) }}</td>
                            @endif

                            @if($visibleColumns['stock'])
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($product->stock > 0)
                                    <span class="bg-green-100 dark:bg-green-500/20 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-500/30 px-3 py-1 rounded-full text-xs font-bold shadow-sm dark:shadow-none">{{ $product->stock }} un.</span>
                                @else
                                    <span class="bg-red-100 dark:bg-red-500/20 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-500/30 px-3 py-1 rounded-full text-xs font-bold shadow-sm dark:shadow-none">Agotado</span>
                                @endif
                            </td>
                            @endif

                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-right space-x-2" @click.stop>
                                <button @click="modalOpen = true; $wire.edit({{ $product->id }})" type="button" title="Editar" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors p-1.5 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/30">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                </button>
                                <button wire:click="delete({{ $product->id }})" wire:confirm="¿Estás seguro de que deseas eliminar este producto?" title="Eliminar" class="text-red-600 dark:text-red-500 hover:text-red-800 dark:hover:text-red-400 transition-colors p-1.5 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/30">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            {{-- Paginación PERF-02 --}}
            @if($products->hasPages())
            <div class="mt-6 flex items-center justify-between">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Mostrando {{ $products->firstItem() }}–{{ $products->lastItem() }}
                    de <span class="font-bold">{{ $products->total() }}</span> productos
                </p>
                <div class="flex items-center gap-1">
                    {{-- Anterior --}}
                    @if($products->onFirstPage())
                        <span class="px-3 py-1.5 rounded-lg text-sm text-gray-300 dark:text-gray-600 border border-gray-200 dark:border-gray-700 cursor-not-allowed">‹ Ant.</span>
                    @else
                        <button wire:click="previousPage" class="px-3 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">‹ Ant.</button>
                    @endif

                    {{-- Página actual --}}
                    <span class="px-3 py-1.5 rounded-lg text-sm font-bold text-white" style="background-color: var(--color-primary);">
                        {{ $products->currentPage() }} / {{ $products->lastPage() }}
                    </span>

                    {{-- Siguiente --}}
                    @if($products->hasMorePages())
                        <button wire:click="nextPage" class="px-3 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">Sig. ›</button>
                    @else
                        <span class="px-3 py-1.5 rounded-lg text-sm text-gray-300 dark:text-gray-600 border border-gray-200 dark:border-gray-700 cursor-not-allowed">Sig. ›</span>
                    @endif
                </div>
            </div>
            @else
            <p class="mt-4 text-xs text-gray-400 dark:text-gray-600 text-right">
                {{ $products->total() }} {{ $products->total() === 1 ? 'producto' : 'productos' }} en total
            </p>
            @endif
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
            <div x-show="modalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-3xl text-left overflow-hidden shadow-2xl dark:[box-shadow:0_25px_50px_-12px_var(--color-primary-glow)] transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="px-8 pt-8 pb-4">
                    <h3 class="text-2xl leading-6 font-bold text-gray-900 dark:text-white mb-8 tracking-tight">
                        {{ $product_id ? 'Editar Producto' : 'Nuevo Producto' }}
                    </h3>
                    <form wire:submit="save">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-4">
                            <div>
                                <label class="block text-gray-700 dark:text-gray-400 text-xs font-bold mb-2 uppercase tracking-wider">Nombre</label>
                                <input wire:model="name" type="text" class="w-full py-2.5 px-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent transition-colors">
                                @error('name') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-gray-700 dark:text-gray-400 text-xs font-bold mb-2 uppercase tracking-wider">SKU / Código</label>
                                <input wire:model="sku" type="text" class="w-full py-2.5 px-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent transition-colors" placeholder="Ej: RM-304">
                                @error('sku') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <div class="flex justify-between items-center mb-2">
                                    <label class="block text-gray-700 dark:text-gray-400 text-xs font-bold uppercase tracking-wider">Categoría</label>
                                    <button type="button" wire:click="createCategory" class="text-[10px] font-bold text-[var(--color-primary)] hover:underline uppercase tracking-wider">
                                        + Nueva
                                    </button>
                                </div>
                                <select wire:model="category_id" class="w-full py-2.5 px-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent transition-colors">
                                    <option value="">Seleccione una categoría</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                @error('category_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <div class="flex justify-between items-center mb-2">
                                    <label class="block text-gray-700 dark:text-gray-400 text-xs font-bold uppercase tracking-wider">Marca</label>
                                    <button type="button" wire:click="createBrand" class="text-[10px] font-bold text-[var(--color-primary)] hover:underline uppercase tracking-wider">
                                        + Nueva
                                    </button>
                                </div>
                                <select wire:model="brand_id" class="w-full py-2.5 px-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent transition-colors">
                                    <option value="">Sin marca</option>
                                    @foreach($brands as $brand)
                                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                    @endforeach
                                </select>
                                @error('brand_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        
                        <div class="p-5 mb-5 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-200 dark:border-slate-700/60 shadow-sm">
                            <h4 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">Calculadora Inteligente de Precios</h4>
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-gray-700 dark:text-gray-400 text-xs font-bold mb-2 uppercase tracking-wider">Costo ($)</label>
                                    <input wire:model.live.debounce.300ms="cost_price" type="number" step="0.01" class="w-full py-2.5 px-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--color-primary)]">
                                    @error('cost_price') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-gray-700 dark:text-gray-400 text-xs font-bold mb-2 uppercase tracking-wider">Ganancia Minor. (%)</label>
                                    <input wire:model.live.debounce.300ms="profit_margin" type="number" class="w-full py-2.5 px-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--color-primary)]">
                                    @error('profit_margin') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-[var(--color-primary)] text-xs font-bold mb-2 uppercase tracking-wider">Desc. Mayorista (%)</label>
                                    <input wire:model.live.debounce.300ms="wholesale_discount" type="number" class="w-full py-2.5 px-3 bg-white dark:bg-gray-900 border border-[var(--color-primary)]/40 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--color-primary)]" placeholder="Ej: 20">
                                    @error('wholesale_discount') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-[var(--color-primary)] text-xs font-bold mb-2 uppercase tracking-wider">Llevando (Cant.)</label>
                                    <input wire:model="wholesale_min_quantity" type="number" min="1" class="w-full py-2.5 px-3 bg-white dark:bg-gray-900 border border-[var(--color-primary)]/40 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--color-primary)]" placeholder="Ej: 3">
                                    @error('wholesale_min_quantity') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-gray-700 dark:text-gray-400 text-xs font-bold mb-2 uppercase tracking-wider">Precio Final Lista</label>
                                    <input wire:model="retail_price" type="number" step="0.01" class="w-full py-3 px-4 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white font-bold focus:ring-2 focus:ring-[var(--color-primary)]">
                                    @error('retail_price') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-[var(--color-primary)] text-xs font-bold mb-2 uppercase tracking-wider">Precio Final Mayorista</label>
                                    <input wire:model="wholesale_price" type="number" step="0.01" class="w-full py-3 px-4 bg-blue-50/50 dark:bg-gray-900 border border-[var(--color-primary)]/40 rounded-xl text-[var(--color-primary)] font-bold focus:ring-2 focus:ring-[var(--color-primary)]">
                                    @error('wholesale_price') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
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
                                <div class="mb-3 flex items-start space-x-4">
                                    <img src="{{ asset('storage/' . $current_image_url) }}" alt="Imagen actual" class="h-20 w-20 object-cover rounded-xl border border-gray-300 dark:border-gray-700 shadow-sm">
                                    <button type="button" wire:click="removeImage" class="text-xs font-bold text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 flex items-center p-1.5 rounded hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        Eliminar Imagen
                                    </button>
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
    
    <!-- Modal Listado Categorías -->
    <div x-show="catListOpen" class="fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" x-cloak>
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
            <div x-show="catListOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900/40 dark:bg-[#0b0f19]/80 backdrop-blur-sm transition-opacity" @click="catListOpen = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div x-show="catListOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <div class="px-8 pt-8 pb-4">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-2xl leading-6 font-bold text-gray-900 dark:text-white tracking-tight">Categorías</h3>
                        <button wire:click="createCategory" class="text-sm text-white font-bold py-2 px-4 rounded-full transition-all hover:opacity-90 shadow-md" style="background-color: var(--color-primary);">+ Nueva</button>
                    </div>
                    
                    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700/50 mb-4 max-h-[60vh] overflow-y-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700/50 text-left">
                            <thead class="bg-gray-50 dark:bg-gray-900/50 sticky top-0">
                                <tr>
                                    <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase">Nombre</th>
                                    <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase text-center">Productos</th>
                                    <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700/50">
                                @forelse($categories as $category)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                    <td class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-gray-200">{{ $category->name }}</td>
                                    <td class="px-4 py-3 text-sm text-center text-[var(--color-primary)] font-bold">{{ $category->products_count ?? $category->products()->count() }}</td>
                                    <td class="px-4 py-3 text-sm font-medium text-right space-x-3">
                                        <button wire:click="editCategory({{ $category->id }})" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">Editar</button>
                                        <button wire:click="deleteCategory({{ $category->id }})" class="text-red-600 hover:text-red-800 dark:text-red-400">Eliminar</button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">No hay categorías registradas.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="flex items-center justify-end mt-6 -mx-8 -mb-4 px-8 py-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-800">
                        <button type="button" @click="catListOpen = false" class="text-gray-600 dark:text-gray-400 font-bold py-2 px-5 hover:text-gray-900 dark:hover:text-white transition-colors">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar/Crear Categoría -->
    <div x-show="catEditOpen" class="fixed z-[60] inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" x-cloak>
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
            <div x-show="catEditOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900/60 dark:bg-[#0b0f19]/90 backdrop-blur-md transition-opacity" @click="catEditOpen = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div x-show="catEditOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
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
                            <button type="button" @click="catEditOpen = false" class="text-gray-600 dark:text-gray-400 font-bold py-2.5 px-5 hover:text-gray-900 dark:hover:text-white transition-colors mr-3">Cancelar</button>
                            <button type="submit" class="text-white font-bold py-2.5 px-8 rounded-full shadow-lg hover:-translate-y-0.5 transition-all" style="background-color: var(--color-primary);">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Listado Marcas -->
    <div x-show="brandListOpen" class="fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" x-cloak>
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
            <div x-show="brandListOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900/40 dark:bg-[#0b0f19]/80 backdrop-blur-sm transition-opacity" @click="brandListOpen = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div x-show="brandListOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <div class="px-8 pt-8 pb-4">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-2xl leading-6 font-bold text-gray-900 dark:text-white tracking-tight">Marcas</h3>
                        <button wire:click="createBrand" class="text-sm text-white font-bold py-2 px-4 rounded-full transition-all hover:opacity-90 shadow-md" style="background-color: var(--color-primary);">+ Nueva</button>
                    </div>
                    
                    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700/50 mb-4 max-h-[60vh] overflow-y-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700/50 text-left">
                            <thead class="bg-gray-50 dark:bg-gray-900/50 sticky top-0">
                                <tr>
                                    <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase">Nombre</th>
                                    <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase text-center">Productos</th>
                                    <th class="px-4 py-3 text-xs font-bold text-gray-500 uppercase text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700/50">
                                @forelse($brands as $brand)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                    <td class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-gray-200">{{ $brand->name }}</td>
                                    <td class="px-4 py-3 text-sm text-center text-[var(--color-primary)] font-bold">{{ $brand->products_count ?? $brand->products()->count() }}</td>
                                    <td class="px-4 py-3 text-sm font-medium text-right space-x-3">
                                        <button wire:click="editBrand({{ $brand->id }})" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">Editar</button>
                                        <button wire:click="deleteBrand({{ $brand->id }})" class="text-red-600 hover:text-red-800 dark:text-red-400">Eliminar</button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">No hay marcas registradas.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="flex items-center justify-end mt-6 -mx-8 -mb-4 px-8 py-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-800">
                        <button type="button" @click="brandListOpen = false" class="text-gray-600 dark:text-gray-400 font-bold py-2 px-5 hover:text-gray-900 dark:hover:text-white transition-colors">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar/Crear Marca -->
    <div x-show="brandEditOpen" class="fixed z-[60] inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" x-cloak>
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
            <div x-show="brandEditOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900/60 dark:bg-[#0b0f19]/90 backdrop-blur-md transition-opacity" @click="brandEditOpen = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div x-show="brandEditOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                <div class="px-8 pt-8 pb-4">
                    <h3 class="text-2xl leading-6 font-bold text-gray-900 dark:text-white mb-6 tracking-tight">
                        {{ $b_id ? 'Editar Marca' : 'Nueva Marca' }}
                    </h3>
                    <form wire:submit="saveBrand">
                        <div class="mb-6">
                            <label class="block text-gray-700 dark:text-gray-400 text-xs font-bold mb-2 uppercase">Nombre</label>
                            <input wire:model="b_name" type="text" class="w-full py-3 px-4 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--color-primary)]">
                            @error('b_name') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div class="flex items-center justify-end bg-gray-50 dark:bg-gray-900/50 -mx-8 -mb-4 px-8 py-5 border-t border-gray-200 dark:border-gray-800">
                            <button type="button" @click="brandEditOpen = false" class="text-gray-600 dark:text-gray-400 font-bold py-2.5 px-5 hover:text-gray-900 dark:hover:text-white transition-colors mr-3">Cancelar</button>
                            <button type="submit" class="text-white font-bold py-2.5 px-8 rounded-full shadow-lg hover:-translate-y-0.5 transition-all" style="background-color: var(--color-primary);">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Actualización Masiva de Precios -->
    <div x-show="massUpdateOpen" class="fixed z-[60] inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" x-cloak>
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
            <div x-show="massUpdateOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900/60 dark:bg-[#0b0f19]/90 backdrop-blur-md transition-opacity" @click="massUpdateOpen = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div x-show="massUpdateOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full">
                <div class="px-8 pt-8 pb-4">
                    <h3 class="text-2xl leading-6 font-bold text-gray-900 dark:text-white mb-6 tracking-tight flex items-center">
                        💸 Actualización Masiva de Precios
                    </h3>
                    
                    <form wire:submit="applyMassUpdate">
                        
                        <div class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 p-4 rounded-xl mb-6">
                            <label class="block text-indigo-800 dark:text-indigo-300 text-xs font-bold mb-2 uppercase">Aplicar a:</label>
                            <select wire:model.live="massTarget" class="w-full py-2.5 px-3 bg-white dark:bg-gray-800 border border-indigo-200 dark:border-indigo-700 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 mb-3">
                                <option value="selected">Productos Seleccionados ({{ count($selectedProducts) }})</option>
                                <option value="category">Todos los productos de una Categoría</option>
                                <option value="brand">Todos los productos de una Marca</option>
                            </select>

                            @if($massTarget === 'category')
                                <select wire:model="massCategoryId" class="w-full py-2.5 px-3 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                                    <option value="">Seleccione Categoría...</option>
                                    @foreach($categories as $cat) <option value="{{ $cat->id }}">{{ $cat->name }}</option> @endforeach
                                </select>
                            @endif

                            @if($massTarget === 'brand')
                                <select wire:model="massBrandId" class="w-full py-2.5 px-3 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                                    <option value="">Seleccione Marca...</option>
                                    @foreach($brands as $brand) <option value="{{ $brand->id }}">{{ $brand->name }}</option> @endforeach
                                </select>
                            @endif
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-gray-700 dark:text-gray-400 text-xs font-bold mb-2 uppercase">Acción</label>
                                <select wire:model="massType" class="w-full py-3 px-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white">
                                    <option value="increase">Aumento (+)</option>
                                    <option value="decrease">Descuento (-)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-700 dark:text-gray-400 text-xs font-bold mb-2 uppercase">Tipo de valor</label>
                                <select wire:model="massValueType" class="w-full py-3 px-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white">
                                    <option value="percent">Porcentaje (%)</option>
                                    <option value="fixed">Monto Fijo ($)</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div>
                                <label class="block text-[var(--color-primary)] text-xs font-bold mb-2 uppercase">Valor (Número)</label>
                                <input wire:model="massValue" type="number" step="0.01" min="0" class="w-full py-3 px-4 bg-blue-50/50 dark:bg-gray-900 border border-[var(--color-primary)]/40 rounded-xl text-gray-900 dark:text-white font-bold focus:ring-2 focus:ring-[var(--color-primary)]">
                                @error('massValue') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-gray-700 dark:text-gray-400 text-xs font-bold mb-2 uppercase">Campo a Modificar</label>
                                <select wire:model="massField" class="w-full py-3 px-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white font-bold">
                                    <option value="cost_price">Precio de Costo</option>
                                    <option value="retail_price">Precio Lista</option>
                                    <option value="wholesale_price">Precio Mayorista</option>
                                </select>
                            </div>
                        </div>

                        <div class="bg-gray-100 dark:bg-gray-800 p-4 rounded-xl mb-6 border border-gray-200 dark:border-gray-700">
                            <label class="flex items-start space-x-3 cursor-pointer">
                                <input type="checkbox" wire:model="massOverride" class="mt-1 rounded border-gray-300 text-red-600 focus:ring-red-500">
                                <div>
                                    <span class="block text-sm font-bold text-gray-900 dark:text-gray-100">Forzar actualización absoluta</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400 mt-1">Si se desmarca (por defecto), al aumentar el "Costo", los precios de venta se recalcularán automáticamente para mantener los márgenes de ganancia. Si se marca, NO se recalcularán y solo se afectará el campo seleccionado.</span>
                                </div>
                            </label>
                        </div>

                        <div class="flex items-center justify-end bg-gray-50 dark:bg-gray-900/50 -mx-8 -mb-4 px-8 py-5 border-t border-gray-200 dark:border-gray-800">
                            <button type="button" @click="massUpdateOpen = false" class="text-gray-600 dark:text-gray-400 font-bold py-2.5 px-5 hover:text-gray-900 dark:hover:text-white transition-colors mr-3">Cancelar</button>
                            <button type="submit" class="text-white font-bold py-2.5 px-8 rounded-full shadow-lg bg-indigo-600 hover:bg-indigo-700 transition-all">🚀 Aplicar Actualización</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Preview Modal -->
    <div x-show="previewImageOpen" class="fixed z-[60] inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" x-cloak>
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
            <div x-show="previewImageOpen" 
                 x-transition:enter="ease-out duration-300" 
                 x-transition:enter-start="opacity-0" 
                 x-transition:enter-end="opacity-100" 
                 x-transition:leave="ease-in duration-200" 
                 x-transition:leave-start="opacity-100" 
                 x-transition:leave-end="opacity-0" 
                 class="fixed inset-0 bg-black/80 backdrop-blur-sm transition-opacity" 
                 @click="previewImageOpen = false" aria-hidden="true"></div>

            <div x-show="previewImageOpen" 
                 x-transition:enter="ease-out duration-300" 
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                 x-transition:leave="ease-in duration-200" 
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                 class="inline-block align-bottom rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle max-w-4xl w-full">
                <div class="relative">
                    <button @click="previewImageOpen = false" class="absolute top-4 right-4 bg-black/50 hover:bg-black/80 text-white rounded-full p-2 transition-colors z-10 backdrop-blur-md">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                    <img :src="previewImageUrl" class="w-full h-auto max-h-[85vh] object-contain bg-transparent mx-auto">
                </div>
            </div>
        </div>
    </div>
</div>
