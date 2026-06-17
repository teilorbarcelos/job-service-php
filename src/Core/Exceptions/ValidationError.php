<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

use App\Core\Exceptions\AppError;

class ValidationError extends AppError
{
    public function __construct(string $message = 'Validation error', ?array $details = null)
    {
        parent::__construct($message, 0, null, $details);
    }
}
