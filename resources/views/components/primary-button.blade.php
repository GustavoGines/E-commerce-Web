<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-6 py-3 rounded-full font-bold text-xs text-white uppercase tracking-widest hover:opacity-90 focus:outline-none transition-all shadow-lg', 'style' => 'background-color: var(--color-primary); box-shadow: 0 4px 14px 0 var(--color-primary-glow);']) }}>
    {{ $slot }}
</button>
