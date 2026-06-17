<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Middleware\AuthModeMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AuthModeMiddlewareTest extends TestCase
{
    private AuthModeMiddleware $middleware;

    protected function setUp(): void
    {
        $this->middleware = new AuthModeMiddleware();
    }

    public function testPassesThroughWhenAuthModeIsLocal(): void
    {
        $original = getenv('AUTH_MODE');
        putenv('AUTH_MODE=local');

        try {
            $uri = $this->createMock(UriInterface::class);
            $uri->method('getPath')->willReturn('/v1/auth/login');

            $request = $this->createMock(ServerRequestInterface::class);
            $request->method('getUri')->willReturn($uri);

            $expected = $this->createMock(ResponseInterface::class);
            $handler = $this->createMock(RequestHandlerInterface::class);
            $handler->expects($this->once())->method('handle')->willReturn($expected);

            $this->assertSame($expected, $this->middleware->process($request, $handler));
        } finally {
            if ($original === false) {
                putenv('AUTH_MODE');
            } else {
                putenv("AUTH_MODE=$original");
            }
        }
    }

    public function testReturns404WhenAuthModeIsRemoteAndPathIsAuth(): void
    {
        $original = getenv('AUTH_MODE');
        putenv('AUTH_MODE=remote');

        try {
            $uri = $this->createMock(UriInterface::class);
            $uri->method('getPath')->willReturn('/v1/auth/login');

            $request = $this->createMock(ServerRequestInterface::class);
            $request->method('getUri')->willReturn($uri);

            $handler = $this->createMock(RequestHandlerInterface::class);
            $handler->expects($this->never())->method('handle');

            $response = $this->middleware->process($request, $handler);
            $this->assertSame(404, $response->getStatusCode());
        } finally {
            if ($original === false) {
                putenv('AUTH_MODE');
            } else {
                putenv("AUTH_MODE=$original");
            }
        }
    }

    public function testPassesThroughWhenAuthModeIsRemoteAndPathIsNotAuth(): void
    {
        $original = getenv('AUTH_MODE');
        putenv('AUTH_MODE=remote');

        try {
            $uri = $this->createMock(UriInterface::class);
            $uri->method('getPath')->willReturn('/v1/user');

            $request = $this->createMock(ServerRequestInterface::class);
            $request->method('getUri')->willReturn($uri);

            $expected = $this->createMock(ResponseInterface::class);
            $handler = $this->createMock(RequestHandlerInterface::class);
            $handler->expects($this->once())->method('handle')->willReturn($expected);

            $this->assertSame($expected, $this->middleware->process($request, $handler));
        } finally {
            if ($original === false) {
                putenv('AUTH_MODE');
            } else {
                putenv("AUTH_MODE=$original");
            }
        }
    }
}
