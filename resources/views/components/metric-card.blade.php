@props([
    'title',
    'value' => 0,
    'hint' => null,
    'accent' => 'brand',
    'icon' => 'heroicon-o-chart-bar',
])

@php
    $accentClasses = [
        'brand' => 'from-brand-600 to-brand-500',
        'success' => 'from-emerald-600 to-emerald-500',
        'warning' => 'from-amber-600 to-amber-500',
        'danger' => 'from-red-600 to-red-500',
        'info' => 'from-sky-600 to-sky-500',
    ];
@endphp

<article {{ $attributes->class(['card-surface p-5']) }}>
    <div class="flex items-start justify-between gap-4">
        <div>
            <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase dark:text-slate-400">{{ $title }}</p>
            <p class="mt-2 text-3xl font-bold text-slate-800 dark:text-white">{{ $value }}</p>
            @if ($hint)
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ $hint }}</p>
            @endif
        </div>
        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br {{ $accentClasses[$accent] ?? $accentClasses['brand'] }} text-white shadow-sm">
            <x-dynamic-component :component="$icon" class="h-5 w-5" />
        </div>
    </div>
</article>
