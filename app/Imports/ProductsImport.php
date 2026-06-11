<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Models\Brand;

class ProductsImport implements OnEachRow, WithHeadingRow
{
    public $importedCount = 0;

    public function onRow(Row $row)
    {
        $rowArray = $row->toArray();

        // WithHeadingRow normalizes headers to snake_case automatically
        // Example: "Precio Mayorista" -> "precio_mayorista"
        $name = $rowArray['nombre'] ?? null;
        
        if (empty($name)) {
            return;
        }

        // Lógica de Categorías
        $categoryName = $rowArray['categoria'] ?? null;
        $categoryId = null;
        
        if (!empty($categoryName)) {
            $category = Category::firstOrCreate(
                ['name' => $categoryName],
                ['slug' => Str::slug($categoryName)]
            );
            $categoryId = $category->id;
        }

        // Lógica de Marcas (Extracción Inteligente)
        $brandName = $rowArray['marca'] ?? null;
        
        // Si no viene la columna "Marca", intentamos deducirla del nombre o la categoría
        if (empty($brandName)) {
            $brandName = $this->extractBrandFromString($name . ' ' . $categoryName);
        }

        $brandId = null;
        if (!empty($brandName)) {
            $brand = Brand::firstOrCreate(
                ['name' => $brandName],
                ['slug' => Str::slug($brandName)]
            );
            $brandId = $brand->id;
        }

        $sku = $rowArray['sku'] ?? ($rowArray['codigo'] ?? null);
        
        $retailPrice = $rowArray['precio'] ?? 0;
        $wholesalePrice = $rowArray['precio_mayorista'] ?? 0;
        $costPrice = $rowArray['costo'] ?? 0;

        $productData = [
            'name' => $name,
            'retail_price' => (float) $retailPrice,
            'wholesale_price' => (float) $wholesalePrice,
            'cost_price' => (float) $costPrice,
            'stock' => (int) ($rowArray['stock'] ?? 0),
            'category_id' => $categoryId,
            'brand_id' => $brandId,
        ];

        // Lógica de actualización o creación
        if (!empty($sku)) {
            $productData['sku'] = $sku;
            Product::updateOrCreate(
                ['sku' => $sku],
                $productData
            );
        } else {
            Product::create($productData);
        }

        $this->importedCount++;
    }

    /**
     * Extrae una marca conocida de un texto dado.
     */
    private function extractBrandFromString($text)
    {
        if (empty($text)) {
            return null;
        }

        // Marcas comunes en electrónica, climatización y accesorios
        $commonBrands = [
            'Samsung', 'LG', 'Sony', 'Philips', 'BGH', 'Noblex', 'TCL', 'Hisense', 
            'Hitachi', 'RCA', 'Philco', 'Sanyo', 'JVC', 'Pioneer', 'Motorola', 
            'Apple', 'Xiaomi', 'Huawei', 'Lenovo', 'Asus', 'Acer', 'HP', 'Dell', 
            'Kanji', 'Noga', 'Nisuta', 'Seisa', 'Suono', 'Panacom', 'Daewoo', 
            'Sansei', 'Atma', 'Liliana', 'Peabody', 'Midea', 'Surrey', 'Carrier', 
            'York', 'Electra', 'Marshall', 'Zenith'
        ];

        // Añadimos las marcas que ya existan en la base de datos para retroalimentar
        $dbBrands = Brand::pluck('name')->toArray();
        $allBrands = array_unique(array_merge($commonBrands, $dbBrands));

        $textLower = mb_strtolower($text);

        foreach ($allBrands as $brand) {
            // Buscamos la palabra exacta (con separadores de palabra para no falsos positivos, ej: LG en "ALGO")
            if (preg_match('/\b' . preg_quote(mb_strtolower($brand), '/') . '\b/u', $textLower)) {
                // Retornamos el nombre bien formateado
                return $brand;
            }
        }

        return null;
    }
}
