<?php

use Livewire\Volt\Component;

new class extends Component {
    public $product;
    public $quantity = 1;
    public $compact = false;

    public function incrementQuantity()
    {
        if ($this->quantity < $this->product->stock) {
            $this->quantity++;
        }
    }

    public function decrementQuantity()
    {
        if ($this->quantity > 1) {
            $this->quantity--;
        }
    }

    public function addToCart()
    {
        $cartService = app(\App\Services\CartService::class);
        
        if ($cartService->addItem($this->product->id, $this->quantity)) {
            $this->dispatch('cart-updated');
            $this->dispatch('notify', message: 'Producto añadido al carrito');
            // Opcional: resetear la cantidad después de agregar
            // $this->quantity = 1;
        } else {
            $this->dispatch('notify', message: 'No hay stock suficiente', type: 'error');
        }
    }
}; ?>

<div class="{{ $compact ? 'mt-3' : 'mt-4' }} w-full">
    @if($product->stock > 0)
        <div class="flex {{ $compact ? 'flex-col items-center gap-2' : 'items-center gap-4' }} w-full">
            <!-- Selector de cantidad -->
            <div x-data="{ qty: @entangle('quantity') }" class="flex items-center border border-gray-200 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-800/50 p-1 flex-shrink-0 {{ $compact ? 'w-full' : 'w-32 sm:w-36' }} justify-between">
                <button @click="if(qty > 1) qty--" type="button" class="{{ $compact ? 'w-8 h-8' : 'w-10 h-10' }} flex items-center justify-center rounded-lg bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 shadow-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed" :disabled="qty <= 1">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4" /></svg>
                </button>
                <input type="number"
                       x-model.number="qty"
                       min="1" :max="{{ $product->stock }}"
                       @change="if(!qty || qty < 1) qty = 1; if(qty > {{ $product->stock }}) qty = {{ $product->stock }}"
                       class="{{ $compact ? 'w-8 text-sm px-0' : 'w-12 text-base px-1' }} text-center font-bold text-gray-900 dark:text-white bg-transparent border-0 border-transparent outline-none focus:ring-0 focus:border-transparent focus:outline-none shadow-none p-0 m-0 appearance-none [-moz-appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:m-0">
                <button @click="if(qty < {{ $product->stock }}) qty++" type="button" class="{{ $compact ? 'w-8 h-8' : 'w-10 h-10' }} flex items-center justify-center rounded-lg bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 shadow-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed" :disabled="qty >= {{ $product->stock }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                </button>
            </div>

            <!-- Botón Añadir -->
            <button
                wire:click="addToCart"
                wire:loading.attr="disabled"
                class="w-full sm:flex-1 flex items-center justify-center {{ $compact ? 'py-2.5 px-2' : 'py-3.5 px-4' }} rounded-xl text-white font-bold tracking-wide transition-all shadow-md hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-70 disabled:cursor-not-allowed group"
                style="background-color: var(--color-primary); box-shadow: 0 4px 10px -2px var(--color-primary-glow);"
                onmouseover="this.style.backgroundColor='var(--color-primary-hover)'"
                onmouseout="this.style.backgroundColor='var(--color-primary)'">
                <svg wire:loading.remove wire:target="addToCart" class="w-5 h-5 {{ $compact ? 'mr-1 sm:mr-2' : 'mr-2' }} transition-transform group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <svg wire:loading wire:target="addToCart" class="animate-spin -ml-1 {{ $compact ? 'mr-1 sm:mr-2' : 'mr-3' }} h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                @if($compact)
                    <span wire:loading.remove wire:target="addToCart" class="whitespace-nowrap text-sm font-black">Añadir</span>
                    <span wire:loading wire:target="addToCart" class="whitespace-nowrap text-sm font-black">...</span>
                @else
                    <span wire:loading.remove wire:target="addToCart" class="whitespace-nowrap text-sm sm:text-base">Añadir al Carrito</span>
                    <span wire:loading wire:target="addToCart" class="whitespace-nowrap text-sm sm:text-base">Añadiendo...</span>
                @endif
            </button>
        </div>
    @else
        <button disabled class="w-full py-3.5 px-4 bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400 rounded-xl font-bold tracking-wide cursor-not-allowed">
            Sin Stock
        </button>
    @endif
</div>
