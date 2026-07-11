<x-mail::message>
# ¡Hola {{ optional($order->user)->name ?? 'Cliente' }}!

Hemos recibido tu pedido **#{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}** correctamente.

### Resumen del pedido

<x-mail::table>
| Producto       | Cant. | Precio  |
|:---------------|:-----:|--------:|
@foreach($order->items as $item)
| {{ $item->product ? $item->product->name : 'Producto' }} | {{ $item->quantity }} | ${{ number_format($item->price, 2) }} |
@endforeach
| **Total:** | | **${{ number_format($order->total, 2) }}** |
</x-mail::table>

Como tu método de pago es **"Acordado con el Vendedor"** (transferencia o efectivo), nos pondremos en contacto contigo a la brevedad para coordinar el pago y la entrega de tus productos.

<x-mail::button :url="route('my-orders')" color="primary">
Ver Mis Órdenes
</x-mail::button>

¡Gracias por elegirnos!<br>
{{ config('app.name') }}
</x-mail::message>
