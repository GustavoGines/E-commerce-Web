<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AdminProductsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Evitar que Livewire realmente intente manipular imágenes físicas complejas si no es necesario
        Storage::fake('public');
    }

    public function test_admin_can_create_a_product_with_image()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();

        $this->actingAs($admin);

        $file = UploadedFile::fake()->image('producto.jpg');

        $component = Volt::test('admin.manage-products')
            ->set('name', 'Nuevo Producto Test')
            ->set('category_id', $category->id)
            ->set('brand_id', $brand->id)
            ->set('cost_price', 1000)
            ->set('profit_margin', 50)
            ->set('wholesale_discount', 20)
            ->set('wholesale_min_quantity', 5)
            ->set('stock', 50)
            ->set('image', $file)
            ->call('save');

        $component->assertHasNoErrors();

        $product = Product::where('name', 'Nuevo Producto Test')->first();
        $this->assertNotNull($product);
        $this->assertEquals(1000, $product->cost_price);
        $this->assertEquals(1500, $product->retail_price); // 1000 + 50%
        $this->assertEquals(1200, $product->wholesale_price); // 1500 - 20%
        $this->assertNotNull($product->image_url);
        Storage::disk('public')->assertExists($product->image_url);
    }

    public function test_admin_can_apply_mass_price_increase()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $product1 = Product::factory()->create([
            'cost_price' => 1000, 
            'profit_margin' => 50, // retail: 1500
            'wholesale_discount' => 20, // wholesale: 1200
        ]);
        
        $product2 = Product::factory()->create([
            'cost_price' => 2000,
            'profit_margin' => 50, // retail: 3000
            'wholesale_discount' => 20, // wholesale: 2400
        ]);

        $this->actingAs($admin);

        // Aumentar costo un 10% a todos seleccionados, recalculando precio final
        Volt::test('admin.manage-products')
            ->set('selectedProducts', [(string)$product1->id, (string)$product2->id])
            ->set('massTarget', 'selected')
            ->set('massType', 'increase')
            ->set('massValueType', 'percent')
            ->set('massValue', 10)
            ->set('massField', 'cost_price')
            ->set('massOverride', false) // Recalcula márgenes en base al costo
            ->call('applyMassUpdate');

        // Producto 1
        $p1 = $product1->fresh();
        $this->assertEquals(1100, $p1->cost_price);
        $this->assertEquals(1650, $p1->retail_price); // 1100 + 50%
        $this->assertEquals(1320, $p1->wholesale_price); // 1650 - 20%

        // Producto 2
        $p2 = $product2->fresh();
        $this->assertEquals(2200, $p2->cost_price);
        $this->assertEquals(3300, $p2->retail_price);
        $this->assertEquals(2640, $p2->wholesale_price);
    }
    
    public function test_admin_can_create_category_and_brand()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        Volt::test('admin.manage-products')
            ->set('cat_name', 'Electrónica')
            ->call('saveCategory');

        $this->assertDatabaseHas('categories', ['name' => 'Electrónica']);

        Volt::test('admin.manage-products')
            ->set('b_name', 'Samsung')
            ->call('saveBrand');

        $this->assertDatabaseHas('brands', ['name' => 'Samsung']);
    }
}
