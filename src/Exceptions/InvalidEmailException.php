<?php

declare(strict_types=1);

namespace Cline\Forrst\Exceptions;

use function sprintf;

/**
 * Exception thrown when an email address is invalid.
 */
final class InvalidEmailException extends ValidationException
{
    public static function forField(string $field): self
    {
        return new self(sprintf('%s must be a valid email address', $field));
    }
}
