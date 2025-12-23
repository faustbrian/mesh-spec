<?php

declare(strict_types=1);

namespace Cline\Forrst\Exceptions;

use function gettype;
use function sprintf;

/**
 * Exception thrown when a field has an invalid type.
 */
final class InvalidFieldTypeException extends ValidationException
{
    public static function forField(string $field, string $expectedType, mixed $actualValue): self
    {
        return new self(sprintf(
            '%s must be %s, got %s',
            $field,
            $expectedType,
            gettype($actualValue),
        ));
    }
}
