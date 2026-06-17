<?php

declare(strict_types=1);

namespace App\Shared\Utils;

use Psr\Log\LoggerInterface;

final class ShutdownHandler
{
    private static bool $registered = false;

    public static function register(callable $cleanup, LoggerInterface $logger, int $timeoutMs = 30000): void
    {
        if (self::$registered) {
            return;
        }
        self::$registered = true;

        if (($_ENV['APP_ENV'] ?? '') === 'testing') {
            return;
        }

        // @codeCoverageIgnoreStart
        $handler = function (int $signal) use ($cleanup, $logger, $timeoutMs): void {
            $logger->info('Shutdown signal received', ['signal' => $signal]);

            $shutdownTimeout = microtime(true) + ($timeoutMs / 1000);

            try {
                $cleanup();
            } catch (\Throwable $e) {
                $logger->error('Error during shutdown cleanup', ['error' => $e->getMessage()]);
            }

            $remaining = $shutdownTimeout - microtime(true);
            if ($remaining > 0) {
                usleep((int)($remaining * 1_000_000));
            }

            exit(0);
        };

        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, $handler);
            pcntl_signal(SIGINT, $handler);
        }
        // @codeCoverageIgnoreEnd
    }
}
