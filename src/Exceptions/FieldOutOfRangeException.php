<?php

declare(strict_types=1);

namespace Cline\Forrst\Exceptions;

use function sprintf;

/**
 * Exception thrown when a numeric field value is out of allowed range.
 */
final class FieldOutOfRangeException extends ValidationException
{
    public static function forField(string $field, int|float $min, int|float $max): self
    {
        return new self(sprintf('%s must be between %s and %s', $field, $min, $max));
    }
}
