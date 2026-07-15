<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;
    public string $turnstileToken = '';

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        if (config('services.turnstile.enabled')) {
            $this->validate([
                'turnstileToken' => ['required', function ($attribute, $value, $fail) {
                    $response = \Illuminate\Support\Facades\Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                        'secret' => config('services.turnstile.secret_key'),
                        'response' => $value,
                        'remoteip' => request()->ip(),
                    ]);
                    if (!$response->json('success')) {
                        $fail('Verificación de seguridad fallida. Por favor recarga e intenta nuevamente.');
                    }
                }],
            ]);
        }

        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('home'), navigate: true);
    }
}; ?>

<div>
    <div class="mb-5 text-center">
        <h2 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">Bienvenido de vuelta</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Ingresa tus credenciales para acceder a tu cuenta.</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <!-- Error Message (Google OAuth errors etc) -->
    @if (session('error'))
        <div class="mb-4 font-medium text-sm text-red-600 dark:text-red-400 p-3 bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-200 dark:border-red-800">
            {{ session('error') }}
        </div>
    @endif

    <form wire:submit="login" class="space-y-4">
        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="form.email" id="email" class="block mt-1 w-full py-2.5 px-4" type="email" name="email" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-password-input wire:model="form.password" id="password" name="password" required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block">
            <label for="remember" class="inline-flex items-center">
                <input wire:model="form.remember" id="remember" type="checkbox" class="rounded border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-[var(--color-primary)] shadow-sm focus:ring-[var(--color-primary)] dark:focus:ring-[var(--color-primary)]" name="remember">
                <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Remember me') }}</span>
            </label>
        </div>

        <!-- Turnstile -->
        @if(config('services.turnstile.enabled'))
            <div wire:ignore class="mt-4">
                <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}" data-callback="setTurnstileTokenLogin"></div>
                <script>
                    function setTurnstileTokenLogin(token) {
                        @this.set('turnstileToken', token);
                    }
                </script>
                @once
                    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
                @endonce
            </div>
            <x-input-error :messages="$errors->get('turnstileToken')" class="mt-2" />
        @endif

        <div class="flex items-center justify-between pt-1">
            @if (Route::has('password.request'))
                <a class="text-sm font-medium text-[var(--color-primary)] hover:opacity-80 transition-opacity" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button>
                {{ __('Log in') }}
            </x-primary-button>
        </div>
        
        <div class="text-center mt-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                ¿No tienes una cuenta? 
                <a href="{{ route('register') }}" class="font-bold text-[var(--color-primary)] hover:underline">Regístrate aquí</a>
            </p>
        </div>

    </form>

    <div class="pt-3">
        <div class="flex items-center">
            <div class="flex-grow border-t border-gray-200 dark:border-gray-700"></div>
            <span class="px-3 text-sm text-gray-500 bg-transparent">O continuar con</span>
            <div class="flex-grow border-t border-gray-200 dark:border-gray-700"></div>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-3">
            <button 
                type="button"
                onclick="window.location.href='{{ route('google.login') }}'"
                class="w-full inline-flex justify-center py-2.5 px-4 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm bg-white dark:bg-gray-900/50 text-sm font-bold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    <path fill="none" d="M1 1h22v22H1z"/>
                </svg>
                Continuar con Google
            </button>
        </div>
    </div>
</div>
