<?php

declare(strict_types=1);

namespace App\Infrastructure\Health;

readonly class HealthCheckResult
{
    public function __construct(
        public string $status,
        public ?int $latencyMs = null,
        public ?string $error = null,
    ) {}
}
