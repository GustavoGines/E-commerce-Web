<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Product;

new #[Layout('layouts.app')] class extends Component {
    public Product $product;
    public $relatedProducts;

    public function mount($slug)
    {
        $this->product = Product::where('slug', $slug)->firstOrFail();
        
        $this->relatedProducts = Product::where('category_id', $this->product->category_id)
                                        ->where('id', '!=', $this->product->id)
                                        ->take(4)
                                        ->get();
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
            <a href="{{ route('home') }}" wire:navigate class="hover:text-[var(--color-primary)] transition-colors">Tienda</a>
            <span>/</span>
            <span>{{ $product->category ? $product->category->name : 'General' }}</span>
            <span>/</span>
            <span class="text-gray-900 dark:text-gray-100 font-medium">{{ $product->name }}</span>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
        
        <!-- Product Main Details -->
        <div class="bg-white/80 dark:bg-gray-800/40 backdrop-blur-md border border-gray-200 dark:border-gray-700/50 shadow-xl dark:shadow-2xl sm:rounded-3xl p-8 mb-12">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                <!-- Image Gallery -->
                <div class="relative group">
                    <div class="aspect-square bg-gray-100 dark:bg-gray-900/80 rounded-2xl overflow-hidden border border-gray-200 dark:border-gray-700/50 flex items-center justify-center p-8">
                        @if($product->image_url)
                            <img src="{{ asset('storage/' . $product->image_url) }}" alt="{{ $product->name }}" class="object-contain w-full h-full transform group-hover:scale-105 transition-transform duration-500">
                        @else
                            <svg class="h-32 w-32 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        @endif
                    </div>
                </div>

                <!-- Info -->
                <div class="flex flex-col justify-center">
                    @if($product->stock > 0)
                        <span class="inline-block px-3 py-1 bg-green-100 dark:bg-green-500/20 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-500/30 text-xs uppercase tracking-widest font-bold rounded-full w-max mb-4">En Stock: {{ $product->stock }}</span>
                    @else
                        <span class="inline-block px-3 py-1 bg-red-100 dark:bg-red-500/20 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-500/30 text-xs uppercase tracking-widest font-bold rounded-full w-max mb-4">Agotado</span>
                    @endif

                    <h1 class="text-3xl sm:text-4xl font-black text-gray-900 dark:text-white tracking-tight leading-tight mb-4">{{ $product->name }}</h1>
                    <p class="text-gray-600 dark:text-gray-400 text-lg mb-8 leading-relaxed">{{ $product->description }}</p>

                    <div class="bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 mb-8">
                        @if(auth()->check() && auth()->user()->role === 'mayorista')
                            <div class="flex justify-between items-center opacity-60 mb-2">
                                <span class="text-sm uppercase tracking-wider font-semibold text-gray-500 dark:text-gray-400 line-through">Precio Retail</span>
                                <span class="text-lg font-medium text-gray-500 dark:text-gray-400 line-through">${{ number_format($product->retail_price, 2) }}</span>
                            </div>
                            <div class="flex justify-between items-end">
                                <span class="text-sm font-bold uppercase tracking-wider text-[var(--color-primary)]">Precio Mayorista</span>
                                <span class="text-4xl sm:text-5xl font-black tracking-tighter text-gray-900 dark:text-white">${{ number_format($product->wholesale_price, 2) }}</span>
                            </div>
                        @else
                            <div class="flex justify-between items-end mb-4">
                                <span class="text-sm font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Precio Oficial</span>
                                <span class="text-4xl sm:text-5xl font-black tracking-tighter text-gray-900 dark:text-white">${{ number_format($product->retail_price, 2) }}</span>
                            </div>
                            <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span>12 meses de garantía oficial</span>
                            </div>
                        @endif
                    </div>

                    <div>
                        <livewire:add-to-cart :product="$product" wire:key="detail-cart-{{ $product->id }}" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Technical Specs -->
        @if($product->technical_specs && count($product->technical_specs) > 0)
        <div class="mb-16">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight mb-6 flex items-center">
                <svg class="w-6 h-6 mr-2 text-[var(--color-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
                Especificaciones Técnicas
            </h3>
            <div class="bg-white/80 dark:bg-gray-800/40 backdrop-blur-md border border-gray-200 dark:border-gray-700/50 shadow-sm rounded-2xl overflow-hidden">
                <dl class="divide-y divide-gray-200 dark:divide-gray-700/50">
                    @foreach($product->technical_specs as $key => $value)
                        <div class="px-6 py-5 grid grid-cols-3 gap-4 hover:bg-gray-50 dark:hover:bg-gray-800/60 transition-colors">
                            <dt class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ $key }}</dt>
                            <dd class="text-sm text-gray-900 dark:text-white font-medium col-span-2">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </div>
        @endif

        <!-- Related Products -->
        @if($relatedProducts->count() > 0)
        <div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight mb-6">Productos Similares</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach ($relatedProducts as $related)
                    <a href="{{ route('product.detail', $related->slug) }}" wire:navigate class="group bg-white dark:bg-gray-800/40 backdrop-blur-md border border-gray-200 dark:border-gray-700/50 rounded-2xl overflow-hidden hover:-translate-y-1 transition-all duration-300 block hover:shadow-xl dark:hover:shadow-[var(--color-primary-glow)]">
                        <div class="aspect-video bg-gray-100 dark:bg-gray-900/80 p-4 flex items-center justify-center border-b border-gray-200 dark:border-gray-700/50">
                            @if($related->image_url)
                                <img src="{{ asset('storage/' . $related->image_url) }}" class="object-contain h-full transform group-hover:scale-105 transition-transform duration-500">
                            @endif
                        </div>
                        <div class="p-4">
                            <h4 class="font-bold text-gray-900 dark:text-gray-100 truncate group-hover:text-[var(--color-primary)] transition-colors">{{ $related->name }}</h4>
                            <div class="mt-2 text-[var(--color-primary)] font-black">
                                ${{ number_format(auth()->check() && auth()->user()->role === 'mayorista' ? $related->wholesale_price : $related->retail_price, 2) }}
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
