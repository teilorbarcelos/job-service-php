<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\JobCancelledException;
use PHPUnit\Framework\TestCase;

class JobCancelledExceptionTest extends TestCase
{
    public function testIsRuntimeException(): void
    {
        $exception = new JobCancelledException('test');
        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertSame('test', $exception->getMessage());
    }
}
