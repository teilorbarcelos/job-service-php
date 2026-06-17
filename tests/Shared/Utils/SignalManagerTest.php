<?php

declare(strict_types=1);

namespace Tests\Shared\Utils;

use App\Shared\Utils\SignalManager;
use PHPUnit\Framework\TestCase;

class SignalManagerTest extends TestCase
{
    public function testCreateTimeoutSignalReturnsJobSignal(): void
    {
        $signal = SignalManager::createTimeoutSignal(5000);
        $this->assertInstanceOf(\App\Core\JobSignal::class, $signal);
        $this->assertFalse($signal->aborted());
    }

    public function testClearTimeoutDoesNotThrow(): void
    {
        SignalManager::createTimeoutSignal(5000);
        SignalManager::clearTimeout();
        $this->assertTrue(true);
    }
}
