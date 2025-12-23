<?php

declare(strict_types=1);

namespace Cline\Forrst\Exceptions;

use function sprintf;

/**
 * Exception thrown when a URL uses an unsupported protocol.
 */
final class InvalidProtocolException extends ValidationException
{
    public static function forUrl(string $field): self
    {
        return new self(sprintf('%s must use http or https protocol', $field));
    }
}
