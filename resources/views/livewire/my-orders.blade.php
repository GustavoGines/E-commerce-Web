<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Order;

new #[Layout('layouts.app')] class extends Component {
    public $orders;

    public function mount()
    {
        $this->orders = Order::where('user_id', auth()->id())
            ->with('items.product')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-900 dark:text-gray-100 leading-tight">
            {{ __('Mis Órdenes') }}
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
        @if (session()->has('success'))
            <div class="mb-6 bg-green-100 dark:bg-green-500/20 border border-green-400 dark:border-green-500/30 text-green-700 dark:text-green-400 px-4 py-3 rounded relative backdrop-blur-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="space-y-6">
            @forelse($orders as $order)
                <div class="bg-white/80 dark:bg-gray-800/40 backdrop-blur-md border border-gray-200 dark:border-gray-700/50 shadow-sm sm:rounded-3xl p-6 transition-colors duration-300 overflow-hidden">
                    <div class="flex justify-between items-center mb-4 border-b border-gray-200 dark:border-gray-700 pb-4">
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 block mb-1">Orden #{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}</span>
                            <span class="text-sm text-gray-900 dark:text-white">{{ $order->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide
                                @if($order->status === 'pendiente') bg-yellow-100 text-yellow-800 dark:bg-yellow-500/20 dark:text-yellow-400
                                @elseif($order->status === 'pagado') bg-blue-100 text-blue-800 dark:bg-blue-500/20 dark:text-blue-400
                                @elseif($order->status === 'completado') bg-green-100 text-green-800 dark:bg-green-500/20 dark:text-green-400
                                @endif
                            ">
                                {{ $order->status }}
                            </span>
                            <div class="mt-2 text-xl font-black text-gray-900 dark:text-white">${{ number_format($order->total, 2) }}</div>
                        </div>
                    </div>
                    
                    <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($order->items as $item)
                            <li class="py-3 flex justify-between items-center">
                                <div class="flex items-center space-x-3">
                                    <div class="h-10 w-10 flex-shrink-0 overflow-hidden rounded bg-gray-100 dark:bg-gray-800">
                                        @if($item->product && $item->product->image_url)
                                            <img src="{{ asset('storage/' . $item->product->image_url) }}" class="h-full w-full object-cover">
                                        @endif
                                    </div>
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-200">{{ $item->product ? $item->product->name : 'Producto Eliminado' }}</span>
                                </div>
                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $item->quantity }} x ${{ number_format($item->price, 2) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @empty
                <div class="text-center py-20 bg-white/50 dark:bg-gray-800/20 backdrop-blur-sm border border-gray-200 dark:border-gray-700/50 rounded-3xl">
                    <svg class="mx-auto h-16 w-16 text-gray-300 dark:text-gray-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Aún no tienes órdenes</h3>
                    <p class="mt-1 text-gray-500 dark:text-gray-400">Cuando realices compras, aparecerán aquí.</p>
                    <a href="{{ route('home') }}" wire:navigate class="mt-6 inline-flex items-center justify-center px-6 py-3 border border-transparent rounded-full shadow-sm text-sm font-medium text-white transition-all hover:opacity-90" style="background-color: var(--color-primary); box-shadow: 0 4px 14px 0 var(--color-primary-glow);">
                        Ir a la tienda
                    </a>
                </div>
            @endforelse
        </div>
    </div>
</div>
