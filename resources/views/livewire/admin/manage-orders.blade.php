<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Order;

new #[Layout('layouts.app')] class extends Component {
    public $orders;

    public function mount()
    {
        $this->loadOrders();
    }

    public function loadOrders()
    {
        $this->orders = Order::with(['user', 'items.product'])->orderBy('created_at', 'desc')->get();
    }

    public function updateStatus($orderId, $status)
    {
        $order = Order::find($orderId);
        if ($order) {
            $order->status = $status;
            $order->save();
            $this->loadOrders();
            $this->dispatch('notify', message: 'Estado actualizado correctamente');
        }
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-900 dark:text-gray-100 leading-tight">
            {{ __('Gestión de Órdenes') }}
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
        <div class="bg-white/80 dark:bg-gray-800/40 backdrop-blur-md border border-gray-200 dark:border-gray-700/50 shadow-xl dark:shadow-2xl sm:rounded-3xl p-6 transition-colors duration-300">
            <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 tracking-tight mb-6">Todas las Órdenes</h3>
            
            <div class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-700/50 shadow-sm transition-colors">
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
                    <tbody class="bg-white dark:bg-transparent divide-y divide-gray-200 dark:divide-gray-700/50 transition-colors">
                        @forelse($orders as $order)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-colors" x-data="{ expanded: false }">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white cursor-pointer" @click="expanded = !expanded">
                                #{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}
                                <svg class="inline-block w-4 h-4 ml-1 transform transition-transform" :class="{'rotate-180': expanded}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                                    @endif
                                ">
                                    {{ $order->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $order->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-right">
                                <select wire:change="updateStatus({{ $order->id }}, $event.target.value)" class="text-xs py-1.5 px-3 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white focus:ring-[var(--color-primary)] focus:border-transparent transition-colors">
                                    <option value="pendiente" {{ $order->status === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                                    <option value="pagado" {{ $order->status === 'pagado' ? 'selected' : '' }}>Pagado</option>
                                    <option value="completado" {{ $order->status === 'completado' ? 'selected' : '' }}>Completado</option>
                                </select>
                            </td>
                            
                            <!-- Items Dropdown (Alpine) -->
                            <tr x-show="expanded" class="bg-gray-50/50 dark:bg-gray-800/10" x-transition x-cloak>
                                <td colspan="6" class="px-6 py-4">
                                    <div class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Detalles de la Orden (Rol: {{ $order->role_applied }})</div>
                                    <div class="text-sm text-gray-700 dark:text-gray-300 mb-4"><strong>Dirección:</strong> {{ $order->shipping_address }}</div>
                                    <ul class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm">
                                        @foreach($order->items as $item)
                                            <li class="p-3 flex justify-between items-center text-sm">
                                                <div class="font-medium text-gray-900 dark:text-white">{{ $item->product ? $item->product->name : 'Producto Eliminado' }}</div>
                                                <div class="text-gray-500 dark:text-gray-400">{{ $item->quantity }} un. x ${{ number_format($item->price, 2) }} = <span class="font-bold text-gray-900 dark:text-white">${{ number_format($item->quantity * $item->price, 2) }}</span></div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </td>
                            </tr>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">No hay órdenes registradas.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
