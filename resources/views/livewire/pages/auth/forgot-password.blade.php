<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $email = '';

    /**
     * Send a password reset link to the provided email address.
     */
    public function sendPasswordResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $status = Password::sendResetLink(
            $this->only('email')
        );

        if ($status != Password::RESET_LINK_SENT) {
            $this->addError('email', __($status));
            return;
        }

        $this->reset('email');
        session()->flash('status', __($status));
        
        // Disparamos el evento para que Alpine.js inicie el temporizador de 60 segundos
        $this->dispatch('link-sent');
    }
}; ?>

<div x-data="{ cooldown: 0 }" 
     @link-sent.window="cooldown = 60; let interval = setInterval(() => { cooldown--; if(cooldown <= 0) clearInterval(interval); }, 1000)">
    
    <div class="mb-6 text-center">
        <h2 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">Recuperar Contraseña</h2>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            ¿Olvidaste tu contraseña? No te preocupes. Escribe tu correo electrónico y te enviaremos un enlace seguro para crear una nueva.
        </p>
    </div>

    <!-- Session Status -->
    @if (session('status'))
        <div class="mb-6 p-4 rounded-xl bg-green-50 border border-green-200 text-sm font-medium text-green-700 text-center shadow-sm">
            Hemos enviado el enlace a tu correo. <br> (Recuerda revisar la carpeta de Spam)
        </div>
    @endif

    <form wire:submit="sendPasswordResetLink" class="space-y-4">
        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input wire:model="email" id="email" class="block mt-1 w-full py-2.5 px-4 rounded-xl" type="email" name="email" required autofocus placeholder="ejemplo@correo.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="pt-2">
            <button type="submit" 
                    x-bind:disabled="cooldown > 0"
                    wire:loading.attr="disabled"
                    class="w-full flex justify-center items-center py-3.5 px-4 border border-transparent rounded-xl shadow-md text-sm font-bold text-white transition-all duration-200 transform active:scale-95 hover:shadow-lg disabled:opacity-60 disabled:cursor-not-allowed disabled:transform-none disabled:shadow-none"
                    style="background: linear-gradient(135deg, var(--color-primary), color-mix(in srgb, var(--color-primary) 60%, #7c3aed))">
                
                {{-- Estado Normal --}}
                <span wire:loading.remove wire:target="sendPasswordResetLink" x-show="cooldown === 0">
                    Enviar enlace de recuperación
                </span>
                
                {{-- Estado Cargando (Esperando servidor) --}}
                <div wire:loading.flex wire:target="sendPasswordResetLink" class="flex-row items-center justify-center gap-2 w-full">
                    <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Enviando...</span>
                </div>

                {{-- Estado Cooldown (Temporizador de 60s) --}}
                <span wire:loading.remove wire:target="sendPasswordResetLink" x-show="cooldown > 0" style="display: none;">
                    Reenviar enlace en <span x-text="cooldown"></span>s
                </span>

            </button>
        </div>
        
        <div class="text-center mt-6">
            <a href="{{ route('login') }}" class="text-sm font-bold text-gray-500 dark:text-gray-400 hover:text-[var(--color-primary)] transition-colors inline-flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Volver al inicio de sesión
            </a>
        </div>
    </form>
</div>
