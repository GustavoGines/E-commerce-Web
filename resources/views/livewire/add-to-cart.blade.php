<?php

use Livewire\Volt\Component;

new class extends Component {
    public $product;
    public $quantity = 1;

    public function addToCart()
    {
        $cartService = app(\App\Services\CartService::class);
        
        if ($cartService->addItem($this->product->id, $this->quantity)) {
            $this->dispatch('cart-updated');
            $this->dispatch('notify', message: 'Producto añadido al carrito');
        } else {
            $this->dispatch('notify', message: 'No hay stock suficiente', type: 'error');
        }
    }
}; ?>

<div class="mt-4 w-full">
    @if($product->stock > 0)
        <button wire:click="addToCart" wire:loading.attr="disabled" class="w-full flex items-center justify-center py-2.5 px-4 rounded-xl text-white font-bold tracking-wide transition-all shadow-md hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-70 disabled:cursor-not-allowed group" style="background-color: var(--color-primary); box-shadow: 0 4px 10px -2px var(--color-primary-glow);">
            <svg wire:loading.remove wire:target="addToCart" class="w-5 h-5 mr-2 transition-transform group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <svg wire:loading wire:target="addToCart" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span wire:loading.remove wire:target="addToCart">Añadir al Carrito</span>
            <span wire:loading wire:target="addToCart">Añadiendo...</span>
        </button>
    @else
        <button disabled class="w-full py-2.5 px-4 bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400 rounded-xl font-bold tracking-wide cursor-not-allowed">
            Sin Stock
        </button>
    @endif
</div>
