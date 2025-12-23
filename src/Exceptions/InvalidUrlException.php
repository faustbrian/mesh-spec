<?php

declare(strict_types=1);

namespace Cline\Forrst\Exceptions;

use function sprintf;

/**
 * Exception thrown when a URL is invalid or uses an unsupported protocol.
 */
final class InvalidUrlException extends ValidationException
{
    public static function invalidFormat(string $field): self
    {
        return new self(sprintf('%s must be a valid URL', $field));
    }
}
