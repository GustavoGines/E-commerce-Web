<?php

use Livewire\Volt\Component;
use Livewire\Attributes\On;

new class extends Component {
    public $count = 0;

    public function mount()
    {
        $this->updateCount();
    }

    #[On('cart-updated')]
    public function updateCount()
    {
        $cartService = app(\App\Services\CartService::class);
        $cart = $cartService->getCartItemsArray();
        $this->count = array_sum($cart);
    }
}; ?>

<button @click="$dispatch('open-cart')" class="relative p-2 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors focus:outline-none shadow-sm flex items-center justify-center">
    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
    </svg>
    @if($count > 0)
        <span class="absolute -top-1 -right-1 flex items-center justify-center min-w-[20px] h-5 px-1 text-[10px] font-bold text-white rounded-full shadow-lg" style="background-color: var(--color-primary); box-shadow: 0 2px 5px var(--color-primary-glow);">
            {{ $count }}
        </span>
    @endif
</button>
