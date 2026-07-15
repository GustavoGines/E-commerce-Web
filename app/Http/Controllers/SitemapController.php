<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;

class SitemapController extends Controller
{
    public function index()
    {
        // Obtener productos activos (stock > 0 o que queramos que se indexen)
        $products = Product::select('id', 'slug', 'updated_at')->get();
        
        // Obtener categorías
        $categories = Category::select('id', 'updated_at')->get();

        return response()->view('sitemap.index', [
            'products' => $products,
            'categories' => $categories,
        ])->header('Content-Type', 'text/xml');
    }
}
