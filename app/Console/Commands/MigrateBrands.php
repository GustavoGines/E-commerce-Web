<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MigrateBrands extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-brands';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate and assign brands to products randomly';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Brand::truncate();
        $brandNames = ['Samsung', 'LG', 'Sony', 'TCL', 'Philips', 'BGH', 'Noblex', 'Hisense', 'Xiaomi', 'Genérico'];
        $brands = [];
        foreach ($brandNames as $name) {
            $brands[] = Brand::create([
                'name' => $name,
                'slug' => Str::slug($name),
            ]);
        }
        $products = Product::all();
        foreach ($products as $product) {
            $product->brand_id = $brands[array_rand($brands)]->id;
            $product->save();
        }
        $this->info('Brands migrated!');
    }
}
