<x-app-layout>
    @php
        $settings = \App\Models\StoreSetting::first();
        $storeName = $settings ? $settings->store_name : 'JCG Electrónica';
        
        // Obtenemos los últimos 8 productos para la página de inicio
        $latestProducts = \App\Models\Product::with('category')->latest()->take(8)->get();
        // Categorías principales para acceso rápido
        $categories = \App\Models\Category::take(4)->get();
    @endphp

    <div class="bg-white min-h-screen">
        
        {{-- ════════════════════════════════════════════════════════
             HERO SECTION — Limpio, centrado en Búsqueda
        ════════════════════════════════════════════════════════ --}}
        <section class="relative bg-gray-50 border-b border-gray-100 overflow-hidden">
            {{-- Elementos decorativos de fondo --}}
            <div class="absolute top-0 right-0 -mr-20 -mt-20 w-96 h-96 rounded-full bg-red-50 opacity-50 blur-3xl pointer-events-none"></div>
            <div class="absolute bottom-0 left-0 -ml-20 -mb-20 w-80 h-80 rounded-full bg-gray-100 opacity-50 blur-3xl pointer-events-none"></div>
            
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-28 flex flex-col lg:flex-row items-center gap-12">
                
                {{-- Texto y Búsqueda --}}
                <div class="flex-1 text-center lg:text-left z-10">
                    <span class="inline-block py-1 px-3 rounded-full bg-red-100 text-[var(--color-primary)] text-xs font-bold uppercase tracking-wider mb-6">
                        Mayorista y Minorista
                    </span>
                    <h1 class="text-5xl lg:text-7xl font-black text-gray-900 tracking-tight leading-[1.1] mb-6">
                        Encuentra tu <br class="hidden lg:block">
                        <span class="text-[var(--color-primary)]">Control Remoto</span>
                    </h1>
                    <p class="text-lg text-gray-600 mb-8 max-w-2xl mx-auto lg:mx-0">
                        Tenemos el catálogo más completo de controles remotos para TV, Smart TV, Aire Acondicionado y TV Box. Busca por marca o modelo.
                    </p>
                    
                    {{-- Buscador Hero --}}
                    <div class="max-w-xl mx-auto lg:mx-0">
                        <form action="{{ route('shop') }}" method="GET" class="relative flex items-center">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input type="text" name="q" placeholder="Ej: Control Samsung, Noblex, Aire Surrey..." 
                                   class="block w-full pl-11 pr-32 py-4 border border-gray-300 rounded-full text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent text-lg shadow-sm transition-shadow hover:shadow-md">
                            <button type="submit" 
                                    class="absolute right-2 top-2 bottom-2 px-6 rounded-full text-white font-bold transition-transform active:scale-95"
                                    style="background-color: var(--color-primary);">
                                Buscar
                            </button>
                        </form>
                    </div>
                    
                    {{-- Marcas --}}
                    <div class="mt-10 pt-8 border-t border-gray-200">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Trabajamos con todas las marcas</p>
                        <div class="flex flex-wrap justify-center lg:justify-start gap-4 text-gray-500 font-bold text-sm">
                            <span>SAMSUNG</span>
                            <span>&bull;</span>
                            <span>LG</span>
                            <span>&bull;</span>
                            <span>NOBLEX</span>
                            <span>&bull;</span>
                            <span>PHILIPS</span>
                            <span>&bull;</span>
                            <span>BGH</span>
                        </div>
                    </div>
                </div>
                
                {{-- Imagen Destacada --}}
                <div class="flex-1 w-full max-w-md lg:max-w-full relative z-10 hidden md:block">
                    <div class="relative w-full aspect-square flex items-center justify-center">
                        <div class="absolute inset-0 bg-white rounded-full shadow-2xl opacity-80 scale-75"></div>
                        <img src="{{ asset('storage/banners/tv_remote.png') }}" alt="Control Remoto Moderno" class="relative z-10 w-3/4 object-contain drop-shadow-2xl hover:scale-105 transition-transform duration-500 hover:rotate-2 mix-blend-multiply">
                        <img src="{{ asset('storage/banners/ac_remote.png') }}" alt="Control de Aire" class="absolute z-0 w-1/2 object-contain drop-shadow-xl -bottom-10 -right-10 opacity-90 -rotate-12 blur-[1px] mix-blend-multiply">
                    </div>
                </div>
            </div>
        </section>

        {{-- ════════════════════════════════════════════════════════
             CATEGORÍAS DESTACADAS
        ════════════════════════════════════════════════════════ --}}
        <section class="py-16 bg-white border-b border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                    
                    <a href="{{ route('shop', ['categoria' => 'TV']) }}" class="group relative h-32 md:h-40 rounded-2xl overflow-hidden shadow-sm flex items-center justify-center bg-gray-900">
                        <div class="absolute inset-0 bg-gradient-to-t from-gray-900 to-transparent opacity-80 z-10"></div>
                        <img src="{{ asset('storage/banners/cat_tv.png') }}" alt="Controles TV" class="absolute inset-0 w-full h-full object-cover opacity-60 group-hover:scale-110 transition-transform duration-500">
                        <span class="relative z-20 text-white font-bold text-sm md:text-base text-center px-4 tracking-wide group-hover:-translate-y-1 transition-transform drop-shadow-md">CONTROLES TV / SMART</span>
                    </a>
                    
                    <a href="{{ route('shop', ['categoria' => 'Aire']) }}" class="group relative h-32 md:h-40 rounded-2xl overflow-hidden shadow-sm flex items-center justify-center bg-gray-900">
                        <div class="absolute inset-0 bg-gradient-to-t from-gray-900 to-transparent opacity-80 z-10"></div>
                        <img src="{{ asset('storage/banners/cat_ac.png') }}" alt="Aire Acondicionado" class="absolute inset-0 w-full h-full object-cover opacity-60 group-hover:scale-110 transition-transform duration-500">
                        <span class="relative z-20 text-white font-bold text-sm md:text-base text-center px-4 tracking-wide group-hover:-translate-y-1 transition-transform drop-shadow-md">AIRE ACONDICIONADO</span>
                    </a>
                    
                    <a href="{{ route('shop', ['categoria' => 'Box']) }}" class="group relative h-32 md:h-40 rounded-2xl overflow-hidden shadow-sm flex items-center justify-center bg-gray-900">
                        <div class="absolute inset-0 bg-gradient-to-t from-gray-900 to-transparent opacity-80 z-10"></div>
                        <img src="{{ asset('storage/banners/cat_box.png') }}" alt="TV Box / Decos" class="absolute inset-0 w-full h-full object-cover opacity-60 group-hover:scale-110 transition-transform duration-500">
                        <span class="relative z-20 text-white font-bold text-sm md:text-base text-center px-4 tracking-wide group-hover:-translate-y-1 transition-transform drop-shadow-md">TV BOX / DECOS</span>
                    </a>
                    
                    <a href="{{ route('shop', ['categoria' => 'Tiras']) }}" class="group relative h-32 md:h-40 rounded-2xl overflow-hidden shadow-sm flex items-center justify-center bg-gray-900">
                        <div class="absolute inset-0 bg-gradient-to-t from-gray-900 to-transparent opacity-80 z-10"></div>
                        <img src="{{ asset('storage/banners/cat_led.png') }}" alt="Tiras LED y Más" class="absolute inset-0 w-full h-full object-cover opacity-60 group-hover:scale-110 transition-transform duration-500">
                        <span class="relative z-20 text-white font-bold text-sm md:text-base text-center px-4 tracking-wide group-hover:-translate-y-1 transition-transform drop-shadow-md">TIRAS LED Y MÁS</span>
                    </a>

                </div>
            </div>
        </section>

        {{-- ════════════════════════════════════════════════════════
             ÚLTIMOS INGRESOS (Reutilizando diseño de tarjeta)
        ════════════════════════════════════════════════════════ --}}
        <section class="py-20 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="flex items-end justify-between mb-10">
                    <div>
                        <h2 class="text-3xl font-black text-gray-900 tracking-tight">Últimos Ingresos</h2>
                        <p class="text-gray-500 mt-2">Novedades y reposición de stock</p>
                    </div>
                    <a href="{{ route('shop') }}" class="hidden sm:inline-flex items-center gap-2 font-bold text-[var(--color-primary)] hover:text-[var(--color-primary-hover)] transition-colors">
                        Ver todo el catálogo
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                    </a>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 xl:grid-cols-5 gap-3 sm:gap-4">
                    @forelse ($latestProducts as $product)
                        <article class="group relative flex flex-col bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm hover:shadow hover:-translate-y-0.5 transition-all duration-300">
                            
                            {{-- Contenedor de Imagen (Más compacto) --}}
                            <a href="{{ route('product.detail', $product->slug) }}" wire:navigate class="relative block aspect-square bg-white overflow-hidden p-3 border-b border-gray-100 flex items-center justify-center">
                                @if($product->image_url)
                                    <img src="{{ asset('storage/' . $product->image_url) }}"
                                         alt="{{ $product->name }}"
                                         class="w-full h-full object-contain transition-transform duration-500 group-hover:scale-105 drop-shadow-sm"
                                         onerror="this.src='https://images.unsplash.com/photo-1591488320449-011701bb6704?q=80&w=400&auto=format&fit=crop'">
                                @else
                                    <svg class="h-10 w-10 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                @endif
                                @if($product->stock <= 0)
                                    <span class="absolute top-2 right-2 text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-red-100 text-red-700">Sin Stock</span>
                                @endif
                            </a>

                            {{-- Contenido (Más compacto) --}}
                            <div class="flex flex-col flex-grow p-3 bg-white">
                                <div class="flex-grow">
                                    @if($product->category)
                                        <span class="text-[9px] font-bold uppercase tracking-widest text-gray-400 mb-1 block truncate">{{ $product->category->name }}</span>
                                    @endif
                                    <a href="{{ route('product.detail', $product->slug) }}" wire:navigate>
                                        <h3 class="text-xs sm:text-sm font-bold text-gray-800 leading-tight hover:text-[var(--color-primary)] transition-colors line-clamp-2" title="{{ $product->name }}">
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
                                    <div class="w-full">
                                        <livewire:add-to-cart :product="$product" :compact="true" wire:key="add-cart-home-{{ $product->id }}" />
                                    </div>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="col-span-full py-12 text-center text-gray-500">
                            No hay productos disponibles por el momento.
                        </div>
                    @endforelse
                </div>
                
                <div class="mt-10 text-center sm:hidden">
                    <a href="{{ route('shop') }}" class="inline-flex items-center justify-center w-full py-3 px-4 bg-gray-100 text-gray-900 font-bold rounded-xl hover:bg-gray-200 transition-colors">
                        Ver todo el catálogo
                    </a>
                </div>

            </div>
        </section>

    </div>
</x-app-layout>
