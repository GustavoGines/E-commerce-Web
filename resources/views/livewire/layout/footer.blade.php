<?php

use Livewire\Volt\Component;

new class extends Component {
    public $theme = 'stealth';
    public $storeName = 'Premium Hardware';

    public function mount()
    {
        $settings = \App\Models\StoreSetting::first();
        if ($settings) {
            $this->theme = $settings->theme_name ?? 'stealth';
            $this->storeName = $settings->store_name ?? 'Premium Hardware';
        }
    }
}; ?>

<footer class="w-full mt-auto relative {{ $theme === 'luxury' ? 'bg-[#030712] border-t border-white/5' : 'bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800' }} transition-colors duration-300">
    @if($theme === 'luxury')
        @include('themes.luxury.footer')
    @else
        {{-- ==========================================
             MODERN / STEALTH THEME FOOTER
             ========================================== --}}
        
        {{-- 1. Franja de Confianza (Trust Banner) Superior --}}
        <div class="border-y border-gray-200 bg-gray-50 dark:bg-slate-800/50 overflow-hidden">
            <div class="max-w-7xl mx-auto px-0 sm:px-6 lg:px-8 py-6 md:py-12">
                {{-- Contenedor con Scroll Horizontal en móvil (Swipeable) --}}
                <div class="flex md:grid md:grid-cols-3 gap-4 md:gap-8 overflow-x-auto snap-x snap-mandatory hide-scrollbar px-4 sm:px-0 text-center md:divide-x divide-gray-200 dark:divide-slate-700">
                    
                    {{-- Bloque 1: Retiro en Local --}}
                    <div class="flex flex-col items-center justify-center p-4 min-w-[250px] snap-center bg-white md:bg-transparent rounded-2xl md:rounded-none shadow-sm md:shadow-none border md:border-none border-gray-100">
                        <div class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-red-100 dark:bg-red-900/30 text-[var(--color-primary)] flex items-center justify-center mb-3 md:mb-4">
                            <svg class="w-5 h-5 md:w-6 md:h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <h4 class="text-xs md:text-sm font-black text-gray-900 dark:text-white uppercase tracking-widest mb-1">Retiro por Sucursal</h4>
                        <p class="text-[10px] md:text-xs text-gray-500 dark:text-gray-400 font-medium leading-tight">Comprá online y pasá a retirar por nuestro local al instante.</p>
                    </div>

                    {{-- Bloque 2: WhatsApp --}}
                    <div class="flex flex-col items-center justify-center p-4 min-w-[250px] snap-center bg-white md:bg-transparent rounded-2xl md:rounded-none shadow-sm md:shadow-none border md:border-none border-gray-100">
                        <div class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-green-100 dark:bg-green-900/30 text-green-600 flex items-center justify-center mb-3 md:mb-4">
                            <svg class="w-5 h-5 md:w-6 md:h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                            </svg>
                        </div>
                        <h4 class="text-xs md:text-sm font-black text-gray-900 dark:text-white uppercase tracking-widest mb-1">¿Dudas? Consultanos</h4>
                        <a href="https://wa.me/5493705075839" target="_blank" class="text-[10px] md:text-xs font-bold text-green-600 hover:text-green-700 hover:underline">
                            370 507-5839
                        </a>
                    </div>

                    {{-- Bloque 3: Compra Segura --}}
                    <div class="flex flex-col items-center justify-center p-4 min-w-[250px] snap-center bg-white md:bg-transparent rounded-2xl md:rounded-none shadow-sm md:shadow-none border md:border-none border-gray-100">
                        <div class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 flex items-center justify-center mb-3 md:mb-4">
                            <svg class="w-5 h-5 md:w-6 md:h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <h4 class="text-xs md:text-sm font-black text-gray-900 dark:text-white uppercase tracking-widest mb-1">Compra 100% Segura</h4>
                        <p class="text-[10px] md:text-xs text-gray-500 dark:text-gray-400 font-medium leading-tight">Protegemos tus datos y garantizamos tu reserva.</p>
                    </div>

                </div>
            </div>
            
            {{-- Estilo para ocultar la barra de scroll horizontal en Tailwind --}}
            <style>
                .hide-scrollbar::-webkit-scrollbar { display: none; }
                .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
            </style>
        </div>

        {{-- 2. Footer Principal --}}
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                
                {{-- Columna 1: Marca --}}
                <div class="md:col-span-1">
                    <div class="mb-6 relative h-16 w-full flex items-center">
                        @php $settings = \App\Models\StoreSetting::first(); @endphp
                        @if(isset($settings) && $settings->logo_url)
                            <img src="{{ asset('storage/logos/logo-cjg-horizontal.png') }}" alt="Logo" class="absolute pointer-events-none drop-shadow-sm" style="top: 50%; left: 0; width: 220px; height: auto; transform: translateY(-50%);">
                        @else
                            <div class="flex items-center gap-2">
                                <svg class="w-6 h-6 text-[var(--color-primary)]" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L2 22h20L12 2zm0 4.5l6.5 13h-13L12 6.5z"/></svg>
                                <h4 class="text-xl font-black text-gray-900 dark:text-white tracking-tight">{{ $storeName }}</h4>
                            </div>
                        @endif
                    </div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm mb-6 leading-relaxed mt-2">
                        El mayor catálogo de controles remotos y electrónica. Ventas por mayor y menor.
                    </p>
                </div>

                {{-- Columna 2: Navegación --}}
                <div>
                    <h5 class="text-xs font-black text-gray-900 dark:text-white uppercase tracking-widest mb-4">Navegación</h5>
                    <ul class="space-y-3">
                        <li><a href="{{ route('home') }}" class="text-gray-500 dark:text-gray-400 hover:text-[var(--color-primary)] text-sm font-medium transition-colors">Inicio</a></li>
                        <li><a href="{{ route('shop') }}" class="text-gray-500 dark:text-gray-400 hover:text-[var(--color-primary)] text-sm font-medium transition-colors">Catálogo Completo</a></li>
                        <li><a href="{{ route('login') }}" class="text-gray-500 dark:text-gray-400 hover:text-[var(--color-primary)] text-sm font-medium transition-colors">Iniciar Sesión / Registro</a></li>
                    </ul>
                </div>

                {{-- Columna 3: Medios de Pago --}}
                <div>
                    <h5 class="text-xs font-black text-gray-900 dark:text-white uppercase tracking-widest mb-4">Medios de Pago</h5>
                    <div class="flex flex-wrap gap-2">
                        <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs font-bold rounded">Efectivo</span>
                        <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs font-bold rounded">Transferencia</span>
                        <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs font-bold rounded">MercadoPago</span>
                    </div>
                    <p class="text-xs text-gray-400 mt-4 italic">* Los pagos se coordinan al retirar el pedido en el local.</p>
                </div>

                {{-- Columna 4: Contacto --}}
                <div>
                    <h5 class="text-xs font-black text-gray-900 dark:text-white uppercase tracking-widest mb-4">Contacto</h5>
                    <ul class="space-y-4">
                        <li>
                            <a href="https://wa.me/5493705075839" target="_blank" class="flex items-center gap-3 text-gray-500 hover:text-green-600 transition-colors group">
                                <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-green-100 flex items-center justify-center transition-colors">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                                </div>
                                <div>
                                    <span class="block text-[10px] font-bold uppercase tracking-widest text-gray-400">WhatsApp</span>
                                    <span class="text-sm font-bold">370 507 5839</span>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="mailto:jcgelectronicaoficial@gmail.com" class="flex items-center gap-3 text-gray-500 hover:text-[var(--color-primary)] transition-colors group">
                                <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-red-50 flex items-center justify-center transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                </div>
                                <div>
                                    <span class="block text-[10px] font-bold uppercase tracking-widest text-gray-400">Email</span>
                                    <span class="text-sm font-bold">jcgelectronica...</span>
                                </div>
                            </a>
                        </li>
                    </ul>
                </div>

            </div>

            <div class="mt-12 pt-8 border-t border-gray-200 dark:border-slate-800 flex flex-col md:flex-row items-center justify-between gap-4">
                <p class="text-gray-400 text-xs font-medium">
                    &copy; {{ date('Y') }} {{ $storeName }}. Todos los derechos reservados.
                </p>
            </div>
        </div>

    @endif
</footer>
