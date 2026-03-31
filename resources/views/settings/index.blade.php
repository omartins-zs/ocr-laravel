@extends('layouts.app')

@section('page-title', 'Configuracoes')

@section('content')
    <section class="page-header">
        <div class="page-title-wrap">
            <h2 class="page-title">
                <x-heroicon-o-cog-6-tooth class="h-6 w-6 text-brand-600 dark:text-brand-300" />
                Configuracoes da plataforma
            </h2>
            <p class="page-subtitle">Parametros de OCR, upload, filas e experiencia da UI.</p>
        </div>
    </section>

    <form method="POST" action="{{ route('settings.update') }}" class="space-y-6">
        @csrf

        @foreach ($settings as $group => $items)
            <article class="card-surface p-5">
                <h3 class="mb-4 flex items-center gap-2 text-lg font-semibold text-slate-800 capitalize dark:text-slate-100">
                    <x-heroicon-o-adjustments-horizontal class="h-5 w-5 text-brand-600 dark:text-brand-300" />
                    {{ str_replace('_', ' ', $group) }}
                </h3>
                <div class="grid gap-4 md:grid-cols-2">
                    @foreach ($items as $index => $setting)
                        <div>
                            <input type="hidden" name="settings[{{ $setting->id }}][id]" value="{{ $setting->id }}">
                            <label class="label-control">{{ $setting->key }}</label>
                            <input
                                type="text"
                                name="settings[{{ $setting->id }}][value]"
                                value="{{ old('settings.'.$setting->id.'.value', $setting->value) }}"
                                class="input-control">
                            @if ($setting->description)
                                <p class="mt-1 text-xs text-slate-500">{{ $setting->description }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </article>
        @endforeach

        <div class="flex justify-end">
            <button type="submit" class="btn-primary">Salvar configuracoes</button>
        </div>
    </form>
@endsection
