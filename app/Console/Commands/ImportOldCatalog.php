<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportOldCatalog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-old-catalog';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import the old catalog products and images from the Vercel app';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting catalog import...');

        $categories = [
            'lcd' => [
                'label' => 'LCD / SMART TV',
                'path' => 'img/lcd/',
                'items' => [
                    132, 133, 401, 402, 403, 404, 405, 406, 407, 408, 409, 410, 411, 412, 415, 416, 417, 418, 419, 420, 421, 422, 423, 424, 425, 426, 427, 428, 429, 430, 431, 432, 433, 434, 435, 436, 437, 438, 439, 440, 441, 442, 443, 444, 445, 446, 447, "448", "448TH", 449, 450, 451, 452, 453, 454, 455, 456, 457, 458, 459, 460, 461, 462, 463, 464, 465, 466, 467, "467JV", 468, 469, 470, 471, 472, 473, 474, 475, 476, 477, 478, 479, 480, 481, 482, 483, 484, 485, 486, 487, 488, 489, 490, 491, 492, 493, 494, 495, 496, 497, "498", "498TH", "498TH2", 499, 500, 501, 502, 503, 504, 505, 506, 507, 508, 509, 510, 511, 512, 513, 514, 515, 516, 517, 518, 519, 520, 521, 522, 523, 524, 525, 526, 527, 528, 529, 530, 531, 532, 533, 534, 535, 536, 537, 538, 539, 540, 541, 542, 543, 544, 545, 546, 547, 548, 549, 550, 551, 552, 553, "553P", 554, 557, 558, 559, 560, 561, 562, 563, 564, 565, 566, 567, 568, 569, 570, 571, 572, 573, 574, 575, 576, 577, 578, 579, 580, 581, 582, 583, 584, 585, 586, 587, 588, 589, 590, 591, 592, 593, 594, 595, 596, 597, 598, 599, 600, 611, 612, 613, 614, 615, 616, 617, 618, 619, 620, 621, 622, 623, 624, 625, 626, 627, 628, 629, 630, 631, 632, 633, 634, 635, 636, 638, 639, 640, 641, 642, 643, 644, 645, 646, 649, 654, 656, 657, 658, 659, 661, 662, 663, 664, 665, 666, 667, 668, 671, 672, 673, 674, 675
                ],
                'nameFormat' => function ($id) { return "LCD {$id}"; }
            ],
            'box' => [
                'label' => 'TV BOX',
                'path' => 'img/box/',
                'items' => ['TvBox1', 'TvBox2'],
                'nameFormat' => function ($id) { return "TV Box {$id}"; }
            ],
            'universal' => [
                'label' => 'UNIVERSAL',
                'path' => 'img/universal/',
                'items' => ['aireUniversal'],
                'nameFormat' => function ($id) { return "Control Universal Aire"; }
            ],
            'aire' => [
                'label' => 'AIRE ACOND.',
                'path' => 'img/aire/',
                'items' => [
                    800, 801, 802, 803, 804, 805, 806, 807, 808, 809, 810, 811, 812, 813, 814, 815, 816, 817, 818, 819, 820, 821, 822, 823, 824, 825, 826, 827, 828, 829, 830, 831, 832, 833, 834, 835, 836, 837, 838, 839, 840, 841, 842, 843, 844, 845, 846, 847, 848, 849, 850, 851, 852, 853, 854, 855, 856, 857, 858, 859, 860, 861, 862, 863, 864, 865, 866, 867, 868, 869, 870, 871, 872, 873, 874, 875, 876, 877, 878, 879, 880, 881, 882, 883, 884, 885, 886, 887, 889, 890, 891, 892, 893, 895
                ],
                'nameFormat' => function ($id) { return "AR {$id}"; }
            ]
        ];

        // Ensure "Genérico" brand exists
        $brand = Brand::firstOrCreate(
            ['slug' => 'generico'],
            ['name' => 'Genérico']
        );

        $baseUrl = 'https://controlesremotos.vercel.app/';
        
        $totalItems = 0;
        foreach ($categories as $cat) {
            $totalItems += count($cat['items']);
        }

        $bar = $this->output->createProgressBar($totalItems);
        $bar->start();

        foreach ($categories as $key => $catData) {
            $category = Category::firstOrCreate(
                ['slug' => Str::slug($catData['label'])],
                ['name' => $catData['label']]
            );

            foreach ($catData['items'] as $itemId) {
                $productName = $catData['nameFormat']($itemId);
                $remoteUrl = $baseUrl . $catData['path'] . $itemId . '.webp';
                
                // Avoid duplicates by name
                if (!Product::where('name', $productName)->exists()) {
                    
                    $filename = "{$key}_{$itemId}.webp";
                    $localPath = "products/{$filename}";

                    // Download image if it doesn't exist locally
                    if (!Storage::disk('public')->exists($localPath)) {
                        try {
                            $response = Http::get($remoteUrl);
                            if ($response->successful()) {
                                Storage::disk('public')->put($localPath, $response->body());
                            } else {
                                $this->warn("\nFailed to download: {$remoteUrl}");
                                $localPath = null;
                            }
                        } catch (\Exception $e) {
                            $this->warn("\nException downloading: {$remoteUrl} - " . $e->getMessage());
                            $localPath = null;
                        }
                    }

                    // Create product
                    Product::create([
                        'category_id' => $category->id,
                        'brand_id' => $brand->id,
                        'name' => $productName,
                        'description' => 'Importado automáticamente del catálogo de Vercel.',
                        'cost_price' => 5000,
                        'profit_margin' => 100,
                        'wholesale_discount' => 20,
                        'wholesale_min_quantity' => 10,
                        'retail_price' => 10000,
                        'wholesale_price' => 8000,
                        'stock' => 100,
                        'image_url' => $localPath,
                    ]);
                }
                
                $bar->advance();
            }
        }

        $bar->finish();
        $this->info("\nCatalog import completed successfully!");
    }
}
