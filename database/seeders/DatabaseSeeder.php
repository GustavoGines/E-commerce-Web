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
            'email' => 'admin@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        User::factory()->create([
            'name' => 'Cliente Normal',
            'email' => 'cliente@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'user',
        ]);

        StoreSetting::create([
            'store_name' => 'TechStore Premium',
            'logo_url' => null,
        ]);

        $catProcesadores = \App\Models\Category::create(['name' => 'Procesadores', 'slug' => 'procesadores']);
        $catMotherboards = \App\Models\Category::create(['name' => 'Motherboards', 'slug' => 'motherboards']);
        $catMemorias = \App\Models\Category::create(['name' => 'Memorias RAM', 'slug' => 'memorias-ram']);
        $catGabinetes = \App\Models\Category::create(['name' => 'Gabinetes', 'slug' => 'gabinetes']);
        $catFuentes = \App\Models\Category::create(['name' => 'Fuentes', 'slug' => 'fuentes']);
        $catTarjetas = \App\Models\Category::create(['name' => 'Placas de Video', 'slug' => 'placas-de-video']);

        $brandAMD = \App\Models\Brand::create(['name' => 'AMD', 'slug' => 'amd']);
        $brandASUS = \App\Models\Brand::create(['name' => 'ASUS', 'slug' => 'asus']);
        $brandCorsair = \App\Models\Brand::create(['name' => 'Corsair', 'slug' => 'corsair']);
        $brandNZXT = \App\Models\Brand::create(['name' => 'NZXT', 'slug' => 'nzxt']);
        $brandEVGA = \App\Models\Brand::create(['name' => 'EVGA', 'slug' => 'evga']);
        $brandNvidia = \App\Models\Brand::create(['name' => 'NVIDIA', 'slug' => 'nvidia']);

        Product::create([
            'category_id' => $catProcesadores->id,
            'brand_id' => $brandAMD->id,
            'name' => 'AMD Ryzen 7 8700G',
            'description' => 'Procesador AMD Ryzen 7 8700G con gráficos Radeon 780M integrados.',
            'cost_price' => 200.00,
            'profit_margin' => 75,
            'wholesale_discount' => 11,
            'wholesale_min_quantity' => 3,
            'retail_price' => 350.00,
            'wholesale_price' => 310.00,
            'stock' => 50,
            'image_url' => 'products/ryzen_cpu.png',
        ]);

        Product::create([
            'category_id' => $catMotherboards->id,
            'brand_id' => $brandASUS->id,
            'name' => 'Motherboard ASUS ROG Strix B650E-F',
            'description' => 'Placa madre ATX color negro con soporte para PCIe 5.0 y DDR5.',
            'cost_price' => 150.00,
            'profit_margin' => 86,
            'wholesale_discount' => 12,
            'wholesale_min_quantity' => 3,
            'retail_price' => 280.00,
            'wholesale_price' => 245.00,
            'stock' => 20,
            'image_url' => 'products/rog_motherboard.png',
        ]);

        Product::create([
            'category_id' => $catMemorias->id,
            'brand_id' => $brandCorsair->id,
            'name' => 'Memoria RAM Corsair Vengeance DDR5 32GB',
            'description' => 'Kit de memoria de 32GB (2x16GB) a 6000MHz, color negro.',
            'cost_price' => 60.00,
            'profit_margin' => 100,
            'wholesale_discount' => 12,
            'wholesale_min_quantity' => 5,
            'retail_price' => 120.00,
            'wholesale_price' => 105.00,
            'stock' => 100,
            'image_url' => 'products/corsair_ram.png',
        ]);

        Product::create([
            'category_id' => $catGabinetes->id,
            'brand_id' => $brandNZXT->id,
            'name' => 'Gabinete NZXT H5 Flow All-Black',
            'description' => 'Gabinete mid-tower con excelente flujo de aire, color negro mate.',
            'cost_price' => 50.00,
            'profit_margin' => 90,
            'wholesale_discount' => 15,
            'wholesale_min_quantity' => 2,
            'retail_price' => 95.00,
            'wholesale_price' => 80.00,
            'stock' => 15,
            'image_url' => 'products/nzxt_case.png',
        ]);

        Product::create([
            'category_id' => $catFuentes->id,
            'brand_id' => $brandEVGA->id,
            'name' => 'Fuente de Poder EVGA SuperNOVA 850G',
            'description' => 'Fuente de 850W 80 Plus Gold, totalmente modular.',
            'cost_price' => 80.00,
            'profit_margin' => 75,
            'wholesale_discount' => 10,
            'wholesale_min_quantity' => 3,
            'retail_price' => 140.00,
            'wholesale_price' => 125.00,
            'stock' => 30,
            'image_url' => 'products/evga_psu.png',
        ]);

        Product::create([
            'category_id' => $catTarjetas->id,
            'brand_id' => $brandNvidia->id,
            'name' => 'Placa de Video NVIDIA GeForce RTX 4090 24GB',
            'description' => 'Tarjeta gráfica de ultra alto rendimiento, 24GB GDDR6X.',
            'cost_price' => 1500.00,
            'profit_margin' => 30,
            'wholesale_discount' => 5,
            'wholesale_min_quantity' => 2,
            'retail_price' => 1950.00,
            'wholesale_price' => 1850.00,
            'stock' => 5,
            'image_url' => 'products/nvidia_gpu.png',
        ]);
    }
}
