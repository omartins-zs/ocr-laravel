@props(['status'])

@php
    $statusValue = $status instanceof \BackedEnum ? $status->value : (string) $status;

    $map = [
        'uploaded' => ['label' => 'Enviado', 'class' => 'badge-status-info'],
        'queued' => ['label' => 'Fila', 'class' => 'badge-status-info'],
        'processing' => ['label' => 'Processando', 'class' => 'badge-status-warning'],
        'needs_review' => ['label' => 'Pendente', 'class' => 'badge-status-warning'],
        'approved' => ['label' => 'Aprovado', 'class' => 'badge-status-success'],
        'rejected' => ['label' => 'Reprovado', 'class' => 'badge-status-danger'],
        'failed' => ['label' => 'Falha', 'class' => 'badge-status-danger'],
        'completed' => ['label' => 'Concluido', 'class' => 'badge-status-success'],
    ];

    $meta = $map[$statusValue] ?? ['label' => ucfirst(str_replace('_', ' ', $statusValue)), 'class' => 'badge-status-info'];
@endphp

<span {{ $attributes->class(['badge-status', $meta['class']]) }}>
    {{ $meta['label'] }}
</span>
