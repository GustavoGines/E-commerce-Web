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

Si realizaste el pago por MercadoPago, el pedido se procesará automáticamente. 
Si el pago es acordado con el vendedor (transferencia o efectivo), nos pondremos en contacto contigo a la brevedad.

<x-mail::button :url="route('my-orders')" color="primary">
Ver Mis Órdenes
</x-mail::button>

¡Gracias por elegirnos!<br>
{{ config('app.name') }}
</x-mail::message>
