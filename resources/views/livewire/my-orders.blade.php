<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public $filtro = 'todas';

    public function mount()
    {
        // ...
    }

    // We change cargarOrdenes to a computed property or just render method with pagination.
    // In Livewire 3/Volt, we can use #[Computed] or with(). Let's use with().
    public function with(): array
    {
        $query = Order::where('user_id', auth()->id())
            ->with('items.product')
            ->orderBy('created_at', 'desc');

        if ($this->filtro !== 'todas') {
            $query->where('status', $this->filtro);
        }

        return [
            'orders' => $query->paginate(10)
        ];
    }

    public function setFiltro($filtro)
    {
        $this->filtro = $filtro;
        $this->resetPage();
    }

    public function cancelarOrden($id)
    {
        $order = Order::where('user_id', auth()->id())
            ->with('items.product')
            ->find($id);

        if ($order && $order->status === 'pendiente') {
            DB::transaction(function () use ($order) {
                // BUG-05 FIX: Restore stock before cancelling.
                foreach ($order->items as $item) {
                    if ($item->product) {
                        $item->product->increment('stock', $item->quantity);
                    }
                }
                $order->update(['status' => 'cancelado']);
            });
        }
    }

    public function eliminarOrden($id)
    {
        $order = Order::where('user_id', auth()->id())
            ->with('items.product')
            ->find($id);

        if ($order && in_array($order->status, ['pendiente', 'cancelado'])) {
            DB::transaction(function () use ($order) {
                // BUG-05 FIX: Restaurar stock antes de eliminar.
                // Solo restaura si la orden estaba pendiente (canceladas ya restauraron el stock al cancelar).
                if ($order->status === 'pendiente') {
                    foreach ($order->items as $item) {
                        if ($item->product) {
                            $item->product->increment('stock', $item->quantity);
                        }
                    }
                }
                $order->items()->delete();
                $order->delete();
            });
        }
    }
}; ?>

