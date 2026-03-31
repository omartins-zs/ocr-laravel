@extends('layouts.app')

@section('page-title', 'Dashboard Operacional')

@section('content')
    @php
        $role = auth()->user()?->role;
        $roleValue = $role instanceof \App\Enums\UserRole ? $role->value : (string) $role;
        $canManageUsers = in_array($roleValue, [\App\Enums\UserRole::Admin->value, \App\Enums\UserRole::Manager->value], true);
    @endphp

    <section class="page-header">
        <div class="page-title-wrap">
            <h2 class="page-title">
                <x-heroicon-o-home class="h-6 w-6 text-brand-600 dark:text-brand-300" />
                Visao operacional
            </h2>
            <p class="page-subtitle">Resumo rapido da operacao OCR com atalhos para agir sem friccao.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('upload') }}" class="btn-primary">
                <x-heroicon-o-cloud-arrow-up class="h-4 w-4" />
                Novo upload
            </a>
            <a href="{{ route('history') }}" class="btn-secondary">
                <x-heroicon-o-archive-box class="h-4 w-4" />
                Historico
            </a>
            <a href="{{ route('queue-status.index') }}" class="btn-secondary">
                <x-heroicon-o-bolt class="h-4 w-4" />
                Fila
            </a>
            @if ($canManageUsers)
                <a href="{{ route('users.index') }}" class="btn-secondary">
                    <x-heroicon-o-users class="h-4 w-4" />
                    Usuarios
                </a>
            @endif
        </div>
    </section>

    <section class="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach ($summaryCards as $card)
            <x-metric-card
                :title="$card['label']"
                :value="$card['total']"
                accent="brand"
                :icon="match ($card['key']) {
                    'queued' => 'heroicon-o-clock',
                    'processing' => 'heroicon-o-cog-6-tooth',
                    'processed' => 'heroicon-o-check-badge',
                    'approved' => 'heroicon-o-check-circle',
                    'rejected' => 'heroicon-o-x-circle',
                    'failed' => 'heroicon-o-exclamation-triangle',
                    default => 'heroicon-o-document-text',
                }" />
        @endforeach
    </section>

    <section class="mb-6 grid gap-6 xl:grid-cols-[2fr_1fr]">
        <article class="card-surface p-5">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-100">Volume de documentos (14 dias)</h2>
                <span class="text-xs text-slate-500 dark:text-slate-400">Atualizacao em tempo real</span>
            </div>
            <canvas id="documentsTrendChart" height="120"></canvas>
        </article>

        <article class="card-surface space-y-4 p-5">
            <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-100">Saude operacional</h2>
            <x-metric-card title="Fila OCR" :value="$queueSize" hint="Jobs pendentes na fila OCR" accent="warning" icon="heroicon-o-bolt" />
            <x-metric-card title="Falhas hoje" :value="$failedToday" hint="Registros em failed_jobs" accent="danger" icon="heroicon-o-exclamation-triangle" />
            @php
                $ocrStatus = (string) ($ocrHealth['status'] ?? 'offline');
                $ocrStatusClasses = match ($ocrStatus) {
                    'online' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300',
                    'disabled' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300',
                    'checking' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-300 animate-pulse',
                    default => 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-300',
                };
            @endphp
            <div id="ocrHealthPanel" data-ocr-health-panel class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm dark:border-slate-700 dark:bg-slate-900">
                <div class="flex items-center justify-between gap-3">
                    <p class="font-semibold text-slate-700 dark:text-slate-200">Servico OCR externo</p>
                    <span id="ocrStatusBadge" data-ocr-health-badge class="rounded-full px-2 py-1 text-xs font-semibold {{ $ocrStatusClasses }}">
                        {{ strtoupper($ocrStatus === 'checking' ? 'verificando...' : $ocrStatus) }}
                    </span>
                </div>
                <p id="ocrBaseUrl" data-ocr-health-base-url class="mt-1 font-mono text-xs text-slate-500 dark:text-slate-400">{{ $ocrHealth['base_url'] ?? 'Consultando...' }}</p>
                <p id="ocrDetails" data-ocr-health-details class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                    Host: {{ $ocrHealth['host'] ?? '...' }}
                    @if (! is_null($ocrHealth['http_status'] ?? null))
                        | HTTP {{ $ocrHealth['http_status'] }}
                    @endif
                    @if (! is_null($ocrHealth['latency_ms'] ?? null))
                        | {{ $ocrHealth['latency_ms'] }}ms
                    @endif
                </p>
                <p id="ocrError" data-ocr-health-error class="mt-2 text-xs text-rose-600 dark:text-rose-300 {{ empty($ocrHealth['error']) ? 'hidden' : '' }}">{{ $ocrHealth['error'] ?? '' }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm dark:border-slate-700 dark:bg-slate-900">
                <p class="font-semibold text-slate-700 dark:text-slate-200">Confianca media OCR</p>
                <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">{{ number_format((float) ($confidence->avg_confidence ?? 0), 2) }}%</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Min {{ number_format((float) ($confidence->min_confidence ?? 0), 2) }}% |
                    Max {{ number_format((float) ($confidence->max_confidence ?? 0), 2) }}%
                </p>
            </div>
        </article>
    </section>

    <section class="card-surface p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-100">Ultimos documentos</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">Visao rapida para abrir, revisar ou reprocessar.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('history') }}" class="btn-secondary">
                    <x-heroicon-o-archive-box class="h-4 w-4" />
                    Ver historico
                </a>
            </div>
        </div>
        <div class="mt-4 overflow-x-auto">
            <table class="table-enterprise min-w-full text-sm">
                <thead>
                    <tr>
                        <th>Arquivo</th>
                        <th>Status</th>
                        <th>Confianca</th>
                        <th>Atualizado</th>
                        <th class="text-right">Acao</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentDocuments as $document)
                        <tr>
                            <td>
                                <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $document->original_filename }}</p>
                                <p class="text-xs font-mono text-slate-500">{{ $document->uuid }}</p>
                            </td>
                            <td><x-status-badge :status="$document->status" /></td>
                            <td>
                                @if ($document->overall_confidence)
                                    <span class="badge-status badge-status-info">{{ number_format((float) $document->overall_confidence, 2) }}%</span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="text-xs text-slate-500">{{ $document->updated_at?->diffForHumans() }}</td>
                            <td class="text-right">
                                <a href="{{ route('documents.show', $document) }}" class="btn-secondary text-xs">
                                    <x-heroicon-o-eye class="h-4 w-4" />
                                    Abrir
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-6 text-center text-sm text-slate-500">Sem documentos recentes.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const ctx = document.getElementById('documentsTrendChart');
            if (!ctx) {
                return;
            }

            const data = @json($documentsPerDay);
            window.withChart((Chart) => {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.map(item => item.day),
                        datasets: [{
                            label: 'Documentos',
                            data: data.map(item => item.total),
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37, 99, 235, 0.16)',
                            fill: true,
                            tension: 0.35,
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { display: false },
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { precision: 0 }
                            }
                        }
                    }
                });
            });
        });
    </script>
@endsection
