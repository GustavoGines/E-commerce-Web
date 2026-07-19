<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    // $orders NO es propiedad pública — se obtiene via computed + paginate()

    public function mount()
    {
        // nada que hacer aquí, loadOrders era el patrón viejo
    }

    public function with(): array
    {
        // PERF-02: Solo carga 30 órdenes por página en lugar de toda la tabla
        return [
            'orders' => Order::with(['user', 'items.product'])
                ->orderBy('created_at', 'desc')
                ->paginate(30)
        ];
    }

    public function loadOrders()
    {
        $this->resetPage();
    }

    public function updateStatus($orderId, $status)
    {
        $order = Order::with('items.product')->find($orderId);
        if ($order) {
            $oldStatus = $order->status;
            $order->status = $status;
            $order->updated_by = auth()->id();
            $order->status_updated_at = now();
            $order->save();

            // Si se marca como pagado/completado manualmente (WhatsApp o pago externo)
            if ($oldStatus === 'pendiente' && in_array($status, ['pagado', 'completado'])) {
                if ($order->user) {
                    // Lógica para convertir a mayorista de por vida si compra 10+ de algo
                    $shouldUpgradeToWholesale = false;
                    foreach ($order->items as $item) {
                        if ($item->quantity >= 10) {
                            $shouldUpgradeToWholesale = true;
                            break;
                        }
                    }

                    if ($shouldUpgradeToWholesale && $order->user->role !== 'mayorista') {
                        $order->user->role = 'mayorista';
                        $order->user->save();
                        // Opcional: enviar correo avisando que ahora es mayorista VIP
                    }

                    if ($order->user->email) {
                        \Illuminate\Support\Facades\Mail::to($order->user->email)->queue(new \App\Mail\OrderPaid($order));
                    }
                }
            }

            // BUG-03 FIX: Restaurar stock si la orden se cancela manualmente desde el panel
            if ($status === 'cancelado' && $oldStatus !== 'cancelado') {
                foreach ($order->items as $item) {
                    if ($item->product) {
                        $item->product->increment('stock', $item->quantity);
                    }
                }
            }

            $this->loadOrders();
            $this->dispatch('notify', message: 'Estado actualizado correctamente');
        }
    }

    public function deleteOrder($orderId)
    {
        $order = Order::with('items.product')->find($orderId);
        if ($order) {
            DB::transaction(function () use ($order) {
                // BUG-06 FIX: Restore stock before deleting, regardless of order status.
                // This prevents permanent inventory loss when admins remove paid/completed orders.
                foreach ($order->items as $item) {
                    if ($item->product) {
                        $item->product->increment('stock', $item->quantity);
                    }
                }
                $order->items()->delete();
                $order->delete();
            });
            $this->loadOrders();
            $this->dispatch('notify', message: 'Orden eliminada y stock restaurado correctamente');
        }
    }
}; ?>

