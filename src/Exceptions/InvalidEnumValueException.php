<?php

declare(strict_types=1);

namespace Cline\Forrst\Exceptions;

use function implode;
use function sprintf;

/**
 * Exception thrown when a value is not one of the allowed enum values.
 */
final class InvalidEnumValueException extends ValidationException
{
    /**
     * @param array<string> $allowedValues
     */
    public static function forField(string $field, array $allowedValues): self
    {
        return new self(sprintf(
            '%s must be one of: %s',
            $field,
            implode(', ', $allowedValues),
        ));
    }
}
