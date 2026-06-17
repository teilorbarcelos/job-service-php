<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

use App\Core\Exceptions\AppError;

class ConnectionError extends AppError
{
    public function __construct(string $message = 'Connection error', ?array $details = null)
    {
        parent::__construct($message, 0, null, $details);
    }
}
