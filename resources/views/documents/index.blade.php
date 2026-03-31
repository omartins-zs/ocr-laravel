@extends('layouts.app')

@section('page-title', 'Historico')

@section('content')
    <section class="page-header">
        <div class="page-title-wrap">
            <h2 class="page-title">
                <x-heroicon-o-archive-box class="h-6 w-6 text-brand-600 dark:text-brand-300" />
                Historico de uploads
            </h2>
            <p class="page-subtitle">Upload, status de OCR e resultado final em uma tela objetiva.</p>
        </div>
        <a href="{{ route('upload') }}" class="btn-primary">
            <x-heroicon-o-cloud-arrow-up class="h-4 w-4" />
            Novo upload
        </a>
    </section>

    <section class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article class="stat-card">
            <p class="stat-label">Total</p>
            <p class="stat-value">{{ number_format($summary['total']) }}</p>
        </article>
        <article class="stat-card">
            <p class="stat-label">Em processamento</p>
            <p class="stat-value">{{ number_format($summary['processing']) }}</p>
        </article>
        <article class="stat-card">
            <p class="stat-label">Aprovados</p>
            <p class="stat-value">{{ number_format($summary['approved']) }}</p>
        </article>
        <article class="stat-card">
            <p class="stat-label">Falhas</p>
            <p class="stat-value text-rose-600 dark:text-rose-300">{{ number_format($summary['failed']) }}</p>
        </article>
    </section>

    <section class="filter-panel">
        <form method="GET" class="grid gap-3 md:grid-cols-3">
            <div class="md:col-span-2">
                <label class="label-control">Buscar</label>
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="input-control" placeholder="Nome, UUID ou hash">
            </div>
            <div>
                <label class="label-control">Status</label>
                <select name="status" class="select-control">
                    <option value="">Todos</option>
                    @foreach ($statusOptions as $status)
                        <option value="{{ $status->value }}" @selected(($filters['status'] ?? null) === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-3 flex justify-end gap-2">
                <a href="{{ route('history') }}" class="btn-secondary">Limpar</a>
                <button type="submit" class="btn-primary">Filtrar</button>
            </div>
        </form>
    </section>

    @php
        $hasBatches = isset($batchGroups) && $batchGroups->isNotEmpty();
        $hasSingles = isset($singleDocuments) && $singleDocuments->isNotEmpty();
    @endphp

    @if (! $hasBatches && ! $hasSingles)
        <x-empty-state title="Nenhum documento encontrado" description="Faca upload do primeiro arquivo para iniciar o pipeline OCR.">
            <div class="mt-4">
                <a href="{{ route('upload') }}" class="btn-primary">Enviar documento</a>
            </div>
        </x-empty-state>
    @else
        @if ($hasBatches)
            <section class="mb-6 space-y-3">
                @foreach ($batchGroups as $batch)
                    <details class="card-surface overflow-hidden">
                        <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-2 px-4 py-3 hover:bg-slate-50/80 dark:hover:bg-slate-800/50">
                            <div>
                                <p class="font-semibold text-slate-900 dark:text-slate-100">
                                    Lote {{ strtoupper(substr((string) $batch['batch_uuid'], 0, 8)) }}
                                </p>
                                <p class="text-xs text-slate-500">
                                    {{ $batch['files_count'] }} arquivo(s) neste lote |
                                    Enviado {{ $batch['created_at']?->format('d/m/Y H:i') ?? '-' }} |
                                    Atualizado {{ $batch['updated_at']?->format('d/m/Y H:i') ?? '-' }}
                                </p>
                            </div>
                            <div class="flex flex-wrap items-center justify-end gap-2">
                                @foreach ($batch['status_counts'] as $status => $count)
                                    <span class="inline-flex items-center gap-1 text-xs text-slate-500">
                                        <x-status-badge :status="$status" />
                                        <span>{{ $count }}</span>
                                    </span>
                                @endforeach
                            </div>
                        </summary>
                        <div class="overflow-x-auto border-t border-slate-200/70 p-2 dark:border-slate-700/70 sm:p-3">
                            <table class="table-enterprise min-w-full text-sm">
                                <thead>
                                    <tr>
                                        <th>Arquivo</th>
                                        <th>UUID</th>
                                        <th>Status</th>
                                        <th>Confianca</th>
                                        <th>Atualizado</th>
                                        <th class="text-right">Acoes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($batch['documents'] as $document)
                                        <tr>
                                            <td>
                                                <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $document->original_filename }}</p>
                                                <p class="mt-0.5 text-xs text-slate-500">{{ formatBytes((int) $document->file_size) }}</p>
                                            </td>
                                            <td class="font-mono text-xs text-slate-500">{{ $document->uuid }}</td>
                                            <td>
                                                <x-status-badge :status="$document->status" />
                                            </td>
                                            <td>
                                                @if ($document->overall_confidence)
                                                    <span class="badge-status badge-status-info">{{ number_format((float) $document->overall_confidence, 2) }}%</span>
                                                @else
                                                    <span class="text-slate-400">-</span>
                                                @endif
                                            </td>
                                            <td class="text-xs text-slate-500" title="{{ $document->updated_at?->toIso8601String() }}">
                                                {{ $document->updated_at?->format('d/m/Y H:i') ?? '-' }}
                                            </td>
                                            <td class="text-right">
                                                <div class="flex flex-wrap justify-end gap-2">
                                                    <a href="{{ route('documents.show', $document) }}" class="btn-secondary text-xs">Detalhes</a>
                                                    @if (($document->status instanceof \BackedEnum ? $document->status->value : (string) $document->status) === 'failed')
                                                        <form method="POST" action="{{ route('documents.reprocess', $document) }}" onsubmit="return confirm('Deseja reprocessar este documento?')">
                                                            @csrf
                                                            <button type="submit" class="btn-secondary text-xs">
                                                                <i class="fa-solid fa-repeat"></i>
                                                                Reprocessar
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </details>
                @endforeach
            </section>
        @endif

        @if ($hasSingles)
            <section class="card-surface overflow-hidden p-2 sm:p-3">
                <div class="mb-2 px-1 text-xs font-semibold tracking-wide text-slate-500 uppercase">
                    Uploads avulsos
                </div>
                <div class="overflow-x-auto">
                    <table class="table-enterprise min-w-full text-sm">
                        <thead>
                            <tr>
                                <th>Arquivo</th>
                                <th>UUID</th>
                                <th>Status</th>
                                <th>Confianca</th>
                                <th>Atualizado</th>
                                <th class="text-right">Acoes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($singleDocuments as $document)
                                <tr>
                                    <td>
                                        <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $document->original_filename }}</p>
                                        <p class="mt-0.5 text-xs text-slate-500">{{ formatBytes((int) $document->file_size) }}</p>
                                    </td>
                                    <td class="font-mono text-xs text-slate-500">{{ $document->uuid }}</td>
                                    <td>
                                        <x-status-badge :status="$document->status" />
                                    </td>
                                    <td>
                                        @if ($document->overall_confidence)
                                            <span class="badge-status badge-status-info">{{ number_format((float) $document->overall_confidence, 2) }}%</span>
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </td>
                                    <td class="text-xs text-slate-500" title="{{ $document->updated_at?->toIso8601String() }}">
                                        {{ $document->updated_at?->format('d/m/Y H:i') ?? '-' }}
                                    </td>
                                    <td class="text-right">
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <a href="{{ route('documents.show', $document) }}" class="btn-secondary text-xs">Detalhes</a>
                                            @if (($document->status instanceof \BackedEnum ? $document->status->value : (string) $document->status) === 'failed')
                                                <form method="POST" action="{{ route('documents.reprocess', $document) }}" onsubmit="return confirm('Deseja reprocessar este documento?')">
                                                    @csrf
                                                    <button type="submit" class="btn-secondary text-xs">
                                                        <i class="fa-solid fa-repeat"></i>
                                                        Reprocessar
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        <div class="mt-4">
            {{ $documents->links() }}
        </div>
    @endif
@endsection
