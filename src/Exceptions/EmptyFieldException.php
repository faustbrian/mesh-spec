<?php

declare(strict_types=1);

namespace Cline\Forrst\Exceptions;

use function sprintf;

/**
 * Exception thrown when a required field is empty.
 */
final class EmptyFieldException extends ValidationException
{
    public static function forField(string $field): self
    {
        return new self(sprintf('%s cannot be empty', $field));
    }
}
