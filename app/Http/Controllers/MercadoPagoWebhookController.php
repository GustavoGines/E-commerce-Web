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
    public function handle(Request $request, MercadoPagoService $mpService): \Illuminate\Http\JsonResponse
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

            // Disparamos el evento para que la lógica de negocio se maneje en listeners separados
            event(new \App\Events\PaymentApproved($payment, $dataId));

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
