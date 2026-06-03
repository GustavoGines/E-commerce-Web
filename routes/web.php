<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use App\Http\Controllers\CheckoutReturnController;
use App\Http\Controllers\MercadoPagoWebhookController;

use App\Models\StoreSetting;

Route::get('/', function () {
    $settings = StoreSetting::first();
    $theme = ($settings && $settings->theme_name) ? $settings->theme_name : 'stealth';
    
    // Fallback to stealth if theme view doesn't exist
    if (!view()->exists("themes.{$theme}.welcome")) {
        $theme = 'stealth';
    }
    
    return view("themes.{$theme}.welcome");
})->name('home');

Route::get('/shop', function () {
    $settings = StoreSetting::first();
    $theme = ($settings && $settings->theme_name) ? $settings->theme_name : 'stealth';
    
    // Fallback to stealth if theme view doesn't exist
    if (!view()->exists("themes.{$theme}.shop")) {
        $theme = 'stealth';
    }
    
    return view("themes.{$theme}.shop");
})->name('shop');


Volt::route('producto/{slug}', 'product-detail')
    ->name('product.detail');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';

use App\Http\Controllers\GoogleAuthController;

Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('google.login');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');

Route::middleware(['auth'])->group(function () {
    Volt::route('checkout', 'checkout')->name('checkout');
    Volt::route('mis-ordenes', 'my-orders')->name('my-orders');

    // URLs de retorno de MercadoPago (requieren usuario autenticado)
    Route::get('checkout/success/{order}', [CheckoutReturnController::class, 'success'])->name('checkout.success');
    Route::get('checkout/failure/{order}', [CheckoutReturnController::class, 'failure'])->name('checkout.failure');
    Route::get('checkout/pending/{order}', [CheckoutReturnController::class, 'pending'])->name('checkout.pending');
});

Route::middleware(['auth', 'is_admin'])->group(function () {
    Volt::route('admin/settings', 'admin.manage-settings')->name('admin.settings');
    Volt::route('admin/products', 'admin.manage-products')->name('admin.products');

    Volt::route('admin/orders', 'admin.manage-orders')->name('admin.orders');
});

// Webhook de MercadoPago — sin auth, sin CSRF (se maneja en bootstrap/app.php)
Route::post('webhooks/mercadopago', [MercadoPagoWebhookController::class, 'handle'])
    ->name('webhook.mercadopago');
