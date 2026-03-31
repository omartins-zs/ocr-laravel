@extends('layouts.app')

@section('page-title', 'Fila e status')

@section('content')
    <section class="page-header">
        <div class="page-title-wrap">
            <h2 class="page-title">
                <x-heroicon-o-bolt class="h-6 w-6 text-brand-600 dark:text-brand-300" />
                Fila e status
            </h2>
            <p class="page-subtitle">Monitore fila OCR, falhas e andamento dos jobs em tempo real.</p>
        </div>
        <a href="{{ route('processing-logs.index') }}" class="btn-secondary">
            <x-heroicon-o-command-line class="h-4 w-4" />
            Abrir logs
        </a>
    </section>

    <section class="mb-6 grid gap-4 md:grid-cols-3">
        <x-metric-card title="Jobs na fila OCR" :value="$queueDepth" accent="warning" icon="heroicon-o-bolt" />
        <x-metric-card title="Jobs com falha" :value="$failedQueue" accent="danger" icon="heroicon-o-exclamation-triangle" />
        <x-metric-card title="Jobs finalizados" :value="(int)($statusTotals['completed'] ?? 0)" accent="success" icon="heroicon-o-check-circle" />
    </section>

    <section class="mb-6 card-surface p-5">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-100">Distribuicao por status</h2>
        </div>
        <canvas id="queueStatusChart" height="120"></canvas>
    </section>

    <section class="card-surface p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-100">Detalhes dos jobs</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">Para listar eventos detalhados de cada tentativa, abra os logs de processamento.</p>
            </div>
            <a href="{{ route('processing-logs.index') }}" class="btn-secondary">
                <x-heroicon-o-command-line class="h-4 w-4" />
                Abrir logs
            </a>
        </div>
        <div class="mt-4 overflow-x-auto">
            <table class="table-enterprise min-w-full text-sm">
                <thead>
                    <tr>
                        <th>Job</th>
                        <th>Documento</th>
                        <th>Status</th>
                        <th>Etapa</th>
                        <th>Tentativas</th>
                        <th>Inicio</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentJobs as $job)
                        <tr>
                            <td class="font-mono text-xs text-slate-600 dark:text-slate-300">{{ $job->job_uuid }}</td>
                            <td>
                                @if ($job->document)
                                    <a href="{{ route('documents.show', $job->document) }}" class="font-medium text-brand-700 hover:underline dark:text-brand-300">
                                        {{ $job->document->original_filename }}
                                    </a>
                                @else
                                    <span class="text-slate-400">Removido</span>
                                @endif
                            </td>
                            <td><x-status-badge :status="$job->status" /></td>
                            <td class="text-xs text-slate-600 dark:text-slate-300">{{ $job->stage->value ?? $job->stage ?? '-' }}</td>
                            <td class="text-xs text-slate-500">{{ $job->attempts }}/{{ $job->max_attempts }}</td>
                            <td class="text-xs text-slate-500">{{ $job->started_at?->diffForHumans() ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-6 text-center text-sm text-slate-500">Nenhum job recente.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const ctx = document.getElementById('queueStatusChart');
            if (!ctx) {
                return;
            }

            const data = @json($chart);
            window.withChart((Chart) => {
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.map(item => item.status),
                        datasets: [{
                            label: 'Jobs',
                            data: data.map(item => item.total),
                            backgroundColor: ['#2563eb', '#f59e0b', '#10b981', '#ef4444', '#0ea5e9', '#64748b'],
                            borderRadius: 8,
                        }]
                    },
                    options: {
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                    }
                });
            });
        });
    </script>
@endsection
