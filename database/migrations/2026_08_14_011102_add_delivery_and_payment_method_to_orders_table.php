<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Agrega las columnas delivery_method y payment_method a la tabla orders.
     * Estas columnas estaban en el $fillable del modelo Order pero nunca
     * se creó la migración correspondiente (BUG-12).
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Método de entrega: 'envio' | 'retiro'
            $table->string('delivery_method')->nullable()->after('zip_code');
            // Método de pago: 'mercadopago' | 'whatsapp' | 'efectivo'
            $table->string('payment_method')->nullable()->after('delivery_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_method', 'payment_method']);
        });
    }
};
