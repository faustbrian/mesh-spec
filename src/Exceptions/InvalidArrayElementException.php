<?php

declare(strict_types=1);

namespace Cline\Forrst\Exceptions;

use function sprintf;

/**
 * Exception thrown when an array element has an invalid type or value.
 */
final class InvalidArrayElementException extends ValidationException
{
    public static function forField(string $field, string $expectedType): self
    {
        return new self(sprintf('All %s must be %s', $field, $expectedType));
    }
}
