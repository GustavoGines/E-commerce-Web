@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 dark:border-slate-700 dark:bg-slate-900/50 dark:text-gray-200 focus:border-[var(--color-primary)] dark:focus:border-[var(--color-primary)] focus:ring-[var(--color-primary)] dark:focus:ring-[var(--color-primary)] rounded-xl shadow-sm transition-colors duration-300']) }}>
