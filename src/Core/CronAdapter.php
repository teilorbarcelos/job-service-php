<?php

declare(strict_types=1);

namespace App\Core;

interface CronAdapter
{
    public function isValid(string $expression): bool;
    public function getNextRunDate(string $expression, \DateTimeImmutable $from): ?\DateTimeImmutable;
}
