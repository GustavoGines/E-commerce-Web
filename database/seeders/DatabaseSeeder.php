<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\StoreSetting;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@admin.com',
            'password' => Hash::make('password'),
        ]);

        StoreSetting::create([
            'store_name' => 'E-commerce Web',
            'primary_color' => '#111827',
            'logo_url' => null,
        ]);

        Product::create([
            'name' => 'AMD Ryzen 7 8700G',
            'description' => 'Procesador AMD Ryzen 7 8700G con gráficos Radeon 780M integrados.',
            'retail_price' => 350.00,
            'wholesale_price' => 310.00,
            'stock' => 50,
        ]);

        Product::create([
            'name' => 'Motherboard ASUS ROG Strix B650E-F',
            'description' => 'Placa madre ATX color negro con soporte para PCIe 5.0 y DDR5.',
            'retail_price' => 280.00,
            'wholesale_price' => 245.00,
            'stock' => 20,
        ]);

        Product::create([
            'name' => 'Memoria RAM Corsair Vengeance DDR5 32GB',
            'description' => 'Kit de memoria de 32GB (2x16GB) a 6000MHz, color negro.',
            'retail_price' => 120.00,
            'wholesale_price' => 105.00,
            'stock' => 100,
        ]);

        Product::create([
            'name' => 'Gabinete NZXT H5 Flow All-Black',
            'description' => 'Gabinete mid-tower con excelente flujo de aire, color negro mate.',
            'retail_price' => 95.00,
            'wholesale_price' => 80.00,
            'stock' => 15,
        ]);

        Product::create([
            'name' => 'Fuente de Poder EVGA SuperNOVA 850G',
            'description' => 'Fuente de 850W 80 Plus Gold, totalmente modular.',
            'retail_price' => 140.00,
            'wholesale_price' => 125.00,
            'stock' => 30,
        ]);
    }
}
