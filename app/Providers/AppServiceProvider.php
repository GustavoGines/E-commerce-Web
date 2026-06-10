<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Fix for Laragon subdirectories: Force Laravel to generate URLs with the correct base path
        if (config('app.url') && ! app()->environment('testing')) {
            URL::forceRootUrl(config('app.url'));

            if (env('TUNNEL_ACTIVE') || str_starts_with(config('app.url'), 'https://')) {
                URL::forceScheme('https');
            }

            $path = parse_url(config('app.url'), PHP_URL_PATH) ?? '';
            $path = trim($path, '/');

            Livewire::setUpdateRoute(function ($handle) use ($path) {
                return Route::post($path.'/livewire/update', $handle)
                    ->middleware(['web']);
            });
        }
    }
}
