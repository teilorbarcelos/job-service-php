<?php

declare(strict_types=1);

namespace App\Shared\Utils;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\JsonFormatter;

final class LoggerFactory
{
    public static function create(string $level = LogLevel::INFO): LoggerInterface
    {
        $logger = new Logger('job');
        $handler = new StreamHandler('php://stdout', $level);
        $handler->setFormatter(new JsonFormatter());
        $logger->pushHandler($handler);
        return $logger;
    }
}
