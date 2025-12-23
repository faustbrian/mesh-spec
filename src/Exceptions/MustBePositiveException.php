<?php

declare(strict_types=1);

namespace Cline\Forrst\Exceptions;

use function sprintf;

/**
 * Exception thrown when a field must be positive (greater than zero).
 */
final class MustBePositiveException extends ValidationException
{
    public static function forField(string $field): self
    {
        return new self(sprintf('%s must be positive', $field));
    }
}
