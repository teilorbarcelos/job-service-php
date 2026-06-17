<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\JobSignal;
use App\Core\JobCancelledException;
use PHPUnit\Framework\TestCase;

class JobSignalTest extends TestCase
{
    public function testInitiallyNotAborted(): void
    {
        $signal = new JobSignal();
        $this->assertFalse($signal->aborted());
    }

    public function testAbortSetsFlag(): void
    {
        $signal = new JobSignal();
        $signal->abort();
        $this->assertTrue($signal->aborted());
    }

    public function testThrowIfAbortedDoesNotThrowWhenNotAborted(): void
    {
        $signal = new JobSignal();
        $signal->throwIfAborted();
        $this->assertTrue(true);
    }

    public function testThrowIfAbortedThrowsWhenAborted(): void
    {
        $signal = new JobSignal();
        $signal->abort();
        $this->expectException(JobCancelledException::class);
        $this->expectExceptionMessage('Job was cancelled');
        $signal->throwIfAborted();
    }
}
