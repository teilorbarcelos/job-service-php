<?php

declare(strict_types=1);

namespace App\Shared\Utils;

use App\Core\JobSignal;

final class SignalManager
{
    public static function createTimeoutSignal(int $timeoutMs): JobSignal
    {
        $signal = new JobSignal();

        if (function_exists('pcntl_signal')) {
            $handler = function () use ($signal): void {
                $signal->abort();
            };
            pcntl_signal(SIGALRM, $handler);
            pcntl_alarm((int)ceil($timeoutMs / 1000));
        }

        return $signal;
    }

    public static function clearTimeout(): void
    {
        if (function_exists('pcntl_alarm')) {
            pcntl_alarm(0);
        }
    }
}
