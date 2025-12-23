<?php

declare(strict_types=1);

namespace Cline\Forrst\Exceptions;

use function sprintf;

/**
 * Exception thrown when a JSON schema for function result is invalid.
 */
final class InvalidResultSchemaException extends ValidationException
{
    public static function forSchema(string $reason): self
    {
        return new self(sprintf('Invalid result schema: %s', $reason));
    }
}
