<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Compra #{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }} — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; color: black !important; }
            .print-card { box-shadow: none !important; border: 1px solid #e5e7eb !important; }
        }
    </style>
</head>
<body class="bg-gray-950 min-h-screen py-10 px-4">

    {{-- Botones de acción (ocultos al imprimir) --}}
    <div class="no-print max-w-3xl mx-auto mb-6 flex items-center justify-between">
        <a href="{{ route('home') }}"
           class="inline-flex items-center gap-2 text-sm text-gray-400 hover:text-white transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Volver a la tienda
        </a>
        <div class="flex gap-3">
            <a href="{{ route('my-orders') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium text-gray-300 border border-gray-700 hover:border-gray-500 transition-all">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Mis órdenes
            </a>
            <button onclick="window.print()"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-bold text-white transition-all hover:opacity-90"
                    style="background-color: var(--color-primary);">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Imprimir / PDF
            </button>
        </div>
    </div>

    {{-- Comprobante --}}
    <div class="print-card max-w-3xl mx-auto bg-gray-900 rounded-3xl overflow-hidden shadow-2xl border border-gray-800">

        {{-- Header verde de éxito --}}
        <div class="bg-gradient-to-r from-green-600 to-emerald-500 p-8 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <span class="text-lg font-bold">¡Pago aprobado!</span>
                    </div>
                    <h1 class="text-3xl font-black">Comprobante de Compra</h1>
                    <p class="text-green-100 mt-1">Orden #{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}</p>
                </div>
                <div class="text-right">
                    <div class="text-4xl font-black">${{ number_format($order->total, 2) }}</div>
                    <div class="text-green-100 text-sm mt-1">Total pagado</div>
                </div>
            </div>
        </div>

        {{-- Cuerpo del comprobante --}}
        <div class="p-8">

            {{-- Datos del comprobante en grid --}}
            <div class="grid grid-cols-2 gap-6 mb-8 pb-8 border-b border-gray-800">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Fecha y hora</p>
                    <p class="text-white font-medium">{{ $order->created_at->format('d/m/Y \a\l\a\s H:i') }} hs</p>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Estado</p>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-green-500/20 text-green-400 border border-green-500/30">
                        ✓ {{ ucfirst($order->status) }}
                    </span>
                </div>
                @if($order->mp_payment_id)
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">N° Operación MP</p>
                    <p class="text-white font-mono text-sm">{{ $order->mp_payment_id }}</p>
                </div>
                @endif
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Tipo de compra</p>
                    <p class="text-white font-medium">{{ $order->role_applied === 'por_volumen' ? 'Descuento por Volumen' : 'Precio de Lista' }}</p>
                </div>
            </div>

            {{-- Datos del comprador --}}
            <div class="mb-8 pb-8 border-b border-gray-800">
                <h2 class="text-sm font-bold uppercase tracking-wider text-gray-400 mb-4">Datos del comprador</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-gray-500 mb-0.5">Nombre</p>
                        <p class="text-white font-medium">{{ $order->user->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-0.5">Email</p>
                        <p class="text-white font-medium">{{ $order->user->email }}</p>
                    </div>
                </div>
            </div>

            {{-- Dirección de envío --}}
            <div class="mb-8 pb-8 border-b border-gray-800">
                <h2 class="text-sm font-bold uppercase tracking-wider text-gray-400 mb-4">Dirección de envío</h2>
                <p class="text-white">
                    {{ $order->address_street }} {{ $order->address_number }},
                    {{ $order->city }}, {{ $order->state }} — CP {{ $order->zip_code }}
                </p>
            </div>

            {{-- Tabla de productos --}}
            <div class="mb-8">
                <h2 class="text-sm font-bold uppercase tracking-wider text-gray-400 mb-4">Productos</h2>
                <div class="rounded-2xl overflow-hidden border border-gray-800">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-800/60 text-xs font-bold uppercase tracking-wider text-gray-400">
                                <th class="px-4 py-3 text-left">Producto</th>
                                <th class="px-4 py-3 text-center">Cant.</th>
                                <th class="px-4 py-3 text-right">P. Unit.</th>
                                <th class="px-4 py-3 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800">
                            @foreach($order->items as $item)
                            <tr class="hover:bg-gray-800/30 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-gray-800 border border-gray-700 overflow-hidden flex-shrink-0">
                                            @if($item->product && $item->product->image_url)
                                                <img src="{{ asset('storage/' . $item->product->image_url) }}" class="w-full h-full object-cover">
                                            @else
                                                <div class="w-full h-full flex items-center justify-center text-gray-600">
                                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                </div>
                                            @endif
                                        </div>
                                        <span class="text-sm font-medium text-white">
                                            {{ $item->product ? $item->product->name : 'Producto eliminado' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center text-gray-300">{{ $item->quantity }}</td>
                                <td class="px-4 py-3 text-right text-gray-300">${{ number_format($item->price, 2) }}</td>
                                <td class="px-4 py-3 text-right font-bold text-white">${{ number_format($item->price * $item->quantity, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray-800/60">
                                <td colspan="3" class="px-4 py-4 text-right font-bold text-gray-300 uppercase tracking-wider text-sm">Total</td>
                                <td class="px-4 py-4 text-right text-2xl font-black text-white">${{ number_format($order->total, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Footer del comprobante --}}
            <div class="text-center text-gray-600 text-xs pt-4 border-t border-gray-800">
                <p>{{ config('app.name') }} • Comprobante generado el {{ now()->format('d/m/Y H:i') }}</p>
                <p class="mt-1">Este comprobante es válido como constancia de pago electrónico.</p>
            </div>
        </div>
    </div>

    <div class="no-print max-w-3xl mx-auto mt-6 text-center">
        <p class="text-gray-600 text-sm">¿Necesitás el comprobante en papel? Usá el botón "Imprimir / PDF" de arriba.</p>
    </div>

</body>
</html>
