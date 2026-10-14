<?php

namespace App\Exceptions;

use RuntimeException;

class OtpException extends RuntimeException
{
    public function __construct(string $message, public readonly int $retryAfter = 0)
    {
        parent::__construct($message);
    }
}
