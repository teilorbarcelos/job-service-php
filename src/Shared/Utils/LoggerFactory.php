<?php

declare(strict_types=1);

namespace App\Shared\Utils;

use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\JsonFormatter;
use Monolog\Level;

final class LoggerFactory
{
    public static function create(string $level = 'info'): LoggerInterface
    {
        $monologLevel = Level::Debug;
        foreach (Level::cases() as $case) {
            if ($case->getName() === strtoupper($level)) {
                $monologLevel = $case;
                break;
            }
        }

        $logger = new Logger('job');
        $handler = new StreamHandler('php://stdout', $monologLevel);
        $handler->setFormatter(new JsonFormatter());
        $logger->pushHandler($handler);
        return $logger;
    }
}
