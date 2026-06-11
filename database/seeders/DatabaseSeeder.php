<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\StoreSetting;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
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
            'name' => 'Cliente Mayorista',
            'email' => 'cliente@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'user',
        ]);

        StoreSetting::create([
            'store_name' => 'JCG Electrónica',
            'theme_name' => 'stealth',
            'logo_url' => null,
        ]);

        $catTV = Category::create(['name' => 'Controles de TV', 'slug' => 'controles-tv']);
        $catAC = Category::create(['name' => 'Controles de Aire Acondicionado', 'slug' => 'controles-ac']);
        $catTVBox = Category::create(['name' => 'TV Box y Streaming', 'slug' => 'tv-box']);
        $catCelulares = Category::create(['name' => 'Accesorios para Celulares', 'slug' => 'accesorios-celulares']);

        $brandSamsung = Brand::create(['name' => 'Samsung', 'slug' => 'samsung']);
        $brandLG = Brand::create(['name' => 'LG', 'slug' => 'lg']);
        $brandPhilips = Brand::create(['name' => 'Philips', 'slug' => 'philips']);
        $brandTCL = Brand::create(['name' => 'TCL', 'slug' => 'tcl']);
        $brandBGH = Brand::create(['name' => 'BGH', 'slug' => 'bgh']);
        $brandNoblex = Brand::create(['name' => 'Noblex', 'slug' => 'noblex']);
        $brandXiaomi = Brand::create(['name' => 'Xiaomi', 'slug' => 'xiaomi']);
        $brandRoku = Brand::create(['name' => 'Roku', 'slug' => 'roku']);
        $brandGenerico = Brand::create(['name' => 'Genérico', 'slug' => 'generico']);

        // TV Remotes
        Product::create([
            'category_id' => $catTV->id,
            'brand_id' => $brandSamsung->id,
            'name' => 'Control Remoto TV Samsung Smart',
            'description' => 'Control remoto compatible con todos los televisores Samsung Smart TV. No requiere programación.',
            'cost_price' => 2.50,
            'profit_margin' => 100,
            'wholesale_discount' => 20,
            'wholesale_min_quantity' => 10,
            'retail_price' => 5.00,
            'wholesale_price' => 4.00,
            'stock' => 200,
            'image_url' => 'banners/tv_remote.png',
        ]);

        Product::create([
            'category_id' => $catTV->id,
            'brand_id' => $brandLG->id,
            'name' => 'Control Remoto TV LG Magic',
            'description' => 'Control remoto original para Smart TV LG. Función de puntero y comandos de voz.',
            'cost_price' => 15.00,
            'profit_margin' => 60,
            'wholesale_discount' => 15,
            'wholesale_min_quantity' => 5,
            'retail_price' => 24.00,
            'wholesale_price' => 20.40,
            'stock' => 50,
            'image_url' => 'banners/tv_remote.png',
        ]);

        Product::create([
            'category_id' => $catTV->id,
            'brand_id' => $brandPhilips->id,
            'name' => 'Control Remoto TV Philips Netflix',
            'description' => 'Control remoto directo para televisores Philips con botón directo a Netflix y YouTube.',
            'cost_price' => 3.00,
            'profit_margin' => 90,
            'wholesale_discount' => 20,
            'wholesale_min_quantity' => 10,
            'retail_price' => 5.70,
            'wholesale_price' => 4.56,
            'stock' => 100,
            'image_url' => 'banners/tv_remote.png',
        ]);

        Product::create([
            'category_id' => $catTV->id,
            'brand_id' => $brandTCL->id,
            'name' => 'Control Remoto TV TCL Android',
            'description' => 'Control con micrófono para Google Assistant, compatible con todos los TCL Android TV.',
            'cost_price' => 4.50,
            'profit_margin' => 80,
            'wholesale_discount' => 15,
            'wholesale_min_quantity' => 10,
            'retail_price' => 8.10,
            'wholesale_price' => 6.88,
            'stock' => 80,
            'image_url' => 'banners/tv_remote.png',
        ]);

        // AC Remotes
        Product::create([
            'category_id' => $catAC->id,
            'brand_id' => $brandGenerico->id,
            'name' => 'Control Remoto Universal Aire Acondicionado',
            'description' => 'Control remoto universal compatible con más de 1000 marcas de aire acondicionado.',
            'cost_price' => 3.00,
            'profit_margin' => 100,
            'wholesale_discount' => 15,
            'wholesale_min_quantity' => 10,
            'retail_price' => 6.00,
            'wholesale_price' => 5.10,
            'stock' => 150,
            'image_url' => 'banners/ac_remote.png',
        ]);

        Product::create([
            'category_id' => $catAC->id,
            'brand_id' => $brandBGH->id,
            'name' => 'Control Remoto Aire Acondicionado BGH',
            'description' => 'Reemplazo directo para aires BGH Silent Air y modelos similares. Funciones frío/calor.',
            'cost_price' => 3.50,
            'profit_margin' => 85,
            'wholesale_discount' => 15,
            'wholesale_min_quantity' => 10,
            'retail_price' => 6.47,
            'wholesale_price' => 5.50,
            'stock' => 120,
            'image_url' => 'banners/ac_remote.png',
        ]);

        Product::create([
            'category_id' => $catAC->id,
            'brand_id' => $brandNoblex->id,
            'name' => 'Control Remoto Aire Noblex Inverter',
            'description' => 'Control para equipos Noblex Inverter, con modo ECO y Turbo. Display digital.',
            'cost_price' => 3.80,
            'profit_margin' => 85,
            'wholesale_discount' => 15,
            'wholesale_min_quantity' => 10,
            'retail_price' => 7.00,
            'wholesale_price' => 5.95,
            'stock' => 90,
            'image_url' => 'banners/ac_remote.png',
        ]);

        // TV Box
        Product::create([
            'category_id' => $catTVBox->id,
            'brand_id' => $brandXiaomi->id,
            'name' => 'Xiaomi Mi Box S 4K (2da Gen)',
            'description' => 'Reproductor multimedia 4K Ultra HD con Google TV. Incluye control remoto con comandos de voz.',
            'cost_price' => 45.00,
            'profit_margin' => 30,
            'wholesale_discount' => 10,
            'wholesale_min_quantity' => 5,
            'retail_price' => 58.50,
            'wholesale_price' => 52.65,
            'stock' => 50,
            'image_url' => 'banners/cat_tvbox.png',
        ]);

        Product::create([
            'category_id' => $catTVBox->id,
            'brand_id' => $brandRoku->id,
            'name' => 'Roku Express 4K',
            'description' => 'Dispositivo de streaming fácil de usar. Calidad de imagen brillante en HD, 4K y HDR.',
            'cost_price' => 35.00,
            'profit_margin' => 35,
            'wholesale_discount' => 10,
            'wholesale_min_quantity' => 5,
            'retail_price' => 47.25,
            'wholesale_price' => 42.52,
            'stock' => 40,
            'image_url' => 'banners/cat_tvbox.png',
        ]);

        Product::create([
            'category_id' => $catTVBox->id,
            'brand_id' => $brandGenerico->id,
            'name' => 'TV Box Android 11.0 4GB RAM 64GB',
            'description' => 'Convertí tu TV en Smart con Android. Memoria de 4GB para fluidez extrema y 64GB de almacenamiento.',
            'cost_price' => 25.00,
            'profit_margin' => 60,
            'wholesale_discount' => 15,
            'wholesale_min_quantity' => 5,
            'retail_price' => 40.00,
            'wholesale_price' => 34.00,
            'stock' => 60,
            'image_url' => 'banners/cat_tvbox.png',
        ]);

        // Accesorios
        Product::create([
            'category_id' => $catCelulares->id,
            'brand_id' => $brandGenerico->id,
            'name' => 'Cargador Pared Carga Rápida 20W Tipo C',
            'description' => 'Cargador de pared con puerto USB-C de 20W. Ideal para carga rápida de dispositivos modernos.',
            'cost_price' => 4.00,
            'profit_margin' => 120,
            'wholesale_discount' => 25,
            'wholesale_min_quantity' => 20,
            'retail_price' => 8.80,
            'wholesale_price' => 6.60,
            'stock' => 300,
            'image_url' => 'banners/cat_accesorios.png',
        ]);

        Product::create([
            'category_id' => $catCelulares->id,
            'brand_id' => $brandGenerico->id,
            'name' => 'Cable USB a Tipo C Mallado 1.5m',
            'description' => 'Cable de carga y transferencia de datos. Recubrimiento mallado ultra resistente. Soporta carga rápida.',
            'cost_price' => 1.50,
            'profit_margin' => 150,
            'wholesale_discount' => 30,
            'wholesale_min_quantity' => 20,
            'retail_price' => 3.75,
            'wholesale_price' => 2.62,
            'stock' => 500,
            'image_url' => 'banners/cat_accesorios.png',
        ]);
    }
}
