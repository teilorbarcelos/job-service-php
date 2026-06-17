<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\JobContext;
use App\Core\JobSignal;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class JobContextTest extends TestCase
{
    public function testConstructor(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $signal = new JobSignal();
        $context = new JobContext($logger, $signal);

        $this->assertSame($logger, $context->logger);
        $this->assertSame($signal, $context->signal);
    }

    public function testSignalIsAbortable(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $signal = new JobSignal();
        $context = new JobContext($logger, $signal);

        $this->assertFalse($context->signal->aborted());
        $context->signal->abort();
        $this->assertTrue($context->signal->aborted());
    }
}
