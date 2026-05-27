<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

new #[Layout('layouts.app')] class extends Component {
    public $cart = [];
    public $products = [];
    public $subtotal = 0;
    
    public $shipping_address = '';

    public function mount()
    {
        $this->cart = session()->get('cart', []);
        
        if (empty($this->cart)) {
            return redirect()->route('home');
        }

        $this->products = Product::whereIn('id', array_keys($this->cart))->get()->keyBy('id');
        $this->calculateSubtotal();
    }

    public function calculateSubtotal()
    {
        $this->subtotal = 0;
        $isMayorista = auth()->user()->role === 'mayorista';

        foreach ($this->cart as $productId => $quantity) {
            if (isset($this->products[$productId])) {
                $product = $this->products[$productId];
                $price = $isMayorista ? $product->wholesale_price : $product->retail_price;
                $this->subtotal += $price * $quantity;
            }
        }
    }

    public function placeOrder()
    {
        $this->validate([
            'shipping_address' => 'required|string|min:5|max:500'
        ]);

        if (empty($this->cart)) {
            return;
        }

        DB::beginTransaction();

        try {
            $isMayorista = auth()->user()->role === 'mayorista';

            // Create Order
            $order = Order::create([
                'user_id' => auth()->id(),
                'status' => 'pendiente',
                'total' => $this->subtotal,
                'shipping_address' => $this->shipping_address,
                'role_applied' => $isMayorista ? 'mayorista' : 'minorista',
            ]);

            // Create Items & Deduct Stock
            foreach ($this->cart as $productId => $quantity) {
                if (isset($this->products[$productId])) {
                    $product = $this->products[$productId];
                    
                    // Stock verification
                    if ($product->stock < $quantity) {
                        throw new \Exception("Sin stock suficiente para: " . $product->name);
                    }

                    $price = $isMayorista ? $product->wholesale_price : $product->retail_price;
                    
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'price' => $price
                    ]);

                    // Deduct Stock
                    $product->decrement('stock', $quantity);
                }
            }

            DB::commit();

            // Clear Cart
            session()->forget('cart');
            $this->dispatch('cart-updated');

            session()->flash('success', '¡Orden realizada con éxito! Nos contactaremos a la brevedad para coordinar el pago.');
            return redirect()->route('my-orders');

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', $e->getMessage());
        }
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-900 dark:text-gray-100 leading-tight">
            {{ __('Checkout: Confirmar Reserva') }}
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
        @if (session()->has('error'))
            <div class="mb-6 bg-red-100 dark:bg-red-500/20 border border-red-400 dark:border-red-500/30 text-red-700 dark:text-red-400 px-4 py-3 rounded relative backdrop-blur-sm">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Order Summary -->
            <div class="bg-white/80 dark:bg-gray-800/40 backdrop-blur-md border border-gray-200 dark:border-gray-700/50 shadow-xl dark:shadow-2xl sm:rounded-3xl p-8">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Resumen de tu Orden</h3>
                <ul class="divide-y divide-gray-200 dark:divide-gray-700/50">
                    @foreach($cart as $productId => $quantity)
                        @if(isset($products[$productId]))
                            @php
                                $product = $products[$productId];
                                $price = (auth()->user()->role === 'mayorista') ? $product->wholesale_price : $product->retail_price;
                            @endphp
                            <li class="py-4 flex justify-between items-center">
                                <div class="flex items-center">
                                    <div class="h-12 w-12 flex-shrink-0 overflow-hidden rounded-md border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 mr-4">
                                        @if($product->image_url)
                                            <img src="{{ asset('storage/' . $product->image_url) }}" class="h-full w-full object-cover">
                                        @endif
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-900 dark:text-white">{{ $product->name }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $quantity }} x ${{ number_format($price, 2) }}</p>
                                    </div>
                                </div>
                                <div class="font-bold text-[var(--color-primary)]">
                                    ${{ number_format($price * $quantity, 2) }}
                                </div>
                            </li>
                        @endif
                    @endforeach
                </ul>
                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700/50 flex justify-between items-center">
                    <span class="text-xl font-bold text-gray-900 dark:text-white">Total a Pagar</span>
                    <span class="text-3xl font-black text-gray-900 dark:text-white">${{ number_format($subtotal, 2) }}</span>
                </div>
            </div>

            <!-- Shipping Form -->
            <div class="bg-white/80 dark:bg-gray-800/40 backdrop-blur-md border border-gray-200 dark:border-gray-700/50 shadow-xl dark:shadow-2xl sm:rounded-3xl p-8" :style="darkMode ? 'box-shadow: 0 10px 30px -10px var(--color-primary-glow);' : ''">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Detalles de Envío</h3>
                <form wire:submit="placeOrder">
                    <div class="mb-6">
                        <label class="block text-gray-700 dark:text-gray-400 text-xs font-bold mb-2 uppercase tracking-wider">Dirección Completa y Teléfono</label>
                        <textarea wire:model="shipping_address" rows="4" placeholder="Ej: Av. Siempre Viva 123, Ciudad. Tel: 555-1234" class="w-full py-3 px-4 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent transition-all shadow-sm dark:shadow-none"></textarea>
                        @error('shipping_address') <span class="text-red-500 dark:text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800/50 rounded-xl p-4 mb-8">
                        <p class="text-sm text-blue-800 dark:text-blue-300">
                            <strong>Nota:</strong> Al confirmar, generarás una solicitud de reserva. Nos pondremos en contacto contigo para coordinar el pago (Transferencia o Efectivo) y el envío.
                        </p>
                    </div>

                    <button type="submit" wire:loading.attr="disabled" class="w-full flex items-center justify-center py-4 px-8 rounded-full text-white font-bold text-lg tracking-wide transition-all shadow-lg hover:shadow-xl hover:-translate-y-0.5 disabled:opacity-70 disabled:cursor-not-allowed group" style="background-color: var(--color-primary); box-shadow: 0 4px 14px 0 var(--color-primary-glow);">
                        <span wire:loading.remove wire:target="placeOrder">Confirmar Reserva</span>
                        <span wire:loading wire:target="placeOrder">Procesando...</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
