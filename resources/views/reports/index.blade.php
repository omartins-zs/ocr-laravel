@extends('layouts.app')

@section('page-title', 'Relatorios')

@section('content')
    <section class="page-header">
        <div class="page-title-wrap">
            <h2 class="page-title">
                <x-heroicon-o-chart-bar class="h-6 w-6 text-brand-600 dark:text-brand-300" />
                Relatorios operacionais
            </h2>
            <p class="page-subtitle">Acompanhe volume, status e performance do OCR em uma visao executiva.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('queue-status.index') }}" class="btn-secondary">
                <x-heroicon-o-bolt class="h-4 w-4" />
                Fila
            </a>
            <a href="{{ route('processing-logs.index') }}" class="btn-secondary">
                <x-heroicon-o-command-line class="h-4 w-4" />
                Logs
            </a>
        </div>
    </section>

    <section class="mb-6 grid gap-4 md:grid-cols-3">
        <x-metric-card title="Dias no grafico" :value="$processedByDay->count()" accent="brand" icon="heroicon-o-calendar" />
        <x-metric-card title="Status monitorados" :value="$statusDistribution->count()" accent="info" icon="heroicon-o-chart-bar" />
        <x-metric-card title="Motores OCR" :value="$averageConfidenceByType->count()" accent="success" icon="heroicon-o-cpu-chip" />
    </section>

    <section class="grid gap-6 xl:grid-cols-2">
        <article class="card-surface p-5">
            <h3 class="mb-3 text-lg font-semibold text-slate-800 dark:text-slate-100">Processados por dia (30 dias)</h3>
            <canvas id="processedByDayChart" height="130"></canvas>
        </article>
        <article class="card-surface p-5">
            <h3 class="mb-3 text-lg font-semibold text-slate-800 dark:text-slate-100">Distribuicao por status</h3>
            <canvas id="statusDistributionChart" height="130"></canvas>
        </article>
    </section>

    <section class="card-surface mt-6 p-5">
        <h3 class="mb-3 text-lg font-semibold text-slate-800 dark:text-slate-100">Confianca media por motor OCR</h3>
        @if ($averageConfidenceByType->isEmpty())
            <x-empty-state title="Sem dados suficientes" description="Envie documentos para gerar este indicador por motor OCR." />
        @else
            <div class="overflow-x-auto">
                <table class="table-enterprise min-w-full text-sm">
                    <thead>
                    <tr>
                        <th>Motor</th>
                        <th>Confianca media</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($averageConfidenceByType as $row)
                        <tr>
                            <td>{{ $row->engine_name }}</td>
                            <td>
                                <span class="badge-status badge-status-info">{{ number_format((float) $row->avg_confidence, 2) }}%</span>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const perDayCtx = document.getElementById('processedByDayChart');
            const statusCtx = document.getElementById('statusDistributionChart');
            const processedByDay = @json($processedByDay);
            const statusDistribution = @json($statusDistribution);

            if (perDayCtx) {
                window.withChart((Chart) => {
                    new Chart(perDayCtx, {
                        type: 'line',
                        data: {
                            labels: processedByDay.map(item => item.day),
                            datasets: [{
                                data: processedByDay.map(item => item.total),
                                borderColor: '#2563eb',
                                backgroundColor: 'rgba(37,99,235,0.15)',
                                fill: true,
                                tension: 0.35,
                            }]
                        },
                        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
                    });
                });
            }

            if (statusCtx) {
                window.withChart((Chart) => {
                    new Chart(statusCtx, {
                        type: 'doughnut',
                        data: {
                            labels: statusDistribution.map(item => item.status),
                            datasets: [{
                                data: statusDistribution.map(item => item.total),
                                backgroundColor: ['#2563eb', '#10b981', '#f59e0b', '#ef4444', '#0ea5e9', '#64748b'],
                            }]
                        },
                        options: { plugins: { legend: { position: 'bottom' } } }
                    });
                });
            }
        });
    </script>
@endsection
