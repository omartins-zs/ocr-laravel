@props([
    'title' => 'Sem dados',
    'description' => 'Nenhum registro encontrado.',
])

<div {{ $attributes->class(['card-surface p-10 text-center']) }}>
    <div class="mx-auto mb-4 h-12 w-12 rounded-2xl bg-slate-200 dark:bg-slate-800"></div>
    <h3 class="text-lg font-semibold text-slate-800 dark:text-slate-100">{{ $title }}</h3>
    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ $description }}</p>
    {{ $slot }}
</div>
