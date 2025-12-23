<?php

declare(strict_types=1);

namespace Cline\Forrst\Exceptions;

use function sprintf;

/**
 * Exception thrown when a field that must be positive has a negative value.
 */
final class NegativeValueException extends ValidationException
{
    public static function forField(string $field): self
    {
        return new self(sprintf('%s cannot be negative', $field));
    }
}
