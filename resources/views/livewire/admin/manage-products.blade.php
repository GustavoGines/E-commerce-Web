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
    public $brand_ids = [];
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
    
    public $new_brand_name = '';
    
    public $showModal = false;
    public $showBrandListModal = false;
    public $showCategoryListModal = false;

    public $manageBrandId = null;
    public $manageBrandName = '';
    public $manageCategoryId = null;
    public $manageCategoryName = '';

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
            $this->selectedProducts = $this->with()['products']->pluck('id')->map(fn($id) => (string)$id)->toArray();
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
        $query = Product::query()->with(['category', 'brands']);

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
            $query->orderBy(Brand::select('name')
                ->join('brand_product', 'brands.id', '=', 'brand_product.brand_id')
                ->whereColumn('brand_product.product_id', 'products.id')
                ->limit(1), $this->sortDirection)
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
        $this->brand_ids = $product->brands->pluck('id')->map(fn($id) => (string)$id)->toArray();
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
            'sku' => 'nullable|string|max:255|unique:products,sku,' . $this->product_id,
            'category_id' => 'required|exists:categories,id',
            'brand_ids' => 'nullable|array',
            'brand_ids.*' => 'exists:brands,id',
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
            // brand_ids se manejan con sync()
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

        $product = Product::updateOrCreate(['id' => $this->product_id], $data);
        $product->brands()->sync($this->brand_ids);

        $this->showModal = false;
        $this->loadProducts();
        $this->resetFields();
    }

    public function delete($id)
    {
        $product = Product::findOrFail($id);
        
        if (\App\Models\OrderItem::where('product_id', $product->id)->exists()) {
            $this->dispatch('notify', message: 'No se puede eliminar: el producto está en órdenes de compra.');
            return;
        }

        if ($product->image_url) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($product->image_url);
        }
        
        $product->delete();
        $this->loadProducts();
        $this->dispatch('notify', message: 'Producto eliminado correctamente.');
    }

    public function createBrandQuick($name)
    {
        if (empty(trim($name))) return;
        $name = trim($name);
        
        $brand = Brand::firstOrCreate(
            ['slug' => \Illuminate\Support\Str::slug($name)],
            ['name' => $name]
        );
        $this->loadBrandsWithCount();
        $this->brand_ids[] = (string)$brand->id;
        $this->dispatch('notify', message: 'Marca creada y seleccionada exitosamente.');
    }

    public function createCategoryQuick($name)
    {
        if (empty(trim($name))) return;
        $name = trim($name);
        
        $category = Category::firstOrCreate(
            ['slug' => \Illuminate\Support\Str::slug($name)],
            ['name' => $name]
        );
        $this->loadCategoriesWithCount();
        $this->category_id = $category->id;
        $this->dispatch('notify', message: 'Categoría creada y seleccionada exitosamente.');
    }

    public function resetFields()
    {
        $this->product_id = null;
        $this->category_id = '';
        $this->brand_ids = [];
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

    public function deleteSelected()
    {
        if (count($this->selectedProducts) > 0) {
            if (\App\Models\OrderItem::whereIn('product_id', $this->selectedProducts)->exists()) {
                $this->dispatch('notify', message: 'No se pueden eliminar productos que están en órdenes de compra.');
                return;
            }
            
            $products = Product::whereIn('id', $this->selectedProducts)->get();
            foreach($products as $product) {
                if ($product->image_url) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($product->image_url);
                }
            }
            
            Product::whereIn('id', $this->selectedProducts)->delete();
            $this->selectedProducts = [];
            $this->selectAll = false;
            $this->loadProducts();
            $this->dispatch('notify', message: 'Productos eliminados correctamente.');
        }
    }



    public function loadCategoriesWithCount()
    {
        $this->categories = Category::withCount('products')->get();
    }

    public function loadBrandsWithCount()
    {
        $this->brands = Brand::withCount('products')->get();
    }

    public function editBrand($id)
    {
        $brand = Brand::findOrFail($id);
        $this->manageBrandId = $brand->id;
        $this->manageBrandName = $brand->name;
    }

    public function saveBrand()
    {
        $this->validate(['manageBrandName' => 'required|string|max:255']);
        if ($this->manageBrandId) {
            $brand = Brand::find($this->manageBrandId);
            $brand->update(['name' => $this->manageBrandName, 'slug' => \Illuminate\Support\Str::slug($this->manageBrandName)]);
            $this->dispatch('notify', message: 'Marca actualizada exitosamente.');
        } else {
            Brand::create(['name' => $this->manageBrandName, 'slug' => \Illuminate\Support\Str::slug($this->manageBrandName)]);
            $this->dispatch('notify', message: 'Marca creada exitosamente.');
        }
        $this->loadBrandsWithCount();
        $this->manageBrandId = null;
        $this->manageBrandName = '';
    }

    public function deleteBrand($id)
    {
        $brand = Brand::findOrFail($id);
        if ($brand->products()->count() > 0) {
            $this->dispatch('notify', message: 'No se puede eliminar la marca porque tiene productos.');
            return;
        }
        $brand->delete();
        $this->loadBrandsWithCount();
        $this->dispatch('notify', message: 'Marca eliminada exitosamente.');
    }

    public function editCategory($id)
    {
        $cat = Category::findOrFail($id);
        $this->manageCategoryId = $cat->id;
        $this->manageCategoryName = $cat->name;
    }

    public function saveCategory()
    {
        $this->validate(['manageCategoryName' => 'required|string|max:255']);
        if ($this->manageCategoryId) {
            $cat = Category::find($this->manageCategoryId);
            $cat->update(['name' => $this->manageCategoryName, 'slug' => \Illuminate\Support\Str::slug($this->manageCategoryName)]);
            $this->dispatch('notify', message: 'Categoría actualizada exitosamente.');
        } else {
            Category::create(['name' => $this->manageCategoryName, 'slug' => \Illuminate\Support\Str::slug($this->manageCategoryName)]);
            $this->dispatch('notify', message: 'Categoría creada exitosamente.');
        }
        $this->loadCategoriesWithCount();
        $this->manageCategoryId = null;
        $this->manageCategoryName = '';
    }

    public function deleteCategory($id)
    {
        $cat = Category::findOrFail($id);
        if ($cat->products()->count() > 0) {
            $this->dispatch('notify', message: 'No se puede eliminar la categoría porque tiene productos.');
            return;
        }
        $cat->delete();
        $this->loadCategoriesWithCount();
        $this->dispatch('notify', message: 'Categoría eliminada exitosamente.');
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
            $query->whereHas('brands', fn($q) => $q->where('brands.id', $this->massBrandId));
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
    brandListOpen: @entangle('showBrandListModal').live,
    massUpdateOpen: @entangle('showMassUpdateModal').live,
    colDropdownOpen: false,
    previewImageOpen: false,
    previewImageUrl: '',
    isProductLoading: false
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
                    
                    <button type="button" @click="isProductLoading = true; modalOpen = true; $wire.create().then(() => isProductLoading = false)" class="group flex items-center justify-center p-2.5 rounded-full transition-all shadow-lg hover:shadow-xl hover:-translate-y-0.5 text-white font-bold" style="background-color: var(--color-primary); box-shadow: 0 4px 14px 0 var(--color-primary-glow);">
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
                        <tr @click="if(!isProductLoading) { isProductLoading = true; modalOpen = true; $wire.edit({{ $product->id }}).then(() => isProductLoading = false) }" class="hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-colors cursor-pointer {{ in_array($product->id, $selectedProducts) ? 'bg-indigo-50/50 dark:bg-indigo-900/10' : '' }}">
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
                                    {{ $product->brands->pluck('name')->join(', ') ?: 'N/A' }}
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
                                <button @click="if(!isProductLoading) { isProductLoading = true; modalOpen = true; $wire.edit({{ $product->id }}).then(() => isProductLoading = false) }" type="button" title="Editar" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors p-1.5 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/30">
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
            <div x-show="modalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-3xl text-left overflow-hidden shadow-2xl dark:[box-shadow:0_25px_50px_-12px_var(--color-primary-glow)] transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full relative">
                
                <!-- Overlay de carga premium -->
                <div x-show="isProductLoading" x-transition.opacity.duration.200ms class="absolute inset-0 z-50 flex items-center justify-center bg-white/40 dark:bg-gray-900/40 backdrop-blur-sm rounded-3xl" style="display: none;">
                    <div class="bg-white dark:bg-gray-800 px-6 py-5 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 flex flex-col items-center transform transition-all animate-pulse-once">
                        <div class="relative w-10 h-10 mb-3">
                            <div class="absolute inset-0 rounded-full border-t-2 border-[var(--color-primary)] animate-spin"></div>
                            <div class="absolute inset-2 rounded-full border-r-2 border-[var(--color-primary)] opacity-50 animate-spin" style="animation-direction: reverse; animation-duration: 1.5s;"></div>
                        </div>
                        <span class="text-sm font-bold bg-clip-text text-transparent bg-gradient-to-r from-[var(--color-primary)] to-blue-600 tracking-wide uppercase text-[10px]">Actualizando...</span>
                    </div>
                </div>

                <div class="px-8 pt-8 pb-4">
                    <h3 class="text-2xl leading-6 font-bold text-gray-900 dark:text-white mb-8 tracking-tight">
                        {{ $product_id ? 'Editar Producto' : 'Nuevo Producto' }}
                    </h3>
                    <form wire:submit="save">
                        <!-- Primera Fila: Nombre y SKU -->
                        <div class="flex flex-col md:flex-row gap-5 mb-4">
                            <div class="w-full md:w-4/5">
                                <label class="block text-gray-700 dark:text-gray-400 text-xs font-bold mb-2 uppercase tracking-wider">Nombre</label>
                                <input wire:model="name" type="text" class="w-full py-2.5 px-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent transition-colors">
                                @error('name') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div class="w-full md:w-1/5">
                                <label class="block text-gray-700 dark:text-gray-400 text-xs font-bold mb-2 uppercase tracking-wider">SKU / Código</label>
                                <input wire:model="sku" type="text" class="w-full py-2.5 px-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent transition-colors" placeholder="Ej: RM-304">
                                @error('sku') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Segunda Fila: Categoría, Marca y Stock -->
                        <div class="flex flex-col md:flex-row gap-5 mb-5">
                            <div class="w-full md:w-2/5">
                                <div class="flex justify-between items-center mb-2">
                                    <label class="flex items-center text-gray-700 dark:text-gray-400 text-xs font-bold uppercase tracking-wider relative" x-data="{ openCatPrompt: false, newCatName: '' }" @click.outside="openCatPrompt = false">
                                        Categoría
                                        <button type="button" @click="openCatPrompt = !openCatPrompt; if(openCatPrompt) $nextTick(() => $refs.catInput.focus())" class="ml-2 bg-[var(--color-primary)] text-white w-5 h-5 flex items-center justify-center rounded-full hover:bg-blue-600 transition-colors shadow-sm" title="Crear nueva categoría">+</button>
                                        
                                        <div x-show="openCatPrompt" style="display:none;" x-transition class="absolute z-50 left-0 top-full mt-2 w-64 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 p-3">
                                            <span class="block text-xs text-gray-500 dark:text-gray-400 mb-2 normal-case font-normal">Nombre de la nueva categoría:</span>
                                            <div class="flex gap-2">
                                                <input x-ref="catInput" x-model="newCatName" @keydown.enter.prevent="$wire.createCategoryQuick(newCatName); openCatPrompt = false; newCatName = ''" type="text" class="w-full text-sm py-1.5 px-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--color-primary)]">
                                                <button type="button" @click="if(newCatName) { $wire.createCategoryQuick(newCatName); openCatPrompt = false; newCatName = ''; }" class="bg-[var(--color-primary)] text-white px-3 py-1.5 rounded-lg text-xs font-bold hover:opacity-90">Crear</button>
                                            </div>
                                        </div>
                                    </label>
                                    <button type="button" @click="catListOpen = true" class="text-[10px] font-bold text-[var(--color-primary)] hover:underline uppercase tracking-wider focus:outline-none">
                                        Gestionar
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
                            <div class="w-full md:w-2/5">
                                <div class="flex justify-between items-center mb-2">
                                    <label class="flex items-center text-gray-700 dark:text-gray-400 text-xs font-bold uppercase tracking-wider relative" x-data="{ openBrandPrompt: false, newBrandName: '' }" @click.outside="openBrandPrompt = false">
                                        Marca
                                        <button type="button" @click="openBrandPrompt = !openBrandPrompt; if(openBrandPrompt) $nextTick(() => $refs.brandInput.focus())" class="ml-2 bg-[var(--color-primary)] text-white w-5 h-5 flex items-center justify-center rounded-full hover:bg-blue-600 transition-colors shadow-sm" title="Crear nueva marca">+</button>
                                        
                                        <div x-show="openBrandPrompt" style="display:none;" x-transition class="absolute z-50 left-0 top-full mt-2 w-64 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 p-3">
                                            <span class="block text-xs text-gray-500 dark:text-gray-400 mb-2 normal-case font-normal">Nombre de la nueva marca:</span>
                                            <div class="flex gap-2">
                                                <input x-ref="brandInput" x-model="newBrandName" @keydown.enter.prevent="$wire.createBrandQuick(newBrandName); openBrandPrompt = false; newBrandName = ''" type="text" class="w-full text-sm py-1.5 px-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--color-primary)]">
                                                <button type="button" @click="if(newBrandName) { $wire.createBrandQuick(newBrandName); openBrandPrompt = false; newBrandName = ''; }" class="bg-[var(--color-primary)] text-white px-3 py-1.5 rounded-lg text-xs font-bold hover:opacity-90">Crear</button>
                                            </div>
                                        </div>
                                    </label>
                                    <button type="button" @click="brandListOpen = true" class="text-[10px] font-bold text-[var(--color-primary)] hover:underline uppercase tracking-wider focus:outline-none">
                                        Gestionar
                                    </button>
                                </div>
                                <div class="flex gap-2">
                                    <select wire:model="brand_ids" multiple class="w-full py-2.5 px-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent transition-colors min-h-[100px]">
                                        @foreach($brands as $brand)
                                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('brand_ids') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div class="w-full md:w-1/5">
                                <label class="block text-gray-700 dark:text-gray-400 text-xs font-bold mb-2 uppercase tracking-wider mt-[2px]">Stock</label>
                                <input wire:model="stock" type="number" class="w-full py-2.5 px-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent transition-colors" placeholder="0">
                                @error('stock') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
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


                        <div class="mb-8" x-data="{ expandedImage: false }">
                            <label class="block text-gray-700 dark:text-gray-400 text-xs font-bold mb-2 uppercase tracking-wider">Imagen del Producto</label>
                            @if($current_image_url)
                                <div class="mb-3 flex items-start space-x-4" :class="expandedImage ? 'flex-col space-x-0 space-y-4' : 'flex-row space-x-4'">
                                    <img @click="expandedImage = !expandedImage" src="{{ asset('storage/' . $current_image_url) }}" alt="Imagen actual" :class="expandedImage ? 'w-full h-auto max-h-96 object-contain' : 'h-20 w-20 object-cover'" class="rounded-xl border border-gray-300 dark:border-gray-700 shadow-sm cursor-pointer hover:opacity-90 transition-all duration-300 bg-white dark:bg-gray-800" title="Haz clic para agrandar">
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

    <!-- Brand Management Modal -->
    <div x-show="brandListOpen" class="fixed z-[70] inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" x-cloak>
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
            <div x-show="brandListOpen" 
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" 
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" 
                 class="fixed inset-0 bg-gray-900/40 dark:bg-[#0b0f19]/80 backdrop-blur-sm transition-opacity" 
                 @click="brandListOpen = false" aria-hidden="true"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="brandListOpen" 
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                 class="inline-block align-bottom bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full relative">
                
                <div class="px-8 pt-8 pb-8">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-2xl leading-6 font-bold text-gray-900 dark:text-white tracking-tight">Gestión de Marcas</h3>
                        <button @click="brandListOpen = false" class="text-gray-400 hover:text-gray-500 transition-colors focus:outline-none">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <div class="overflow-x-auto bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 max-h-[60vh] overflow-y-auto mb-4">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-100 dark:bg-gray-900 sticky top-0 z-10">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nombre</th>
                                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Productos</th>
                                    <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                                @foreach($brands as $brand)
                                    @if($manageBrandId === $brand->id)
                                    <tr class="bg-blue-50/50 dark:bg-blue-900/10">
                                        <td class="px-6 py-3" colspan="2">
                                            <input wire:model="manageBrandName" type="text" class="w-full py-2 px-3 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-[var(--color-primary)] dark:bg-gray-900 dark:text-white" @keydown.enter="$wire.saveBrand()" placeholder="Nombre de la marca">
                                            @error('manageBrandName') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                        </td>
                                        <td class="px-6 py-3 text-right">
                                            <button wire:click="saveBrand" class="text-white bg-green-500 hover:bg-green-600 font-bold py-1.5 px-3 rounded text-xs mr-2 transition-colors">Guardar</button>
                                            <button wire:click="$set('manageBrandId', null)" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 text-xs font-bold transition-colors">Cancelar</button>
                                        </td>
                                    </tr>
                                    @else
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white">{{ $brand->name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-bold text-gray-500 dark:text-gray-400">
                                            <span class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded-full">{{ $brand->products()->count() }}</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button wire:click="editBrand({{ $brand->id }})" class="text-[var(--color-primary)] hover:opacity-80 font-bold mr-4">Editar</button>
                                            <button wire:click="deleteBrand({{ $brand->id }})" wire:confirm="¿Eliminar la marca {{ $brand->name }}? Esta acción no se puede deshacer." class="text-red-600 hover:text-red-800 dark:hover:text-red-400 font-bold transition-colors">Eliminar</button>
                                        </td>
                                    </tr>
                                    @endif
                                @endforeach
                                @if(count($brands) === 0)
                                    <tr>
                                        <td colspan="3" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400 text-sm">No hay marcas creadas aún. Usá el botón + en el formulario para crear una.</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Management Modal -->
    <div x-show="catListOpen" class="fixed z-[70] inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" x-cloak>
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
            <div x-show="catListOpen" 
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" 
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" 
                 class="fixed inset-0 bg-gray-900/40 dark:bg-[#0b0f19]/80 backdrop-blur-sm transition-opacity" 
                 @click="catListOpen = false" aria-hidden="true"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="catListOpen" 
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                 class="inline-block align-bottom bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full relative">
                
                <div class="px-8 pt-8 pb-8">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-2xl leading-6 font-bold text-gray-900 dark:text-white tracking-tight">Gestión de Categorías</h3>
                        <button @click="catListOpen = false" class="text-gray-400 hover:text-gray-500 transition-colors focus:outline-none">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <div class="overflow-x-auto bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 max-h-[60vh] overflow-y-auto mb-4">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-100 dark:bg-gray-900 sticky top-0 z-10">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nombre</th>
                                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Productos</th>
                                    <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                                @foreach($categories as $category)
                                    @if($manageCategoryId === $category->id)
                                    <tr class="bg-blue-50/50 dark:bg-blue-900/10">
                                        <td class="px-6 py-3" colspan="2">
                                            <input wire:model="manageCategoryName" type="text" class="w-full py-2 px-3 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-[var(--color-primary)] dark:bg-gray-900 dark:text-white" @keydown.enter="$wire.saveCategory()" placeholder="Nombre de la categoría">
                                            @error('manageCategoryName') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                        </td>
                                        <td class="px-6 py-3 text-right">
                                            <button wire:click="saveCategory" class="text-white bg-green-500 hover:bg-green-600 font-bold py-1.5 px-3 rounded text-xs mr-2 transition-colors">Guardar</button>
                                            <button wire:click="$set('manageCategoryId', null)" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 text-xs font-bold transition-colors">Cancelar</button>
                                        </td>
                                    </tr>
                                    @else
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white">{{ $category->name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-bold text-gray-500 dark:text-gray-400">
                                            <span class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded-full">{{ $category->products()->count() }}</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button wire:click="editCategory({{ $category->id }})" class="text-[var(--color-primary)] hover:opacity-80 font-bold mr-4">Editar</button>
                                            <button wire:click="deleteCategory({{ $category->id }})" wire:confirm="¿Eliminar la categoría {{ $category->name }}? Esta acción no se puede deshacer." class="text-red-600 hover:text-red-800 dark:hover:text-red-400 font-bold transition-colors">Eliminar</button>
                                        </td>
                                    </tr>
                                    @endif
                                @endforeach
                                @if(count($categories) === 0)
                                    <tr>
                                        <td colspan="3" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400 text-sm">No hay categorías creadas aún. Usá el botón + en el formulario para crear una.</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
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
