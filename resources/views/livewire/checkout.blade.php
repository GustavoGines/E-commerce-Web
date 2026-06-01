<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\MercadoPagoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

new #[Layout('layouts.app')] class extends Component {
    public $cart = [];
    public $products = [];
    public $subtotal = 0;
    
    public $address_street = '';
    public $address_number = '';
    public $city = '';
    public $state = '';
    public $zip_code = '';
    public $phone = '';

    public function mount()
    {
        $cartService = app(\App\Services\CartService::class);
        $this->cart = $cartService->getCartItemsArray();
        
        if (empty($this->cart)) {
            return redirect()->route('home');
        }

        $this->products = Product::whereIn('id', array_keys($this->cart))->get()->keyBy('id');
        $this->calculateSubtotal();
    }

    public function calculateSubtotal()
    {
        $this->subtotal = 0;

        foreach ($this->cart as $productId => $quantity) {
            if (isset($this->products[$productId])) {
                $product = $this->products[$productId];
                $price = ($quantity >= $product->wholesale_min_quantity) ? $product->wholesale_price : $product->retail_price;
                $this->subtotal += $price * $quantity;
            }
        }
    }

    public function updateQuantity($productId, $action)
    {
        $cartService = app(\App\Services\CartService::class);
        $cartService->updateQuantity($productId, $action);
        $this->cart = $cartService->getCartItemsArray();
        
        if (empty($this->cart)) {
            return redirect()->route('home');
        }

        $this->calculateSubtotal();
        $this->dispatch('cart-updated');
    }

    public function removeItem($productId)
    {
        $cartService = app(\App\Services\CartService::class);
        $cartService->removeItem($productId);
        $this->cart = $cartService->getCartItemsArray();
        
        if (empty($this->cart)) {
            return redirect()->route('home');
        }

        $this->calculateSubtotal();
        $this->dispatch('cart-updated');
    }

    public function placeOrder()
    {
        $this->validate([
            'address_street' => 'required|string|max:255',
            'address_number' => 'required|string|max:50',
            'city'           => 'required|string|max:255',
            'state'          => 'required|string|max:255',
            'zip_code'       => 'required|string|max:20',
            'phone'          => 'required|string|min:8|max:25',
        ], [
            'phone.required' => 'El número de celular/WhatsApp es obligatorio para poder contactarte.'
        ]);

        if (empty($this->cart)) {
            return;
        }

        DB::beginTransaction();

        try {
            // 1. Crear la Orden en DB con estado 'pendiente'
            $order = Order::create([
                'user_id'        => auth()->id(),
                'status'         => 'pendiente',
                'total'          => $this->subtotal,
                'phone'          => $this->phone,
                'address_street' => $this->address_street,
                'address_number' => $this->address_number,
                'city'           => $this->city,
                'state'          => $this->state,
                'zip_code'       => $this->zip_code,
                'role_applied'   => 'por_volumen',
            ]);

            // 2. Crear Items y descontar stock
            foreach ($this->cart as $productId => $quantity) {
                if (isset($this->products[$productId])) {
                    $product = $this->products[$productId];
                    
                    if ($product->stock < $quantity) {
                        throw new \Exception("Sin stock suficiente para: " . $product->name);
                    }

                    $price = ($quantity >= $product->wholesale_min_quantity) ? $product->wholesale_price : $product->retail_price;
                    
                    OrderItem::create([
                        'order_id'   => $order->id,
                        'product_id' => $product->id,
                        'quantity'   => $quantity,
                        'price'      => $price,
                    ]);

                    $product->decrement('stock', $quantity);
                }
            }

            // 3. Generar Preferencia de Pago en MercadoPago
            $mpService  = app(MercadoPagoService::class);
            $preference = $mpService->createPreference($order, $this->cart);

            // 4. Guardar el preference_id en la orden
            $order->update(['mp_preference_id' => $preference['preference_id']]);

            DB::commit();

            // 5. Limpiar el carrito
            app(\App\Services\CartService::class)->clear();
            $this->dispatch('cart-updated');

            // 6. Redirigir a MercadoPago en la misma pestaña
            $redirectUrl = app()->isProduction()
                ? $preference['init_point']
                : $preference['sandbox_init_point'];

            return redirect()->away($redirectUrl);

        } catch (\MercadoPago\Exceptions\MPApiException $e) {
            DB::rollBack();
            Log::error('Error MP al crear preferencia en checkout', ['error' => $e->getMessage()]);
            session()->flash('error', 'No pudimos conectar con MercadoPago. Por favor intentá de nuevo en unos minutos.');
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
                                $price = ($quantity >= $product->wholesale_min_quantity) ? $product->wholesale_price : $product->retail_price;
                            @endphp
                            <li class="py-4 flex justify-between items-center">
                                <div class="flex items-center">
                                    <div class="h-12 w-12 flex-shrink-0 overflow-hidden rounded-md border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 mr-4">
                                        @if($product->image_url)
                                            <img src="{{ asset('storage/' . $product->image_url) }}" class="h-full w-full object-cover">
                                        @endif
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-900 dark:text-white line-clamp-2 text-sm">{{ $product->name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">${{ number_format($price, 2) }} c/u</p>
                                        <div class="flex items-center gap-3 mt-2">
                                            <div class="flex items-center border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800/50 p-1">
                                                <button wire:click.prevent="updateQuantity({{ $productId }}, 'decrement')" wire:loading.attr="disabled" type="button" class="w-6 h-6 flex items-center justify-center rounded-md bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 shadow-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4" /></svg>
                                                </button>
                                                <span class="w-8 text-center text-xs font-bold text-gray-900 dark:text-white">
                                                    <span wire:loading.remove wire:target="updateQuantity({{ $productId }}, 'decrement'), updateQuantity({{ $productId }}, 'increment')">{{ $quantity }}</span>
                                                    <span wire:loading wire:target="updateQuantity({{ $productId }}, 'decrement'), updateQuantity({{ $productId }}, 'increment')" class="inline-block animate-pulse w-2 h-2 bg-gray-400 rounded-full"></span>
                                                </span>
                                                <button wire:click.prevent="updateQuantity({{ $productId }}, 'increment')" wire:loading.attr="disabled" type="button" class="w-6 h-6 flex items-center justify-center rounded-md bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 shadow-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed" @if($quantity >= $product->stock) disabled @endif>
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                                                </button>
                                            </div>
                                            <button wire:click.prevent="removeItem({{ $productId }})" wire:loading.attr="disabled" type="button" class="text-xs text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 font-medium transition-colors inline-flex items-center gap-1 disabled:opacity-50">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                Eliminar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="font-bold text-gray-900 dark:text-white text-lg">
                                        ${{ number_format($price * $quantity, 2) }}
                                    </div>
                                    @if($quantity >= $product->wholesale_min_quantity)
                                        <span class="text-[9px] font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400 mt-1 block">Precio Mayorista</span>
                                    @endif
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
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-gray-700 dark:text-gray-400 text-xs font-bold mb-2 uppercase tracking-wider">Calle</label>
                            <input wire:model="address_street" type="text" class="w-full py-3 px-4 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent transition-all shadow-sm dark:shadow-none" placeholder="Av. Siempre Viva">
                            @error('address_street') <span class="text-red-500 dark:text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-gray-700 dark:text-gray-400 text-xs font-bold mb-2 uppercase tracking-wider">Número/Piso</label>
                            <input wire:model="address_number" type="text" class="w-full py-3 px-4 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent transition-all shadow-sm dark:shadow-none" placeholder="123 Piso 4">
                            @error('address_number') <span class="text-red-500 dark:text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div>
                            <label class="block text-gray-700 dark:text-gray-400 text-xs font-bold mb-2 uppercase tracking-wider">Ciudad</label>
                            <input wire:model="city" type="text" class="w-full py-3 px-4 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent transition-all shadow-sm dark:shadow-none" placeholder="Rosario">
                            @error('city') <span class="text-red-500 dark:text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-gray-700 dark:text-gray-400 text-xs font-bold mb-2 uppercase tracking-wider">Provincia</label>
                            <input wire:model="state" type="text" class="w-full py-3 px-4 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent transition-all shadow-sm dark:shadow-none" placeholder="Santa Fe">
                            @error('state') <span class="text-red-500 dark:text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-gray-700 dark:text-gray-400 text-xs font-bold mb-2 uppercase tracking-wider">C. Postal</label>
                            <input wire:model="zip_code" type="text" class="w-full py-3 px-4 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent transition-all shadow-sm dark:shadow-none" placeholder="2000">
                            @error('zip_code') <span class="text-red-500 dark:text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-gray-700 dark:text-gray-400 text-xs font-bold mb-2 uppercase tracking-wider">Número de Celular / WhatsApp</label>
                        <div class="flex">
                            <span class="inline-flex items-center px-4 rounded-l-xl border border-r-0 border-gray-300 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 font-bold">
                                📞
                            </span>
                            <input wire:model="phone" type="tel" class="w-full py-3 px-4 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-r-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent transition-all shadow-sm dark:shadow-none" placeholder="Ej: 11 5555-4444">
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Obligatorio para avisarte cuando despachemos tu pedido.</p>
                        @error('phone') <span class="text-red-500 dark:text-red-400 text-xs mt-1 block font-bold">{{ $message }}</span> @enderror
                    </div>
                    
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800/50 rounded-xl p-4 mb-8">
                        <p class="text-sm text-blue-800 dark:text-blue-300">
                            <strong>Pago seguro:</strong> Al confirmar, serás redirigido a MercadoPago para completar tu pago de forma segura.
                        </p>
                    </div>

                    <button type="submit"
                            wire:loading.attr="disabled"
                            class="w-full inline-flex items-center justify-center gap-2 py-4 px-8 rounded-full text-white font-bold text-lg tracking-wide transition-all shadow-lg hover:shadow-xl hover:-translate-y-0.5 disabled:opacity-70 disabled:cursor-not-allowed"
                            style="background-color: var(--color-primary); box-shadow: 0 4px 14px 0 var(--color-primary-glow);">

                        {{-- Ícono candado (estado normal) --}}
                        <svg wire:loading.remove wire:target="placeOrder"
                             class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>

                        {{-- Spinner (estado cargando) --}}
                        <svg wire:loading wire:target="placeOrder"
                             class="animate-spin w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>

                        {{-- Texto normal --}}
                        <span wire:loading.remove wire:target="placeOrder">Pagar con MercadoPago</span>

                        {{-- Texto cargando --}}
                        <span wire:loading wire:target="placeOrder">Redirigiendo a MercadoPago...</span>

                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
