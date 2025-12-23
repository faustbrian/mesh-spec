<?php

declare(strict_types=1);

namespace Cline\Forrst\Exceptions;

use function sprintf;

/**
 * Exception thrown when an array that must have items is empty.
 */
final class EmptyArrayException extends ValidationException
{
    public static function forField(string $field): self
    {
        return new self(sprintf('%s cannot be empty', $field));
    }
}
