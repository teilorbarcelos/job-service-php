<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\JobInfo;
use PHPUnit\Framework\TestCase;

class JobInfoTest extends TestCase
{
    public function testConstructor(): void
    {
        $info = new JobInfo('test-job', '*/5 * * * *', true, 'A test job');
        $this->assertSame('test-job', $info->name);
        $this->assertSame('*/5 * * * *', $info->schedule);
        $this->assertTrue($info->enabled);
        $this->assertSame('A test job', $info->description);
    }
}
