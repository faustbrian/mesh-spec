<?php

declare(strict_types=1);

namespace Cline\Forrst\Exceptions;

use function sprintf;

/**
 * Exception thrown when a semantic version string is invalid.
 */
final class InvalidSemanticVersionException extends ValidationException
{
    public static function forVersion(string $version): self
    {
        return new self(sprintf('Version must follow semantic versioning (e.g., 1.0.0), got: %s', $version));
    }
}
