<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // No tocar la DB aquí
    }

    public function boot(): void
    {
        $theme = 'stealth';
        try {
            $settings = \Illuminate\Support\Facades\Cache::rememberForever('store_settings', function () {
                return \App\Models\StoreSetting::first();
            });

            $dbTheme = ($settings && $settings->theme_name) ? $settings->theme_name : 'stealth';
            if ($dbTheme !== 'stealth') {
                $theme = $dbTheme;
            }
        } catch (\Exception $e) {}

        \Illuminate\Support\Facades\View::share('activeTheme', $theme);
        app()->singleton('activeTheme', fn() => $theme);

        // Fix for Laragon subdirectories: Force Laravel to generate URLs with the correct base path
        if (config('app.url') && ! app()->environment('testing')) {
            URL::forceRootUrl(config('app.url'));

            if (config('app.tunnel_active') || str_starts_with(config('app.url'), 'https://')) {
                URL::forceScheme('https');
            }

            $path = parse_url(config('app.url'), PHP_URL_PATH) ?? '';
            $path = trim($path, '/');

            Livewire::setUpdateRoute(function ($handle) use ($path) {
                return Route::post($path.'/livewire/update', $handle)
                    ->middleware(['web']);
            });
        }

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\PaymentApproved::class,
            \App\Listeners\UpdateOrderOnPayment::class,
        );
    }
}
