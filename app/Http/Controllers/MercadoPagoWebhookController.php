<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\MercadoPagoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MercadoPagoWebhookController extends Controller
{
    /**
     * Recibe las notificaciones IPN/Webhook de MercadoPago.
     * MercadoPago envía un POST con el tipo de notificación y el ID del recurso.
     * Siempre debemos responder 200 inmediatamente, y luego procesar de forma idempotente.
     */
    public function handle(Request $request, MercadoPagoService $mpService)
    {
        // Loggear el payload completo para debugging
        Log::info('MercadoPago Webhook recibido', $request->all());

        $type = $request->input('type');
        $dataId = $request->input('data.id'); // ID del pago

        // Solo nos interesan las notificaciones de tipo "payment"
        if ($type !== 'payment' || empty($dataId)) {
            return response()->json(['status' => 'ignored'], 200);
        }

        try {
            // Consultamos el pago directamente a la API de MP (nunca confiar solo en el webhook)
            $payment = $mpService->getPayment($dataId);

            $orderId = $payment->external_reference ?? null;
            if (! $orderId) {
                Log::warning('Webhook MP: pago sin external_reference', ['payment_id' => $dataId]);

                return response()->json(['status' => 'no_reference'], 200);
            }

            $order = Order::find($orderId);
            if (! $order) {
                Log::warning('Webhook MP: orden no encontrada', ['order_id' => $orderId]);

                return response()->json(['status' => 'order_not_found'], 200);
            }

            // Guardar el ID del pago en la orden
            $order->mp_payment_id = $dataId;

            // Mapear el estado de MP a nuestros estados internos
            $order->status = match ($payment->status) {
                'approved' => 'pagado',
                'pending',
                'in_process' => 'pendiente',
                'rejected',
                'cancelled' => 'cancelado',
                default => $order->status, // no cambiar si es estado desconocido
            };

            $order->save();

            Log::info('Webhook MP: orden actualizada', [
                'order_id' => $orderId,
                'payment_id' => $dataId,
                'mp_status' => $payment->status,
                'new_status' => $order->status,
            ]);

        } catch (\Exception $e) {
            // Siempre devolver 200 para que MP no reintente indefinidamente.
            // El error queda loggeado para revisión manual.
            Log::error('Webhook MP: excepción al procesar pago', [
                'payment_id' => $dataId,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json(['status' => 'ok'], 200);
    }
}
