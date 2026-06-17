<?php

declare(strict_types=1);

namespace App\Core;

readonly class JobResult
{
    public function __construct(
        public string $job,
        public JobStatus $status,
        public int $durationMs,
        public ?string $error = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'job' => $this->job,
            'status' => $this->status->value,
            'duration_ms' => $this->durationMs,
            'error' => $this->error,
        ];
    }
}
