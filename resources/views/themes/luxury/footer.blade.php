{{-- ==========================================
     LUXURY THEME FOOTER (Turnstime/Etonal Fusion)
     ========================================== --}}
<div class="w-full bg-[#030712] text-white overflow-hidden border-t border-white/5">

    {{-- 1. Brand Carousel (Monochrome) --}}
    <div class="border-b border-white/5 py-12 relative overflow-hidden flex items-center">
        <div class="absolute inset-y-0 left-0 w-32 bg-gradient-to-r from-[#030712] to-transparent z-10"></div>
        <div class="absolute inset-y-0 right-0 w-32 bg-gradient-to-l from-[#030712] to-transparent z-10"></div>
        
        {{-- Marquee Animation Container --}}
        <div class="flex space-x-16 min-w-max animate-[marquee_30s_linear_infinite] px-8">
            @for ($i = 0; $i < 3; $i++)
                <div class="flex items-center gap-16">
                    <span class="text-2xl font-black tracking-widest uppercase text-gray-500 opacity-50 hover:opacity-100 hover:text-white transition-all cursor-default">NVIDIA</span>
                    <span class="text-2xl font-black tracking-widest uppercase text-gray-500 opacity-50 hover:opacity-100 hover:text-white transition-all cursor-default">AMD</span>
                    <span class="text-2xl font-black tracking-widest uppercase text-gray-500 opacity-50 hover:opacity-100 hover:text-white transition-all cursor-default">CORSAIR</span>
                    <span class="text-2xl font-black tracking-widest uppercase text-gray-500 opacity-50 hover:opacity-100 hover:text-white transition-all cursor-default">ASUS ROG</span>
                    <span class="text-2xl font-black tracking-widest uppercase text-gray-500 opacity-50 hover:opacity-100 hover:text-white transition-all cursor-default">INTEL</span>
                    <span class="text-2xl font-black tracking-widest uppercase text-gray-500 opacity-50 hover:opacity-100 hover:text-white transition-all cursor-default">GIGABYTE</span>
                </div>
            @endfor
        </div>
        
        <style>
            @keyframes marquee {
                0% { transform: translateX(0); }
                100% { transform: translateX(-33.33%); }
            }
        </style>
    </div>

    {{-- 2. Full-width Promo Banner --}}
    <div class="relative w-full h-[400px] flex items-center justify-center border-b border-white/5">
        <div class="absolute inset-0 bg-[#0a0f1c] z-0"></div>
        <img src="https://images.unsplash.com/photo-1542393545-10f5cde2c810?q=80&w=1600&auto=format&fit=crop" 
             alt="Promo Setup" class="absolute inset-0 w-full h-full object-cover opacity-20 mix-blend-lighten z-0">
        <div class="absolute inset-0 bg-gradient-to-t from-[#030712] via-transparent to-transparent z-10"></div>
        
        <div class="relative z-20 text-center px-4 max-w-3xl mx-auto">
            <h2 class="text-4xl md:text-6xl font-black tracking-tighter text-white mb-6">Eleva tu <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-purple-500">Rendimiento</span></h2>
            <p class="text-gray-400 text-lg mb-8 font-light">Equípate con el hardware que utilizan los profesionales. Sin compromisos, solo potencia bruta.</p>
            <a href="{{ route('shop') }}" class="inline-flex items-center gap-2 bg-white text-black font-bold uppercase tracking-widest text-[11px] px-8 py-4 hover:bg-gray-200 hover:scale-105 transition-all duration-300">
                Ver Colección
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
            </a>
        </div>
    </div>

    {{-- 3. FAQ Section (2 Columnas) --}}
    <div class="max-w-7xl mx-auto px-6 lg:px-8 py-24 border-b border-white/5" x-data="{ activeAccordion: 1 }">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
            
            {{-- FAQ Left: Image --}}
            <div class="relative aspect-square md:aspect-[4/3] rounded-3xl overflow-hidden bg-[#0a0f1c] border border-white/10 group">
                <img src="https://images.unsplash.com/photo-1587202372634-32705e3bf49c?q=80&w=800&auto=format&fit=crop" 
                     alt="FAQ Hardware" class="w-full h-full object-cover opacity-60 group-hover:scale-105 transition-transform duration-1000">
                <div class="absolute inset-0 bg-gradient-to-tr from-black via-transparent to-transparent"></div>
                <div class="absolute bottom-8 left-8 right-8">
                    <h3 class="text-3xl font-black text-white tracking-tight">Resolvemos tus <br>dudas técnicas.</h3>
                </div>
            </div>

            {{-- FAQ Right: Accordion --}}
            <div class="space-y-4">
                {{-- Accordion Item 1 --}}
                <div class="border-b border-white/10 pb-4">
                    <button @click="activeAccordion = activeAccordion === 1 ? null : 1" class="w-full flex justify-between items-center py-4 text-left group">
                        <span class="text-lg font-bold text-gray-200 group-hover:text-[var(--color-primary)] transition-colors">¿Tienen garantía los componentes?</span>
                        <svg class="w-5 h-5 text-gray-500 transform transition-transform duration-300" :class="activeAccordion === 1 ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div x-show="activeAccordion === 1" x-collapse>
                        <p class="text-gray-400 text-sm font-light pb-4">Sí, todos nuestros productos cuentan con la garantía oficial del fabricante, que oscila entre 1 y 3 años dependiendo de la marca y el componente específico.</p>
                    </div>
                </div>

                {{-- Accordion Item 2 --}}
                <div class="border-b border-white/10 pb-4">
                    <button @click="activeAccordion = activeAccordion === 2 ? null : 2" class="w-full flex justify-between items-center py-4 text-left group">
                        <span class="text-lg font-bold text-gray-200 group-hover:text-[var(--color-primary)] transition-colors">¿Realizan envíos a todo el país?</span>
                        <svg class="w-5 h-5 text-gray-500 transform transition-transform duration-300" :class="activeAccordion === 2 ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div x-show="activeAccordion === 2" x-collapse>
                        <p class="text-gray-400 text-sm font-light pb-4">Absolutamente. Realizamos envíos asegurados a todo el territorio nacional a través de correos logísticos premium para garantizar la integridad de tu hardware.</p>
                    </div>
                </div>

                {{-- Accordion Item 3 --}}
                <div class="border-b border-white/10 pb-4">
                    <button @click="activeAccordion = activeAccordion === 3 ? null : 3" class="w-full flex justify-between items-center py-4 text-left group">
                        <span class="text-lg font-bold text-gray-200 group-hover:text-[var(--color-primary)] transition-colors">¿Cómo funciona el precio mayorista?</span>
                        <svg class="w-5 h-5 text-gray-500 transform transition-transform duration-300" :class="activeAccordion === 3 ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div x-show="activeAccordion === 3" x-collapse>
                        <p class="text-gray-400 text-sm font-light pb-4">El precio mayorista se aplica automáticamente en tu carrito cuando alcanzas la cantidad mínima requerida por producto. Ideal para ensambladores y empresas.</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- 4. Footer Clásico (4 Columnas) --}}
    <div class="max-w-7xl mx-auto px-6 lg:px-8 py-20">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 lg:gap-8">
            
            {{-- Col 1: Brand Info --}}
            <div class="lg:col-span-1">
                <h4 class="text-2xl font-black text-white tracking-tighter mb-6">{{ $storeName }}</h4>
                <p class="text-gray-500 text-sm leading-relaxed mb-8">
                    @php 
                        $settings = \App\Models\StoreSetting::getSettings();
                        $tagline = $settings->store_tagline ?? 'Redefiniendo el estándar del hardware de alto rendimiento. Diseñado para quienes no aceptan compromisos.';
                    @endphp
                    {{ $tagline }}
                </p>
                <div class="flex items-center gap-4">
                    <a href="#" class="w-10 h-10 rounded-full bg-white/5 flex items-center justify-center text-gray-400 hover:bg-white hover:text-black transition-colors"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/></svg></a>
                    <a href="#" class="w-10 h-10 rounded-full bg-white/5 flex items-center justify-center text-gray-400 hover:bg-white hover:text-black transition-colors"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg></a>
                </div>
            </div>

            {{-- Col 2: Descubrir --}}
            <div class="lg:col-span-1">
                <h5 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-6">Descubrir</h5>
                <ul class="space-y-4">
                    <li><a href="{{ route('shop') }}" class="text-gray-500 hover:text-[var(--color-primary)] transition-colors text-sm">Catálogo de Hardware</a></li>
                    <li><a href="#" class="text-gray-500 hover:text-[var(--color-primary)] transition-colors text-sm">Tarjetas Gráficas</a></li>
                    <li><a href="#" class="text-gray-500 hover:text-[var(--color-primary)] transition-colors text-sm">Procesadores</a></li>
                    <li><a href="#" class="text-gray-500 hover:text-[var(--color-primary)] transition-colors text-sm">Ofertas Especiales</a></li>
                </ul>
            </div>

            {{-- Col 3: Soporte --}}
            <div class="lg:col-span-1">
                <h5 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-6">Soporte</h5>
                <ul class="space-y-4">
                    <li><a href="#" class="text-gray-500 hover:text-[var(--color-primary)] transition-colors text-sm">Garantía Premium</a></li>
                    <li><a href="#" class="text-gray-500 hover:text-[var(--color-primary)] transition-colors text-sm">Rastreo de Pedidos</a></li>
                    <li><a href="#" class="text-gray-500 hover:text-[var(--color-primary)] transition-colors text-sm">Centro de Ayuda</a></li>
                    <li><a href="#" class="text-gray-500 hover:text-[var(--color-primary)] transition-colors text-sm">Términos y Condiciones</a></li>
                </ul>
            </div>

            {{-- Col 4: Newsletter --}}
            <div class="lg:col-span-1">
                <h5 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-6">Newsletter</h5>
                <p class="text-gray-500 text-sm mb-4">Acceso anticipado a hardware exclusivo y ofertas.</p>
                <form class="flex relative mb-8">
                    <input type="email" placeholder="Correo electrónico" class="w-full bg-white/5 border border-white/10 rounded-lg py-3 pl-4 pr-12 text-white placeholder-gray-600 focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)] transition-all text-sm">
                    <button type="button" class="absolute right-0 top-0 bottom-0 px-4 text-gray-400 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </button>
                </form>
                
                {{-- Payment Trust --}}
                <div class="flex items-center gap-3">
                    <div class="w-8 h-5 bg-white/10 rounded flex items-center justify-center text-[8px] text-gray-300 font-bold">VISA</div>
                    <div class="w-8 h-5 bg-white/10 rounded flex items-center justify-center text-[8px] text-gray-300 font-bold">MC</div>
                    <div class="w-8 h-5 bg-white/10 rounded flex items-center justify-center text-[8px] text-gray-300 font-bold">MP</div>
                    <div class="w-8 h-5 bg-white/10 rounded flex items-center justify-center text-[8px] text-gray-300 font-bold">AMX</div>
                </div>
            </div>

        </div>
    </div>

    {{-- Bottom Copyright --}}
    <div class="border-t border-white/5 bg-[#010205] py-6">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 text-center md:text-left flex flex-col md:flex-row justify-between items-center gap-4">
            <p class="text-gray-600 text-xs font-light">
                &copy; {{ date('Y') }} {{ $storeName }}. Creado con tecnología <span class="text-white">Laravel</span> & <span class="text-white">Livewire</span>.
            </p>
            <div class="flex gap-6">
                <a href="#" class="text-gray-600 hover:text-white text-xs transition-colors">Privacidad</a>
                <a href="#" class="text-gray-600 hover:text-white text-xs transition-colors">Cookies</a>
            </div>
        </div>
    </div>
</div>
