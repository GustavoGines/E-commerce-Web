<?php

use Livewire\Volt\Component;
use App\Models\Product;
use Livewire\Attributes\On;

new class extends Component {
    public $cart = [];
    public $products = [];
    public $subtotal = 0;
    public $theme = 'stealth';
    
    public function mount()
    {
        $this->loadCart();
        $settings = \App\Models\StoreSetting::first();
        if ($settings) {
            $this->theme = $settings->theme_name ?? 'stealth';
        }
    }

    #[On('cart-updated')]
    public function loadCart()
    {
        $cartService = app(\App\Services\CartService::class);
        $this->cart = $cartService->getCartItemsArray();
        
        if (count($this->cart) > 0) {
            $this->products = Product::whereIn('id', array_keys($this->cart))->get()->keyBy('id');
            $this->calculateSubtotal();
        } else {
            $this->products = collect();
            $this->subtotal = 0;
        }
    }

    public function getPrice($product, $quantity)
    {
        if ($this->theme === 'modern-light') {
            $hasPreviousOrders = auth()->check() && auth()->user()->orders()->where('status', '!=', 'cancelada')->exists();
            $totalItems = array_sum($this->cart);
            
            if ($hasPreviousOrders || $totalItems >= 10) {
                return $product->wholesale_price;
            } else {
                return $product->retail_price;
            }
        }

        return ($quantity >= $product->wholesale_min_quantity) ? $product->wholesale_price : $product->retail_price;
    }

    public function calculateSubtotal()
    {
        $this->subtotal = 0;

        foreach ($this->cart as $productId => $quantity) {
            if (isset($this->products[$productId])) {
                $product = $this->products[$productId];
                $price = $this->getPrice($product, $quantity);
                $this->subtotal += $price * $quantity;
            }
        }
    }

    public function updateQuantity($productId, $action)
    {
        $cartService = app(\App\Services\CartService::class);
        $success = $cartService->updateQuantity($productId, $action);
        
        if (!$success && $action === 'increment') {
            $this->dispatch('notify', message: 'Límite de stock alcanzado', type: 'error');
        } else {
            $this->dispatch('cart-updated');
        }
    }

    public function removeItem($productId)
    {
        $cartService = app(\App\Services\CartService::class);
        $cartService->removeItem($productId);
        $this->dispatch('cart-updated');
    }
}; ?>

