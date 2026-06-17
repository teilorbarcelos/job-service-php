<?php

declare(strict_types=1);

namespace App\Core;

final class JobSignal
{
    private bool $aborted = false;

    public function abort(): void
    {
        $this->aborted = true;
    }

    public function aborted(): bool
    {
        return $this->aborted;
    }

    public function throwIfAborted(): void
    {
        if ($this->aborted) {
            throw new JobCancelledException('Job was cancelled');
        }
    }
}

class JobCancelledException extends \RuntimeException {}
