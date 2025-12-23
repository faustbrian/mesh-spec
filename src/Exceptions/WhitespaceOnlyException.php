<?php

declare(strict_types=1);

namespace Cline\Forrst\Exceptions;

use function sprintf;

/**
 * Exception thrown when a field contains only whitespace.
 */
final class WhitespaceOnlyException extends ValidationException
{
    public static function forField(string $field): self
    {
        return new self(sprintf('%s cannot be empty or whitespace only', $field));
    }
}
