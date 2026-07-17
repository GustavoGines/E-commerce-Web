<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Product;
use App\Models\Category;

class SitemapController extends Controller
{
    /**
     * FIX-06: Genera el sitemap XML con caché de 6 horas.
     *
     * - Los datos se regeneran desde DB como máximo una vez cada 6 horas.
     * - Solo se incluyen productos con stock > 0 (evita indexar productos agotados).
     * - La ruta tiene throttle:30,1 en web.php para limitar bots agresivos.
     */
    public function index()
    {
        $xml = Cache::remember('sitemap_xml', now()->addHours(6), function () {
            // Solo productos con stock disponible para indexar en buscadores
            $products = Product::select('id', 'slug', 'updated_at')
                ->where('stock', '>', 0)
                ->get();

            $categories = Category::select('id', 'updated_at')->get();

            return view('sitemap.index', [
                'products'   => $products,
                'categories' => $categories,
            ])->render();
        });

        return response($xml)->header('Content-Type', 'text/xml');
    }
}
