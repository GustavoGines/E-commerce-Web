<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$settings = \App\Models\StoreSetting::first();
$settings->logo_url = 'logos/logo final JCG 2.png';
$settings->save();
echo "Logo updated to: " . $settings->logo_url . "\n";
