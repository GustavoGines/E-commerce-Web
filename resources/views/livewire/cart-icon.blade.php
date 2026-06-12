<?php

use Livewire\Volt\Component;
use Livewire\Attributes\On;

new class extends Component {
    public $count = 0;
    public $theme = 'stealth';

    public function mount()
    {
        $settings = \App\Models\StoreSetting::getSettings();
        if ($settings) {
            $this->theme = $settings->theme_name ?? 'stealth';
        }
        $this->updateCount();
    }

    #[On('cart-updated')]
    #[On('cart-badge-updated')]
    public function updateCount()
    {
        $cartService = app(\App\Services\CartService::class);
        $cart = $cartService->getCartItemsArray();
        $this->count = array_sum($cart);
    }
}; ?>

<button onclick="POS.openCart()"
        class="relative p-2.5 rounded-xl
               {{ $theme === 'luxury' ? 'bg-[#0a0f1c] border border-white/5 text-gray-400 hover:bg-white/5 hover:text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white' }}
               transition-all focus:outline-none
               flex items-center justify-center"
        aria-label="Abrir carrito">
    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
    </svg>
    @if($count > 0)
        <span class="absolute -top-1 -right-1 flex items-center justify-center min-w-[20px] h-5 px-1
                     text-[10px] font-bold text-white rounded-full shadow-lg"
              style="background-color: var(--color-primary); box-shadow: 0 2px 8px var(--color-primary-glow);">
            {{ $count }}
        </span>
    @endif
</button>
