<?php

declare(strict_types=1);

namespace Cline\Forrst\Exceptions;

use function sprintf;

/**
 * Exception thrown when a field exceeds maximum allowed length.
 */
final class FieldExceedsMaxLengthException extends ValidationException
{
    public static function forField(string $field, int $maxLength): self
    {
        return new self(sprintf('%s cannot exceed %d characters', $field, $maxLength));
    }
}
