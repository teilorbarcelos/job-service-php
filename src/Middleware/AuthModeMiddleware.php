<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class AuthModeMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (getenv('AUTH_MODE') === 'remote') {
            $path = $request->getUri()->getPath();
            if (str_starts_with($path, '/v1/auth')) {
                $response = new Response();
                return $response->withStatus(404);
            }
        }

        return $handler->handle($request);
    }
}
