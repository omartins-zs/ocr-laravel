@extends('layouts.app')

@section('page-title', 'Logs de processamento')

@section('content')
    <section class="page-header">
        <div class="page-title-wrap">
        <h2 class="page-title">
            <x-heroicon-o-command-line class="h-6 w-6 text-brand-600 dark:text-brand-300" />
            Logs e falhas
        </h2>
        <p class="page-subtitle">Auditoria detalhada do pipeline OCR por job e documento.</p>
        </div>
        <a href="{{ route('queue-status.index') }}" class="btn-secondary">
            <x-heroicon-o-bolt class="h-4 w-4" />
            Ver fila
        </a>
    </section>

    <section class="filter-panel">
        <form method="GET" class="grid gap-3 md:grid-cols-4">
            <div>
                <label class="label-control flex items-center gap-1.5">
                    <x-heroicon-o-funnel class="h-3.5 w-3.5" />
                    Nivel
                </label>
                <select name="level" class="w-full">
                    <option value="">Todos</option>
                    @foreach ($levels as $level)
                        <option value="{{ $level }}" @selected(($filters['level'] ?? null) === $level)>{{ strtoupper($level) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label-control flex items-center gap-1.5">
                    <x-heroicon-o-adjustments-horizontal class="h-3.5 w-3.5" />
                    Etapa
                </label>
                <select name="stage" class="w-full">
                    <option value="">Todas</option>
                    @foreach ($stages as $stage)
                        <option value="{{ $stage }}" @selected(($filters['stage'] ?? null) === $stage)>{{ $stage }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="label-control flex items-center gap-1.5">
                    <x-heroicon-o-magnifying-glass class="h-3.5 w-3.5" />
                    Buscar mensagem
                </label>
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="input-control">
            </div>
            <div class="md:col-span-4 flex justify-end gap-2">
                <a href="{{ route('processing-logs.index') }}" class="btn-secondary">
                    <x-heroicon-o-x-mark class="h-4 w-4" />
                    Limpar
                </a>
                <button type="submit" class="btn-primary">
                    <x-heroicon-o-funnel class="h-4 w-4" />
                    Filtrar
                </button>
            </div>
        </form>
    </section>

    @if ($logs->isEmpty())
        <x-empty-state title="Nenhum log encontrado" description="Nenhum evento para os filtros selecionados." />
    @else
        <section class="card-surface p-4">
            <div class="overflow-x-auto">
                <table class="table-enterprise min-w-full text-sm">
                    <thead>
                    <tr>
                        <th>Data</th>
                        <th>Nivel</th>
                        <th>Etapa</th>
                        <th>Mensagem</th>
                        <th>Documento</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($logs as $log)
                        <tr>
                            <td class="text-xs text-slate-500">{{ $log->logged_at?->format('d/m/Y H:i:s') }}</td>
                            <td>
                                <span class="badge-status {{ $log->level === 'error' ? 'badge-status-danger' : ($log->level === 'warning' ? 'badge-status-warning' : 'badge-status-info') }}">
                                    {{ strtoupper($log->level) }}
                                </span>
                            </td>
                            <td>{{ $log->stage ?? '-' }}</td>
                            <td class="text-slate-700 dark:text-slate-200">{{ $log->message }}</td>
                            <td>
                                @if ($log->document)
                                    <a href="{{ route('documents.show', $log->document) }}" class="text-brand-700 hover:underline dark:text-brand-300">
                                        {{ $log->document->original_filename }}
                                    </a>
                                @else
                                    <span class="text-slate-400">Removido</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $logs->links() }}</div>
        </section>
    @endif
@endsection
