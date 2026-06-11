<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $retail   = fake()->randomFloat(2, 1000, 50000);
        $wholesale = round($retail * 0.80, 2); // 20% descuento mayorista

        return [
            'name'                  => fake()->words(3, true),
            'sku'                   => null, // nullable por defecto
            'description'           => fake()->sentence(),
            'retail_price'          => $retail,
            'wholesale_price'       => $wholesale,
            'wholesale_min_quantity'=> 10,
            'cost_price'            => round($retail * 0.60, 2),
            'profit_margin'         => 40,
            'wholesale_discount'    => 20,
            'stock'                 => 100,
        ];
    }
}
