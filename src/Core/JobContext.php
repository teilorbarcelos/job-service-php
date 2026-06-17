<?php

declare(strict_types=1);

namespace App\Core;

use Psr\Log\LoggerInterface;

readonly class JobContext
{
    public function __construct(
        public LoggerInterface $logger,
        public JobSignal $signal,
    ) {}
}
