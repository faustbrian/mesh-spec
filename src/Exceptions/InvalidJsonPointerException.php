<?php

declare(strict_types=1);

namespace Cline\Forrst\Exceptions;

use function sprintf;

/**
 * Exception thrown when a JSON pointer format is invalid.
 */
final class InvalidJsonPointerException extends ValidationException
{
    public static function forPointer(string $pointer): self
    {
        return new self(sprintf('Invalid JSON pointer format: %s', $pointer));
    }
}
