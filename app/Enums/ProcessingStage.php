<?php

namespace App\Enums;

enum ProcessingStage: string
{
    case Uploaded = 'uploaded';
    case Validation = 'validation';
    case Queued = 'queued';
    case Detecting = 'detecting';
    case Ocr = 'ocr';
    case Parsing = 'parsing';
    case Persisting = 'persisting';
    case Completed = 'completed';
    case Failed = 'failed';
}
