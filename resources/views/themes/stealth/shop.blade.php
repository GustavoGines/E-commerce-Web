<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-2xl text-slate-900 dark:text-white leading-tight">
                Catálogo de Hardware
            </h2>
            <div class="text-sm text-slate-500 dark:text-slate-400">
                La mejor selección de componentes.
            </div>
        </div>
    </x-slot>

    <div class="py-12 bg-slate-50 dark:bg-[#080c14] min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Wrapper to mimic stealth style --}}
            <style>
                .dot-grid {
                    background-image: radial-gradient(circle, rgba(255,255,255,0.06) 1px, transparent 1px);
                    background-size: 28px 28px;
                }
            </style>
            
            <div class="relative z-10 flex flex-col flex-grow">
                <livewire:product-grid />
            </div>
        </div>
    </div>
</x-app-layout>
