<?php

declare(strict_types=1);

namespace Cline\Forrst\Exceptions;

use function sprintf;

/**
 * Exception thrown when an unknown JSON schema type is encountered.
 */
final class UnknownSchemaTypeException extends ValidationException
{
    public static function forType(string $type): self
    {
        return new self(sprintf('Unknown schema type: %s', $type));
    }
}
