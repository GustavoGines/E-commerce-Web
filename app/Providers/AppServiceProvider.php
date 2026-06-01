<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Fix for Laragon subdirectories: Force Laravel to generate URLs with the correct base path
        if (config('app.url') && !app()->environment('testing')) {
            \Illuminate\Support\Facades\URL::forceRootUrl(config('app.url'));
            
            if (env('TUNNEL_ACTIVE') || str_starts_with(config('app.url'), 'https://')) {
                \Illuminate\Support\Facades\URL::forceScheme('https');
            }
            
            $path = parse_url(config('app.url'), PHP_URL_PATH) ?? '';
            $path = trim($path, '/');
            
            \Livewire\Livewire::setUpdateRoute(function ($handle) use ($path) {
                return \Illuminate\Support\Facades\Route::post($path . '/livewire/update', $handle)
                    ->middleware(['web']);
            });
        }
    }
}
