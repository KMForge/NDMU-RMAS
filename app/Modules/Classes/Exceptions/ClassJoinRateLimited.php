<?php

namespace App\Modules\Classes\Exceptions;

use RuntimeException;

class ClassJoinRateLimited extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $retryAfterSeconds,
    ) {
        parent::__construct($message);
    }
}
