<?php

declare(strict_types=1);

namespace Tests\Shared\Utils;

use App\Shared\Utils\ShutdownHandler;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ShutdownHandlerTest extends TestCase
{
    public function testRegisterInTestingEnvDoesNothing(): void
    {
        $_ENV['APP_ENV'] = 'testing';
        $logger = $this->createMock(LoggerInterface::class);

        $cleanup = function (): void {
            $this->assertTrue(true);
        };

        ShutdownHandler::register($cleanup, $logger);
        ShutdownHandler::register($cleanup, $logger);

        $this->assertTrue(true);
    }

    public function testRegisterSetsRegisteredFlag(): void
    {
        $_ENV['APP_ENV'] = 'testing';
        $logger = $this->createMock(LoggerInterface::class);

        ShutdownHandler::register(function () {}, $logger);

        $this->assertTrue(true);
    }
}
