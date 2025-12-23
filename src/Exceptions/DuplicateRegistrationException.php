<?php

declare(strict_types=1);

namespace Cline\Forrst\Exceptions;

use function sprintf;

/**
 * Exception thrown when attempting to register a duplicate item.
 */
final class DuplicateRegistrationException extends ValidationException
{
    public static function forItem(string $type, string $name): self
    {
        return new self(sprintf('%s "%s" is already registered', $type, $name));
    }
}
