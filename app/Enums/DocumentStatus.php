<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case Uploaded = 'uploaded';
    case Queued = 'queued';
    case Processing = 'processing';
    case NeedsReview = 'needs_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Uploaded => 'Enviado',
            self::Queued => 'Na fila',
            self::Processing => 'Processando',
            self::NeedsReview => 'Pendente (legado)',
            self::Approved => 'Aprovado',
            self::Rejected => 'Reprovado',
            self::Failed => 'Falhou',
        };
    }
}
