<?php

declare(strict_types=1);

namespace App\Core;

use Psr\Log\LoggerInterface;

readonly class JobInfo
{
    public function __construct(
        public string $name,
        public string $schedule,
        public bool $enabled,
        public string $description,
    ) {}
}
