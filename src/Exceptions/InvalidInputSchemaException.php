<?php

declare(strict_types=1);

namespace Cline\Forrst\Exceptions;

use function sprintf;

/**
 * Exception thrown when a JSON schema for function input is invalid.
 */
final class InvalidInputSchemaException extends ValidationException
{
    public static function forField(string $field, string $reason): self
    {
        return new self(sprintf('Invalid input schema for "%s": %s', $field, $reason));
    }
}
