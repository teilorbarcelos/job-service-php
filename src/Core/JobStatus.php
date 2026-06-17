<?php

declare(strict_types=1);

namespace App\Core;

enum JobStatus: string
{
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case TIMEOUT = 'timeout';
}
