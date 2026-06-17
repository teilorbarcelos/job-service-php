<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

class ConnectionError extends AppError
{
    /** @param array<string, mixed>|null $details */
    public function __construct(string $message = 'Connection error', ?array $details = null)
    {
        parent::__construct($message, 0, null, $details);
    }
}
