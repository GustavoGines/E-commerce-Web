<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Support\Str;

// Clear old brands
Brand::truncate();

// Create new brands
$brandNames = [
    'Samsung',
    'LG',
    'Sony',
    'TCL',
    'Philips',
    'BGH',
    'Noblex',
    'Hisense',
    'Xiaomi',
    'Genérico'
];

$brands = [];
foreach ($brandNames as $name) {
    $brands[] = Brand::create([
        'name' => $name,
        'slug' => Str::slug($name)
    ]);
}

// Assign random brands to products
$products = Product::all();
foreach ($products as $product) {
    $randomBrand = $brands[array_rand($brands)];
    $product->brand_id = $randomBrand->id;
    $product->save();
}

echo "Marcas actualizadas y asignadas aleatoriamente a los productos con éxito.\n";
