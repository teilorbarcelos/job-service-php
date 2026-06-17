<?php

declare(strict_types=1);

namespace Tests\Core;

use PHPUnit\Framework\TestCase;

class ExceptionsTest extends TestCase
{
    public function testAppErrorConstructor(): void
    {
        $error = new \App\Core\Exceptions\AppError('test message', 42, null, ['key' => 'value']);
        $this->assertSame('test message', $error->getMessage());
        $this->assertSame(42, $error->getCode());
        $this->assertSame(['key' => 'value'], $error->details);
    }

    public function testConfigurationError(): void
    {
        $error = new \App\Core\Exceptions\ConfigurationError('config error', ['field' => 'host']);
        $this->assertInstanceOf(\App\Core\Exceptions\AppError::class, $error);
        $this->assertSame('config error', $error->getMessage());
        $this->assertSame(['field' => 'host'], $error->details);
    }

    public function testConfigurationErrorDefaultMessage(): void
    {
        $error = new \App\Core\Exceptions\ConfigurationError();
        $this->assertSame('Configuration error', $error->getMessage());
    }

    public function testConnectionError(): void
    {
        $error = new \App\Core\Exceptions\ConnectionError('conn error', ['host' => 'localhost']);
        $this->assertInstanceOf(\App\Core\Exceptions\AppError::class, $error);
        $this->assertSame('conn error', $error->getMessage());
        $this->assertSame(['host' => 'localhost'], $error->details);
    }

    public function testConnectionErrorDefaultMessage(): void
    {
        $error = new \App\Core\Exceptions\ConnectionError();
        $this->assertSame('Connection error', $error->getMessage());
    }

    public function testValidationError(): void
    {
        $error = new \App\Core\Exceptions\ValidationError('validation error', ['field' => 'email']);
        $this->assertInstanceOf(\App\Core\Exceptions\AppError::class, $error);
        $this->assertSame('validation error', $error->getMessage());
        $this->assertSame(['field' => 'email'], $error->details);
    }

    public function testValidationErrorDefaultMessage(): void
    {
        $error = new \App\Core\Exceptions\ValidationError();
        $this->assertSame('Validation error', $error->getMessage());
    }

    public function testAppErrorThrows(): void
    {
        $this->expectException(\App\Core\Exceptions\AppError::class);
        throw new \App\Core\Exceptions\ConfigurationError('test');
    }
}
