<?php

declare(strict_types=1);

namespace Cline\Forrst\Exceptions;

use function sprintf;

/**
 * Exception thrown when a function name is invalid.
 */
final class InvalidFunctionNameException extends ValidationException
{
    public static function forName(string $name, string $reason): self
    {
        return new self(sprintf('Invalid function name "%s": %s', $name, $reason));
    }
}
