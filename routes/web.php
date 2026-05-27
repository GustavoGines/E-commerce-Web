<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::view('/', 'welcome')->name('home');

Volt::route('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Volt::route('producto/{slug}', 'product-detail')
    ->name('product.detail');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';

Route::middleware(['auth'])->group(function () {
    Volt::route('checkout', 'checkout')->name('checkout');
    Volt::route('mis-ordenes', 'my-orders')->name('my-orders');
});

Route::middleware(['auth', 'is_admin'])->group(function () {
    Volt::route('admin/settings', 'admin.manage-settings')->name('admin.settings');
    Volt::route('admin/products', 'admin.manage-products')->name('admin.products');
    Volt::route('admin/categories', 'admin.manage-categories')->name('admin.categories');
    Volt::route('admin/orders', 'admin.manage-orders')->name('admin.orders');
});
