<?php

declare(strict_types=1);

namespace Cline\Forrst\Exceptions;

use function sprintf;

/**
 * Exception thrown when a component reference cannot be resolved.
 */
final class ComponentReferenceNotFoundException extends ValidationException
{
    public static function forRef(string $ref): self
    {
        return new self(sprintf("Component reference '%s' does not exist", $ref));
    }
}