<div x-data="{}"
     x-show="$store.cart.open"
     @open-cart.window="$store.cart.show()"
     @keydown.escape.window="$store.cart.hide()"
     class="relative z-[100]"
     aria-labelledby="slide-over-title" role="dialog" aria-modal="true" x-cloak>
    
    <!-- Backdrop -->
    <div x-show="$store.cart.open" 
         x-transition:enter="ease-in-out duration-500" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100" 
         x-transition:leave="ease-in-out duration-500" 
         x-transition:leave-start="opacity-100" 
         x-transition:leave-end="opacity-0" 
         class="fixed inset-0 transition-opacity {{ $theme === 'luxury' ? 'bg-[#030712]/80 backdrop-blur-md' : 'bg-gray-900/60 dark:bg-[#0b0f19]/80 backdrop-blur-sm' }}" 
         @click="$store.cart.hide()">
    </div>

    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute inset-0 overflow-hidden">
            <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                
                <!-- Slide-over panel -->
                <div x-show="$store.cart.open" 
                     x-transition:enter="transform transition ease-in-out duration-500 sm:duration-700" 
                     x-transition:enter-start="translate-x-full" 
                     x-transition:enter-end="translate-x-0" 
                     x-transition:leave="transform transition ease-in-out duration-500 sm:duration-700" 
                     x-transition:leave-start="translate-x-0" 
                     x-transition:leave-end="translate-x-full" 
                     class="pointer-events-auto w-screen max-w-md">
                     
                     <div class="flex h-full flex-col shadow-2xl transition-colors duration-300 {{ $theme === 'luxury' ? 'bg-[#0a0f1c]/90 backdrop-blur-3xl border-l border-white/5' : 'bg-white dark:bg-gray-900 border-l border-gray-200 dark:border-gray-800' }}" :style="('{{ $theme }}' === 'stealth' && $store.theme.dark) ? 'box-shadow: -10px 0 30px -10px var(--color-primary-glow);' : ''">
                        <div class="flex-1 overflow-y-auto px-4 py-6 sm:px-6">
                            <div class="flex items-start justify-between">
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white" id="slide-over-title">Carrito de Compras</h2>
                                <div class="ml-3 flex h-7 items-center">
                                    <button type="button" @click="$store.cart.hide()" class="relative -m-2 p-2 text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 transition-colors focus:outline-none">
                                        <span class="absolute -inset-0.5"></span>
                                        <span class="sr-only">Cerrar panel</span>
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="mt-8">
                                <div class="flow-root">
                                    <ul role="list" class="-my-6 divide-y divide-gray-200 dark:divide-gray-800">
                                        @forelse($cart as $productId => $quantity)
                                            @if(isset($products[$productId]))
                                                @php
                                                    $product = $products[$productId];
                                                    $price = $this->getPrice($product, $quantity);
                                                @endphp
                                                <li class="flex py-6 transition-all">
                                                    <div class="h-24 w-24 flex-shrink-0 overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800">
                                                        @if($product->image_url)
                                                            <img src="{{ asset('storage/' . $product->image_url) }}" alt="{{ $product->name }}" class="h-full w-full object-cover object-center">
                                                        @else
                                                            <div class="h-full w-full flex items-center justify-center text-gray-400 text-xs font-bold uppercase">Sin img</div>
                                                        @endif
                                                    </div>

                                                    <div class="ml-4 flex flex-1 flex-col justify-between">
                                                        <div>
                                                            <div class="flex flex-col sm:flex-row sm:justify-between text-base font-bold text-gray-900 dark:text-white gap-1 sm:gap-4">
                                                                <h3 class="line-clamp-2 leading-tight flex-1">
                                                                    {{ $product->name }}
                                                                </h3>
                                                                <p class="text-[var(--color-primary)] whitespace-nowrap">${{ number_format($price * $quantity, 2) }}</p>
                                                            </div>
                                                            <div class="mt-1 flex items-center flex-wrap gap-2">
                                                                @if($price == $product->wholesale_price)
                                                                    <p class="text-xs text-gray-400 dark:text-gray-500 line-through">${{ number_format($product->retail_price, 2) }} c/u</p>
                                                                    <p class="text-sm font-black text-emerald-600 dark:text-emerald-400">${{ number_format($price, 2) }} c/u</p>
                                                                    <span class="inline-flex items-center text-[9px] font-black uppercase tracking-wider text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-transparent border border-emerald-200 dark:border-emerald-500/50 px-1.5 py-0.5 rounded shadow-sm">
                                                                        🔥 Precio Mayorista
                                                                    </span>
                                                                @else
                                                                    <p class="text-sm text-gray-500 dark:text-gray-400">${{ number_format($price, 2) }} c/u</p>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div class="flex flex-1 items-end justify-between text-sm mt-3 sm:mt-0">
                                                            <div class="flex items-center border rounded-full overflow-hidden shadow-sm relative isolate {{ $theme === 'luxury' ? 'border-white/10 bg-white/5' : 'border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800' }}">
                                                                <button wire:click="updateQuantity({{ $productId }}, 'decrement')" wire:loading.attr="disabled" type="button" class="px-3 py-1 font-bold transition-colors disabled:cursor-not-allowed {{ $theme === 'luxury' ? 'text-gray-400 hover:bg-white/10 disabled:text-gray-600' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:text-gray-300 dark:disabled:text-gray-600' }}">-</button>
                                                                <span class="px-2 font-bold min-w-[2rem] text-center {{ $theme === 'luxury' ? 'text-white' : 'text-gray-900 dark:text-white' }}">
                                                                    <span wire:loading.remove wire:target="updateQuantity({{ $productId }}, 'decrement'), updateQuantity({{ $productId }}, 'increment')">{{ $quantity }}</span>
                                                                    <span wire:loading wire:target="updateQuantity({{ $productId }}, 'decrement'), updateQuantity({{ $productId }}, 'increment')" class="inline-block animate-pulse w-3 h-3 rounded-full {{ $theme === 'luxury' ? 'bg-white/50' : 'bg-gray-400' }}"></span>
                                                                </span>
                                                                <button wire:click="updateQuantity({{ $productId }}, 'increment')" wire:loading.attr="disabled" type="button" class="px-3 py-1 font-bold transition-colors disabled:cursor-not-allowed {{ $theme === 'luxury' ? 'text-gray-400 hover:bg-white/10 disabled:text-gray-600' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:text-gray-300 dark:disabled:text-gray-600' }}" @if($quantity >= $product->stock) disabled @endif>+</button>
                                                            </div>

                                                            <div class="flex">
                                                                <button wire:click="removeItem({{ $productId }})" type="button" class="font-medium text-red-500 hover:text-red-400 transition-colors">Eliminar</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </li>
                                            @endif
                                        @empty
                                            <li class="py-12 flex flex-col items-center justify-center text-center">
                                                <svg class="h-16 w-16 text-gray-300 dark:text-gray-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                                </svg>
                                                <p class="text-gray-500 dark:text-gray-400 font-medium">Tu carrito está vacío.</p>
                                                <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">¡Añade algo de hardware a tu setup!</p>
                                            </li>
                                        @endforelse
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="border-t px-4 py-6 sm:px-6 transition-colors duration-300 {{ $theme === 'luxury' ? 'border-white/10 bg-white/5' : 'border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/50' }}">
                            <div class="flex justify-between text-base font-black text-xl {{ $theme === 'luxury' ? 'text-white' : 'text-gray-900 dark:text-white' }}">
                                <p>Subtotal</p>
                                <p>${{ number_format($subtotal, 2) }}</p>
                            </div>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Impuestos y envío calculados en el checkout.</p>
                            <div class="mt-6">
                                <a href="{{ route('checkout') }}"
                                   wire:navigate
                                   @click="$store.cart.hide()"
                                   class="flex items-center justify-center w-full rounded-full px-6 py-4 text-base font-bold text-white shadow-lg transition-all hover:opacity-90 hover:-translate-y-0.5 {{ empty($cart) ? 'opacity-50 cursor-not-allowed pointer-events-none' : '' }}"
                                   style="background-color: var(--color-primary); box-shadow: 0 4px 14px 0 var(--color-primary-glow);">
                                    Proceder al Pago
                                </a>
                            </div>
                            <div class="mt-6 flex justify-center text-center text-sm">
                                <p class="text-gray-500 dark:text-gray-400">
                                    o&nbsp;
                                    <button type="button" @click="$store.cart.hide()" class="font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white underline underline-offset-2 transition-colors">
                                        Seguir Comprando
                                        <span aria-hidden="true">&rarr;</span>
                                    </button>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
