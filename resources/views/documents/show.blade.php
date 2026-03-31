@extends('layouts.app')

@section('page-title', 'Detalhe do documento')

@section('content')
    @php
        $currentStatus = $document->status instanceof \BackedEnum ? $document->status->value : (string) $document->status;
    @endphp

    <section class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-xs uppercase tracking-wide text-slate-500">Documento {{ $document->uuid }}</p>
            <h2 class="text-2xl font-bold text-slate-800 dark:text-slate-100">{{ $document->original_filename }}</h2>
            <div class="mt-2 flex items-center gap-2">
                <x-status-badge :status="$document->status" />
                <span class="text-xs text-slate-500">Etapa: {{ $document->processing_stage->value ?? $document->processing_stage }}</span>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('documents.download', $document) }}" class="btn-secondary">
                <x-heroicon-o-arrow-down-tray class="h-4 w-4" />
                Download
            </a>
            @if ($currentStatus === 'failed')
                <form method="POST" action="{{ route('documents.reprocess', $document) }}" onsubmit="return confirm('Deseja reprocessar este documento?')">
                    @csrf
                    <button class="btn-secondary" type="submit">
                        <i class="fa-solid fa-repeat"></i>
                        Reprocessar
                    </button>
                </form>
            @endif
            <a href="{{ route('history') }}" class="btn-primary">Voltar para historico</a>
        </div>
    </section>

    <section class="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-metric-card title="Paginas" :value="$document->total_pages ?? 0" />
        <x-metric-card title="Confianca OCR" :value="$document->overall_confidence ? number_format($document->overall_confidence, 2).'%' : '-'" accent="info" />
        <x-metric-card title="Motor" :value="$document->latestExtraction?->source_engine ?? '-'" accent="success" />
        <x-metric-card title="Atualizado" :value="$document->updated_at?->format('d/m H:i') ?? '-'" accent="warning" />
    </section>

    <section class="grid gap-6 xl:grid-cols-2">
        <article class="card-surface overflow-hidden">
            <div class="border-b border-slate-200 px-4 py-3 dark:border-slate-800">
                <h3 class="font-semibold text-slate-800 dark:text-slate-100">Visualizacao original</h3>
            </div>
            <div class="h-[760px] bg-slate-100 dark:bg-slate-950">
                <iframe src="{{ $previewUrl }}" class="h-full w-full"></iframe>
            </div>
        </article>

        <article class="card-surface p-5">
            <div class="mb-4">
                <h3 class="font-semibold text-slate-800 dark:text-slate-100">Campos extraidos</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400">Resultado estruturado retornado pelo OCR.</p>
            </div>

            @if (! $document->latestExtraction || $document->latestExtraction->fields->isEmpty())
                <x-empty-state title="Sem campos extraidos" description="Aguarde o processamento ou solicite reprocessamento." />
            @else
                <div class="max-h-[520px] space-y-3 overflow-y-auto pr-2">
                    @foreach ($document->latestExtraction->fields as $field)
                        <div class="rounded-xl border border-slate-200 p-3 dark:border-slate-700">
                            <p class="text-xs uppercase tracking-wide text-slate-500">{{ $field->field_key }}</p>
                            <p class="font-medium text-slate-800 dark:text-slate-100">{{ $field->label }}</p>
                            <p class="mt-2 break-words text-sm text-slate-700 dark:text-slate-200">
                                {{ $field->value ?: '-' }}
                            </p>
                            <div class="mt-2 grid gap-2 text-xs text-slate-500 sm:grid-cols-2">
                                <p>Normalizado: {{ $field->normalized_value ?: '-' }}</p>
                                <p>Confianca: {{ $field->confidence !== null ? number_format((float) $field->confidence, 2).'%' : '-' }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </article>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-2">
        <article class="card-surface p-5">
            <h3 class="mb-3 font-semibold text-slate-800 dark:text-slate-100">Texto OCR extraido</h3>
            <textarea readonly class="input-control min-h-72 font-mono text-xs">{{ $document->latestExtraction?->raw_text }}</textarea>
        </article>

        <article class="card-surface p-5">
            <h3 class="mb-3 font-semibold text-slate-800 dark:text-slate-100">Historico de processamento</h3>
            <div class="space-y-3">
                @forelse($document->processingJobs as $job)
                    <div class="rounded-xl border border-slate-200 p-3 text-sm dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <p class="font-medium text-slate-700 dark:text-slate-200">{{ $job->job_uuid }}</p>
                            <x-status-badge :status="$job->status" />
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Etapa: {{ $job->stage->value ?? $job->stage }} | Tentativas: {{ $job->attempts }}/{{ $job->max_attempts }}</p>
                        @if ($job->logs->isNotEmpty())
                            <ul class="mt-2 space-y-1 border-t border-slate-100 pt-2 text-xs text-slate-500 dark:border-slate-800">
                                @foreach ($job->logs->take(4) as $log)
                                    <li>{{ $log->logged_at?->format('H:i:s') }} - [{{ strtoupper($log->level) }}] {{ $log->message }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @empty
                    <x-empty-state title="Sem historico" description="Nenhum job registrado para este documento." />
                @endforelse
            </div>
        </article>
    </section>

@endsection