<div x-data="{ openFilter: false }">
    <x-slot name="header">
        <div class="flex items-center space-x-6">
            <a href="{{ route('admin.orders') }}" wire:navigate class="font-semibold text-xl text-[var(--color-primary)] leading-tight">
                {{ __('Todas las Órdenes') }}
            </a>
            <span class="text-gray-300 dark:text-gray-700">|</span>
            <a href="{{ route('my-orders') }}" wire:navigate class="font-semibold text-xl text-gray-500 hover:text-gray-900 dark:hover:text-gray-100 transition-colors leading-tight">
                {{ __('Mis Compras') }}
            </a>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
        <div class="bg-white/80 dark:bg-gray-800/40 backdrop-blur-md border border-gray-200 dark:border-gray-700/50 shadow-xl dark:shadow-2xl sm:rounded-3xl p-6 transition-colors duration-300">
            <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 tracking-tight mb-6">Todas las Órdenes</h3>
            
            <div class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-700/50 shadow-sm transition-colors hidden md:block">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700/50 text-left">
                    <thead class="bg-gray-50 dark:bg-gray-900/50 transition-colors">
                        <tr>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Orden ID</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cliente</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Monto</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estado</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fecha</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Acción</th>
                        </tr>
                    </thead>
                    {{-- Retiramos el tbody exterior para evitar HTML inválido (anidamiento de tbody) --}}
                        @forelse($orders as $order)
                        <tbody x-data="{ expanded: false }" class="bg-white dark:bg-transparent divide-y divide-gray-200 dark:divide-gray-700/50 transition-colors">
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white cursor-pointer select-none" @click="expanded = !expanded">
                                    #{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}
                                    <svg class="inline-block w-4 h-4 ml-1 transform transition-transform duration-200" :class="{'rotate-180': expanded}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $order->user->name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $order->user->email }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-[var(--color-primary)]">
                                    ${{ number_format($order->total, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wide
                                        @if($order->status === 'pendiente') bg-yellow-100 text-yellow-800 dark:bg-yellow-500/20 dark:text-yellow-400
                                        @elseif($order->status === 'pagado') bg-blue-100 text-blue-800 dark:bg-blue-500/20 dark:text-blue-400
                                        @elseif($order->status === 'completado') bg-green-100 text-green-800 dark:bg-green-500/20 dark:text-green-400
                                        @elseif($order->status === 'cancelado') bg-red-100 text-red-800 dark:bg-red-500/20 dark:text-red-400
                                        @endif
                                    ">
                                        {{ $order->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $order->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <select wire:key="select-{{ $order->id }}-{{ $order->status }}" wire:change="updateStatus({{ $order->id }}, $event.target.value)" class="text-xs py-1.5 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white focus:ring-[var(--color-primary)] focus:border-transparent transition-colors">
                                            <option value="pendiente" {{ $order->status === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                                            <option value="pagado" {{ $order->status === 'pagado' ? 'selected' : '' }}>Pagado</option>
                                            <option value="completado" {{ $order->status === 'completado' ? 'selected' : '' }}>Completado</option>
                                            <option value="cancelado" {{ $order->status === 'cancelado' ? 'selected' : '' }}>Cancelado</option>
                                        </select>
                                        
                                        <button wire:click="deleteOrder({{ $order->id }})" wire:confirm="¿Seguro que deseas eliminar esta orden del sistema?" class="text-red-500 hover:text-red-700 dark:hover:text-red-400 transition-colors p-1" title="Eliminar Orden">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            {{-- Fila expandible de detalles --}}
                            <tr x-show="expanded" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-cloak>
                                <td colspan="6" class="px-6 py-5 bg-gray-50/80 dark:bg-gray-900/30">
                                    <div class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                        Detalles de la Orden 
                                        @if($order->role_applied)
                                            <span class="text-[10px] px-2 py-0.5 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-full normal-case font-semibold">Rol: {{ ucwords(str_replace('_', ' ', $order->role_applied)) }}</span>
                                        @endif
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                        {{-- Forma de entrega --}}
                                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-3">
                                            <h4 class="text-xs font-bold text-gray-400 uppercase mb-2">Forma de Entrega</h4>
                                            <div class="text-sm font-medium text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                </svg>
                                                {{ $order->delivery_method === 'envio' ? 'Envío a domicilio' : 'Retiro en Local' }}
                                            </div>
                                            @if($order->delivery_address)
                                                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $order->delivery_address }}</div>
                                            @endif
                                        </div>

                                        {{-- Contacto --}}
                                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-3">
                                            <h4 class="text-xs font-bold text-gray-400 uppercase mb-2">Contacto</h4>
                                            <div class="text-sm text-gray-700 dark:text-gray-300">
                                                @if($order->phone && $order->phone !== '-')
                                                    <div class="font-medium mb-2">{{ $order->phone }}</div>
                                                    @php
                                                        $cleanPhone = preg_replace('/[^0-9]/', '', $order->phone);
                                                        $waMessage = urlencode("Hola {$order->user->name}, te escribo de JCG Electrónica por tu orden #".str_pad($order->id, 5, '0', STR_PAD_LEFT).".");
                                                    @endphp
                                                    <a href="https://wa.me/{{ $cleanPhone }}?text={{ $waMessage }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-500 hover:bg-green-600 text-white rounded-md text-xs font-bold transition-colors">
                                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86s.274.072.376-.043c.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564c.173.087.289.129.332.202.043.073.043.423-.101.827z"></path></svg>
                                                        Abrir WhatsApp
                                                    </a>
                                                @else
                                                    <span class="text-gray-500 italic text-xs">No especificado</span>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- El bloque de MercadoPago fue removido temporalmente --}}
                                    </div>

                                    {{-- Productos --}}
                                    <ul class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
                                        <li class="px-4 py-2 bg-gray-50 dark:bg-gray-900/50 flex justify-between text-xs font-bold text-gray-500 uppercase tracking-wider">
                                            <span>Producto</span>
                                            <span>Cant. × Precio = Subtotal</span>
                                        </li>
                                        @foreach($order->items as $item)
                                            <li class="px-4 py-3 flex justify-between items-center text-sm">
                                                <div class="font-medium text-gray-900 dark:text-white">{{ $item->product ? $item->product->name : '⚠ Producto Eliminado' }}</div>
                                                <div class="text-gray-500 dark:text-gray-400 text-right">
                                                    {{ $item->quantity }} × ${{ number_format($item->price, 2) }} = <span class="font-bold text-gray-900 dark:text-white">${{ number_format($item->quantity * $item->price, 2) }}</span>
                                                </div>
                                            </li>
                                        @endforeach
                                        <li class="px-4 py-3 flex justify-between items-center bg-gray-50 dark:bg-gray-900/30 font-bold text-sm">
                                            <span class="text-gray-700 dark:text-gray-300">TOTAL</span>
                                            <span class="text-[var(--color-primary)]">${{ number_format($order->total, 2) }}</span>
                                        </li>
                                    </ul>
                                </td>
                            </tr>
                        </tbody>
                        @empty
                        <tbody class="bg-white dark:bg-transparent">
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">No hay órdenes registradas.</td>
                            </tr>
                        </tbody>
                        @endforelse
                </table>
            </div>

            <!-- Vista Móvil para Órdenes (Tarjetas) -->
            <div class="block md:hidden space-y-4 mt-6">
                @forelse($orders as $order)
                <div x-data="{ expanded: false }" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm relative transition-all" :class="expanded ? 'ring-2 ring-[var(--color-primary)]' : ''">
                    <!-- Cabecera Tarjeta -->
                    <div class="flex justify-between items-start mb-3 cursor-pointer" @click="expanded = !expanded">
                        <div class="flex flex-col">
                            <span class="text-sm font-black text-gray-900 dark:text-white flex items-center gap-1">
                                #{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}
                                <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="expanded ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </span>
                            <span class="text-xs text-gray-500">{{ $order->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div @click.stop>
                            <select wire:key="select-mob-{{ $order->id }}-{{ $order->status }}" wire:change="updateStatus({{ $order->id }}, $event.target.value)" class="text-[10px] uppercase font-bold py-1 px-2 border-0 rounded-md ring-1 ring-inset focus:ring-2 focus:ring-inset focus:ring-[var(--color-primary)] transition-colors cursor-pointer outline-none
                                @if($order->status === 'pendiente') bg-yellow-50 text-yellow-800 ring-yellow-600/20 dark:bg-yellow-500/10 dark:text-yellow-500 dark:ring-yellow-500/20
                                @elseif($order->status === 'pagado') bg-blue-50 text-blue-800 ring-blue-600/20 dark:bg-blue-500/10 dark:text-blue-500 dark:ring-blue-500/20
                                @elseif($order->status === 'completado') bg-green-50 text-green-800 ring-green-600/20 dark:bg-green-500/10 dark:text-green-500 dark:ring-green-500/20
                                @elseif($order->status === 'cancelado') bg-red-50 text-red-800 ring-red-600/20 dark:bg-red-500/10 dark:text-red-500 dark:ring-red-500/20
                                @endif">
                                <option value="pendiente" {{ $order->status === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                                <option value="pagado" {{ $order->status === 'pagado' ? 'selected' : '' }}>Pagado</option>
                                <option value="completado" {{ $order->status === 'completado' ? 'selected' : '' }}>Completado</option>
                                <option value="cancelado" {{ $order->status === 'cancelado' ? 'selected' : '' }}>Cancelado</option>
                            </select>
                        </div>
                    </div>

                    <!-- Info Cliente -->
                    <div class="mb-4">
                        <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $order->user->name }}</div>
                        <div class="text-xs text-gray-500">{{ $order->user->email }}</div>
                    </div>

                    <!-- Total y Acciones -->
                    <div class="flex justify-between items-center border-t border-gray-100 dark:border-gray-700/50 pt-3">
                        <div class="text-lg font-black text-[var(--color-primary)]">
                            ${{ number_format($order->total, 2) }}
                        </div>
                        <button wire:click="deleteOrder({{ $order->id }})" wire:confirm="¿Seguro que querés eliminar esta orden?" class="text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/40 border border-red-200 dark:border-red-900/50 py-1.5 px-3 rounded-lg transition-colors flex items-center gap-1 text-[10px] font-bold uppercase">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Borrar
                        </button>
                    </div>

                    <!-- Detalles Expandibles (Mobile) -->
                    <div x-show="expanded" x-transition x-cloak class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700/50">
                        <div class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Detalles de la Orden</div>
                        
                        <div class="space-y-3 mb-4">
                            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-3 border border-gray-100 dark:border-gray-700/50">
                                <div class="text-[10px] font-bold text-gray-400 uppercase mb-1">Forma de Entrega</div>
                                <div class="text-xs font-bold text-emerald-600 dark:text-emerald-400">{{ $order->delivery_method === 'envio' ? 'Envío a domicilio' : 'Retiro en Local' }}</div>
                                @if($order->delivery_address)
                                    <div class="mt-1 text-xs text-gray-500">{{ $order->delivery_address }}</div>
                                @endif
                            </div>
                            
                            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-3 border border-gray-100 dark:border-gray-700/50">
                                <div class="text-[10px] font-bold text-gray-400 uppercase mb-1">Contacto</div>
                                @if($order->phone && $order->phone !== '-')
                                    <div class="text-xs font-bold mb-2">{{ $order->phone }}</div>
                                    @php
                                        $cleanPhone = preg_replace('/[^0-9]/', '', $order->phone);
                                        $waMessage = urlencode("Hola {$order->user->name}, te escribo por tu orden #".str_pad($order->id, 5, '0', STR_PAD_LEFT).".");
                                    @endphp
                                    <a href="https://wa.me/{{ $cleanPhone }}?text={{ $waMessage }}" target="_blank" rel="noopener noreferrer" class="inline-flex w-full justify-center items-center gap-1.5 px-3 py-1.5 bg-green-500 text-white rounded-lg text-xs font-bold">
                                        WhatsApp
                                    </a>
                                @else
                                    <div class="text-xs text-gray-500 italic">No especificado</div>
                                @endif
                            </div>
                        </div>

                        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Productos</div>
                        <ul class="divide-y divide-gray-100 dark:divide-gray-700/50">
                            @foreach($order->items as $item)
                                <li class="py-2 flex justify-between gap-2">
                                    <div class="text-xs font-medium">{{ $item->product ? $item->product->name : '⚠ Producto Eliminado' }}</div>
                                    <div class="text-xs whitespace-nowrap text-right">
                                        {{ $item->quantity }} × ${{ number_format($item->price, 2) }}
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @empty
                    <div class="text-center py-8 text-gray-500 text-sm">No hay órdenes registradas.</div>
                @endforelse
            </div>
        </div>

        {{-- Paginación PERF-02 --}}
        @if($orders->hasPages())
            <div class="mt-4 px-2 sm:px-6 py-4 flex flex-col sm:flex-row items-center justify-between text-sm text-gray-500 dark:text-gray-400 gap-4 sm:gap-0">
                <div class="text-center sm:text-left">
                Mostrando {{ $orders->firstItem() }}–{{ $orders->lastItem() }} 
                de <span class="font-bold">{{ $orders->total() }}</span> órdenes
                </div>
                <div class="flex items-center gap-2">
                @if($orders->onFirstPage())
                    <button disabled class="px-3 py-1.5 bg-gray-100 dark:bg-gray-800 text-gray-400 border border-gray-200 dark:border-gray-700 rounded-md cursor-not-allowed whitespace-nowrap">Anterior</button>
                @else
                    <button wire:click="previousPage" class="px-3 py-1.5 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors whitespace-nowrap">Anterior</button>
                @endif
                <span class="px-2 font-bold whitespace-nowrap">
                    {{ $orders->currentPage() }} / {{ $orders->lastPage() }}
                </span>
                @if($orders->hasMorePages())
                    <button wire:click="nextPage" class="px-3 py-1.5 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors whitespace-nowrap">Siguiente</button>
                @else
                    <button disabled class="px-3 py-1.5 bg-gray-100 dark:bg-gray-800 text-gray-400 border border-gray-200 dark:border-gray-700 rounded-md cursor-not-allowed whitespace-nowrap">Siguiente</button>
                @endif
                </div>
            </div>
        @endif
    </div>
</div>
