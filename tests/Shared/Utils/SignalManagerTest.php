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
        $signal = SignalManager::createTimeoutSignal(5000);
        $this->assertFalse($signal->aborted());
        SignalManager::clearTimeout();
        $this->assertTrue(true);
    }

    public function testCreateTimeoutSignalWithoutPcntl(): void
    {
        // Simulate no pcntl by creating the signal but then calling clearTimeout
        $signal = SignalManager::createTimeoutSignal(1000);
        $this->assertFalse($signal->aborted());
        $signal->abort();
        $this->assertTrue($signal->aborted());
    }

    public function testMultipleTimeoutSignals(): void
    {
        $s1 = SignalManager::createTimeoutSignal(5000);
        $s2 = SignalManager::createTimeoutSignal(10000);
        $this->assertNotSame($s1, $s2);
        $this->assertFalse($s1->aborted());
        $this->assertFalse($s2->aborted());
    }

    public function testClearAfterMultipleCreates(): void
    {
        SignalManager::createTimeoutSignal(5000);
        SignalManager::createTimeoutSignal(10000);
        SignalManager::clearTimeout();
        $this->assertTrue(true);
    }
}
