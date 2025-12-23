<?php

declare(strict_types=1);

namespace Cline\Forrst\Exceptions;

use RuntimeException;

use function sprintf;

/**
 * Exception thrown when an extension is used but not configured.
 */
final class ExtensionNotConfiguredException extends RuntimeException implements ForrstException
{
    public static function forExtension(string $extension): self
    {
        return new self(sprintf('Extension "%s" is not configured', $extension));
    }
}
