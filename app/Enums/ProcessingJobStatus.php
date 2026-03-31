<?php

namespace App\Enums;

enum ProcessingJobStatus: string
{
    case Queued = 'queued';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Retrying = 'retrying';
    case Timeout = 'timeout';
}