<div>
    <x-slot name="header">
        @if(auth()->check() && auth()->user()->role === 'admin')
            <div class="flex items-center space-x-6">
                <a href="{{ route('admin.orders') }}" wire:navigate class="font-semibold text-xl text-gray-500 hover:text-gray-900 dark:hover:text-gray-100 transition-colors leading-tight">
                    {{ __('Todas las Órdenes') }}
                </a>
                <span class="text-gray-300 dark:text-gray-700">|</span>
                <a href="{{ route('my-orders') }}" wire:navigate class="font-semibold text-xl text-[var(--color-primary)] leading-tight">
                    {{ __('Mis Compras') }}
                </a>
            </div>
        @else
            <h2 class="font-semibold text-xl text-gray-900 dark:text-gray-100 leading-tight">
                Mis Compras
            </h2>
        @endif
    </x-slot>

    <div class="max-w-4xl mx-auto py-10 sm:px-6 lg:px-8">

        {{-- Encabezado con contador --}}
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-black text-gray-900 dark:text-white">Historial de Compras</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    {{ $orders->total() }} {{ $orders->total() === 1 ? 'orden encontrada' : 'órdenes encontradas' }}
                </p>
            </div>
            <a href="{{ route('home') }}" wire:navigate
               class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium text-white transition-all hover:opacity-90"
               style="background-color: var(--color-primary);">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
                Seguir comprando
            </a>
        </div>

        {{-- Filtros por estado --}}
        <div class="flex gap-2 mb-6 overflow-x-auto pb-2">
            @foreach(['todas' => 'Todas', 'pendiente' => '🟡 Pendiente', 'pagado' => '🔵 Pagado', 'completado' => '🟢 Completado', 'cancelado' => '🔴 Cancelado'] as $valor => $label)
                <button wire:click="setFiltro('{{ $valor }}')"
                        class="flex-shrink-0 px-4 py-2 rounded-full text-sm font-medium transition-all border
                            {{ $filtro === $valor
                                ? 'text-white border-transparent'
                                : 'text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-700 hover:border-gray-400 dark:hover:border-gray-500' }}"
                        style="{{ $filtro === $valor ? 'background-color: var(--color-primary); border-color: var(--color-primary);' : '' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- Lista de órdenes --}}
        <div class="space-y-4">
            @forelse($orders as $order)
                <div class="bg-white/80 dark:bg-gray-800/40 backdrop-blur-md border border-gray-200 dark:border-gray-700/50 shadow-sm sm:rounded-2xl overflow-hidden transition-all duration-300"
                     x-data="{ expandido: false }">

                    {{-- Cabecera clickeable --}}
                    <button @click="expandido = !expandido"
                            class="w-full text-left px-6 py-5 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/20 transition-colors">
                        <div class="flex items-center gap-4">
                            {{-- Ícono estado --}}
                            <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0
                                @if($order->status === 'pagado') bg-blue-100 dark:bg-blue-500/20
                                @elseif($order->status === 'completado') bg-green-100 dark:bg-green-500/20
                                @elseif($order->status === 'pendiente') bg-yellow-100 dark:bg-yellow-500/20
                                @else bg-red-100 dark:bg-red-500/20 @endif">
                                @if($order->status === 'pagado')
                                    <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                @elseif($order->status === 'completado')
                                    <svg class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                @elseif($order->status === 'pendiente')
                                    <svg class="w-5 h-5 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                @else
                                    <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                @endif
                            </div>

                            <div>
                                <div class="font-bold text-gray-900 dark:text-white">
                                    Orden #{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $order->created_at->format('d/m/Y') }} · {{ $order->items->count() }} {{ $order->items->count() === 1 ? 'producto' : 'productos' }}
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            {{-- Badge estado --}}
                            <span class="hidden sm:inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wide
                                @if($order->status === 'pendiente') bg-yellow-100 text-yellow-800 dark:bg-yellow-500/20 dark:text-yellow-400
                                @elseif($order->status === 'pagado') bg-blue-100 text-blue-800 dark:bg-blue-500/20 dark:text-blue-400
                                @elseif($order->status === 'completado') bg-green-100 text-green-800 dark:bg-green-500/20 dark:text-green-400
                                @else bg-red-100 text-red-800 dark:bg-red-500/20 dark:text-red-400 @endif">
                                {{ $order->status }}
                            </span>

                            <div class="text-right">
                                <div class="font-black text-gray-900 dark:text-white text-lg">${{ number_format($order->total, 2) }}</div>
                            </div>

                            {{-- Flecha acordeón --}}
                            <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="expandido ? 'rotate-180' : ''"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </button>

                    {{-- Detalle expandible --}}
                    <div x-show="expandido"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-2"
                         class="border-t border-gray-100 dark:border-gray-700/50">

                        <div class="px-6 py-5 space-y-5">

                            {{-- Productos --}}
                            <div>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-3">Productos</h3>
                                <ul class="space-y-3">
                                    @foreach($order->items as $item)
                                        <li class="flex items-center justify-between gap-3">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 overflow-hidden flex-shrink-0">
                                                    @if($item->product && $item->product->image_url)
                                                        <img src="{{ asset('storage/' . $item->product->image_url) }}" class="w-full h-full object-cover">
                                                    @endif
                                                </div>
                                                <div>
                                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                        {{ $item->product ? $item->product->name : 'Producto eliminado' }}
                                                    </p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $item->quantity }} x ${{ number_format($item->price, 2) }}</p>
                                                </div>
                                            </div>
                                            <span class="font-bold text-gray-900 dark:text-white text-sm">
                                                ${{ number_format($item->price * $item->quantity, 2) }}
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>

                            {{-- Entrega --}}
                            <div class="pt-3 border-t border-gray-100 dark:border-gray-800">
                                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-2">Forma de entrega</h3>
                                <p class="text-sm font-medium text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    {{ $order->delivery_label }}
                                </p>
                            </div>

                            {{-- Acciones --}}
                            <div class="pt-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                                @if($order->mp_payment_id)
                                    <p class="text-xs text-gray-500 dark:text-gray-600">
                                        Op. MP: <span class="font-mono">{{ $order->mp_payment_id }}</span>
                                    </p>
                                @else
                                    <span></span>
                                @endif

                                @if(in_array($order->status, ['pagado', 'completado']))
                                    <a href="{{ route('checkout.success', $order) }}"
                                       target="_blank"
                                       class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium text-white transition-all hover:opacity-90"
                                       style="background-color: var(--color-primary);">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                        </svg>
                                        Ver comprobante
                                    </a>
                                @elseif($order->status === 'pendiente' && $order->mp_preference_id)
                                    <div class="flex items-center gap-3 flex-wrap justify-end mt-4 sm:mt-0">
                                        @php
                                            $mpUrl = app()->isProduction() 
                                                ? "https://www.mercadopago.com.ar/checkout/v1/redirect?pref_id=" . $order->mp_preference_id
                                                : "https://sandbox.mercadopago.com.ar/checkout/v1/redirect?pref_id=" . $order->mp_preference_id;
                                        @endphp
                                        
                                        {{-- Cancelar --}}
                                        <button wire:click="cancelarOrden({{ $order->id }})"
                                                wire:confirm="¿Seguro que querés cancelar esta orden?"
                                                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold transition-all text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800">
                                            Cancelar
                                        </button>

                                        {{-- Pagar ahora (con style inline para asegurar que se vea sin Vite) --}}
                                        <a href="{{ $mpUrl }}"
                                           style="background-color: #facc15; color: #111827;"
                                           class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-bold transition-all shadow-sm hover:opacity-80">
                                            Pagar ahora
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                            </svg>
                                        </a>
                                    </div>
                                @endif

                                @if(in_array($order->status, ['pendiente', 'cancelado']))
                                    <button wire:click="eliminarOrden({{ $order->id }})"
                                            wire:confirm="¿Estás seguro de eliminar permanentemente esta orden del historial?"
                                            class="ml-3 inline-flex items-center gap-1 text-xs font-medium text-red-500 hover:text-red-700 transition-colors mt-4 sm:mt-0">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        Eliminar
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-24 bg-white/50 dark:bg-gray-800/20 backdrop-blur-sm border border-gray-200 dark:border-gray-700/50 rounded-3xl">
                    <svg class="mx-auto h-16 w-16 text-gray-300 dark:text-gray-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">
                        {{ $filtro === 'todas' ? 'Aún no tenés órdenes' : 'No hay órdenes con este estado' }}
                    </h3>
                    <p class="text-gray-500 dark:text-gray-400 text-sm mb-6">
                        {{ $filtro === 'todas' ? 'Cuando realices compras, aparecerán aquí.' : 'Probá cambiando el filtro.' }}
                    </p>
                    @if($filtro !== 'todas')
                        <button wire:click="setFiltro('todas')"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-medium border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-gray-500 transition-all">
                            Ver todas las órdenes
                        </button>
                    @else
                        <a href="{{ route('home') }}" wire:navigate
                           class="inline-flex items-center gap-2 px-6 py-3 rounded-full text-sm font-bold text-white transition-all hover:opacity-90"
                           style="background-color: var(--color-primary);">
                            Ir a la tienda
                        </a>
                    @endif
                </div>
            @endforelse
        </div>

        <div class="mt-6">
            {{ $orders->links() }}
        </div>
    </div>
</div>
