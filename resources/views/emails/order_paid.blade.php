<!DOCTYPE html>
<html>
<head>
    <title>Tu pago ha sido confirmado</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee;">
        <h2 style="color: #10b981;">¡Pago Confirmado!</h2>
        <p>Hola {{ optional($order->user)->name ?? 'Cliente' }}, hemos recibido el pago de tu pedido <strong>#{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}</strong>.</p>
        
        <p>El estado de tu pedido ha sido actualizado a <strong>Pagado</strong> y ya estamos preparando el envío/entrega.</p>

        <table style="width: 100%; border-collapse: collapse; margin-top: 20px; margin-bottom: 20px;">
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #e2e8f0; font-weight: bold;">Monto Pagado:</td>
                <td style="padding: 10px; border-bottom: 1px solid #e2e8f0;">${{ number_format($order->total, 2) }}</td>
            </tr>
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #e2e8f0; font-weight: bold;">ID de Pago:</td>
                <td style="padding: 10px; border-bottom: 1px solid #e2e8f0;">{{ $order->mp_payment_id ?? 'N/A' }}</td>
            </tr>
        </table>

        <p>Puedes ver los detalles completos en la sección de <a href="{{ route('my-orders') }}">Mis Órdenes</a>.</p>
        <p>¡Gracias por tu compra!</p>
    </div>
</body>
</html>
