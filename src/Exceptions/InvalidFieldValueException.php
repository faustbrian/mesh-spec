<?php

declare(strict_types=1);

namespace Cline\Forrst\Exceptions;

use function sprintf;

/**
 * Exception thrown when a field has an invalid value.
 */
final class InvalidFieldValueException extends ValidationException
{
    public static function forField(string $field, string $reason): self
    {
        return new self(sprintf('%s is invalid: %s', $field, $reason));
    }
}
