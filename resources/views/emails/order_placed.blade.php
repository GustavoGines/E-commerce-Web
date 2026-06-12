<!DOCTYPE html>
<html>
<head>
    <title>Tu pedido ha sido recibido</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee;">
        <h2 style="color: #2563eb;">¡Hola {{ optional($order->user)->name ?? 'Cliente' }}!</h2>
        <p>Hemos recibido tu pedido <strong>#{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}</strong> correctamente.</p>
        
        <h3>Resumen del pedido</h3>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
            <thead>
                <tr style="background-color: #f8fafc; text-align: left;">
                    <th style="padding: 10px; border-bottom: 2px solid #e2e8f0;">Producto</th>
                    <th style="padding: 10px; border-bottom: 2px solid #e2e8f0;">Cant.</th>
                    <th style="padding: 10px; border-bottom: 2px solid #e2e8f0;">Precio</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #e2e8f0;">{{ $item->product_name ?? 'Producto' }}</td>
                    <td style="padding: 10px; border-bottom: 1px solid #e2e8f0;">{{ $item->quantity }}</td>
                    <td style="padding: 10px; border-bottom: 1px solid #e2e8f0;">${{ number_format($item->price, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" style="text-align: right; padding: 10px; font-weight: bold;">Total:</td>
                    <td style="padding: 10px; font-weight: bold; color: #2563eb;">${{ number_format($order->total, 2) }}</td>
                </tr>
            </tfoot>
        </table>

        <p>Puedes ver el estado de tu pedido en la sección de <a href="{{ route('my-orders') }}">Mis Órdenes</a>.</p>
        <p>¡Gracias por elegirnos!</p>
    </div>
</body>
</html>
