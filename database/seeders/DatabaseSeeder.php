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

        StoreSetting::create([
            'store_name' => 'JCG Electronica',
            'store_tagline' => 'El mayor catálogo de controles remotos y electrónica. Ventas por mayor y menor.',
            'logo_url' => 'logos/9mq9acRO0Bsn3Xam2NvFo7KokEgo5Ann47kjvYao.png',
            'favicon_url' => 'favicons/QJTfG29AuKbmYhlqAr5PEqO4CH9JoVAiJIwt9Y9C.png',
            'theme_name' => 'modern-light',
            'social_links' => [
                'tiktok' => 'https://www.tiktok.com/@jcg.electronica',
                'facebook' => 'https://www.facebook.com/profile.php?id=61558661411698',
                'whatsapp' => '3705075839',
                'instagram' => 'https://www.instagram.com/jcgelectronica.fsa/'
            ],
        ]);

        // Se eliminaron los productos de prueba. La base de datos iniciará limpia.
    }
}
