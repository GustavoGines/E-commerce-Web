<x-mail::message>
# ¡Pago Confirmado!

Hola {{ optional($order->user)->name ?? 'Cliente' }}, hemos recibido el pago de tu pedido **#{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}**.

El estado de tu pedido ha sido actualizado a **Pagado** y ya estamos preparando el envío/entrega.

<x-mail::table>
| Detalle | Valor |
|:---|:---|
| **Monto Pagado:** | ${{ number_format($order->total, 2) }} |
| **ID de Pago:** | {{ $order->mp_payment_id ?? 'N/A' }} |
</x-mail::table>

<x-mail::button :url="route('my-orders')" color="success">
Ver Detalles del Pedido
</x-mail::button>

¡Gracias por tu compra!<br>
{{ config('app.name') }}
</x-mail::message>
