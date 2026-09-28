<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a ticket workflow rule is violated (server-side enforcement).
 */
class WorkflowException extends RuntimeException
{
    public static function message(string $message): self
    {
        return new self($message);
    }
}
