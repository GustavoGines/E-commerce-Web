<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'status' => 'pendiente',
            'total' => 1000,
            'phone' => '1234567890',
            'address_street' => 'Calle Falsa',
            'address_number' => '123',
            'city' => 'Springfield',
            'state' => 'State',
            'zip_code' => '1234',
            'role_applied' => 'minorista',
        ];
    }
}
