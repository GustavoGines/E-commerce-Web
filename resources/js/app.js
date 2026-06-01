// ═══════════════════════════════════════════════════════════════════════════
// TEMA — Funciones globales puras (sin depender de Alpine)
// Estas funcionan SIEMPRE, sin importar el estado de inicialización de Alpine
// ═══════════════════════════════════════════════════════════════════════════

window.POS = window.POS || {};

// Aplica el tema al <html> y persiste la preferencia
POS.applyTheme = function (dark) {
    document.documentElement.classList.toggle('dark', dark);
    localStorage.theme = dark ? 'dark' : 'light';
};

// Alterna el tema
POS.toggleTheme = function () {
    const isDark = !document.documentElement.classList.contains('dark');
    POS.applyTheme(isDark);
    // Sincronizar con Alpine Store si ya está disponible
    try { window.Alpine && Alpine.store('theme') && (Alpine.store('theme').dark = isDark); } catch (_) {}
    // Disparar evento para que otros componentes reaccionen
    window.dispatchEvent(new CustomEvent('pos:theme-changed', { detail: { dark: isDark } }));
};

// Leer el tema guardado (llamado antes de que Alpine cargue, anti-flash)
POS.initTheme = function () {
    const dark = localStorage.theme === 'dark' ||
        (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches);
    POS.applyTheme(dark);
};

// ═══════════════════════════════════════════════════════════════════════════
// CARRITO — Funciones globales puras
// ═══════════════════════════════════════════════════════════════════════════

POS.openCart = function () {
    try { window.Alpine && Alpine.store('cart') && Alpine.store('cart').show(); } catch (_) {}
    // Fallback: evento DOM clásico
    window.dispatchEvent(new CustomEvent('open-cart'));
};

POS.closeCart = function () {
    try { window.Alpine && Alpine.store('cart') && Alpine.store('cart').hide(); } catch (_) {}
};

// ═══════════════════════════════════════════════════════════════════════════
// ALPINE — Registrar stores globales (Livewire inyecta Alpine)
// ═══════════════════════════════════════════════════════════════════════════

document.addEventListener('alpine:init', () => {

    Alpine.store('theme', {
        dark: document.documentElement.classList.contains('dark'),

        toggle() { POS.toggleTheme(); this.dark = !this.dark; },
        apply()  { POS.applyTheme(this.dark); }
    });

    Alpine.store('cart', {
        open: false,
        show()   { this.open = true;  },
        hide()   { this.open = false; },
        toggle() { this.open = !this.open; }
    });

    // Mantener el store sincronizado cuando el tema cambia por otra vía
    window.addEventListener('pos:theme-changed', (e) => {
        Alpine.store('theme').dark = e.detail.dark;
    });
});

// ═══════════════════════════════════════════════════════════════════════════
// Livewire SPA: re-sincronizar store de tema tras navegación
// ═══════════════════════════════════════════════════════════════════════════
document.addEventListener('livewire:navigated', () => {
    const dark = localStorage.theme === 'dark' ||
        (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches);
    POS.applyTheme(dark);
    try { Alpine.store('theme').dark = dark; } catch (_) {}
});
