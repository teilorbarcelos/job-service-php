<?php

declare(strict_types=1);

namespace App\Core;

final class DragonmantankCronAdapter implements CronAdapter
{
    public function isValid(string $expression): bool
    {
        return \Cron\CronExpression::isValidExpression($expression);
    }

    public function getNextRunDate(string $expression, \DateTimeImmutable $from): ?\DateTimeImmutable
    {
        try {
            $cron = new \Cron\CronExpression($expression);
            return \DateTimeImmutable::createFromMutable($cron->getNextRunDate($from));
        } catch (\Throwable) {
            return null;
        }
    }
}
