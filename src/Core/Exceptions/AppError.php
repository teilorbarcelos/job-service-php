<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

abstract class AppError extends \RuntimeException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        public readonly ?array $details = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
