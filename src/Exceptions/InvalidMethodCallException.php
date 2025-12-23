<?php

declare(strict_types=1);

namespace Cline\Forrst\Exceptions;

use BadMethodCallException;

use function sprintf;

/**
 * Exception thrown when a method is called in an invalid context.
 */
final class InvalidMethodCallException extends BadMethodCallException implements ForrstException
{
    public static function cannotCall(string $method, string $reason): self
    {
        return new self(sprintf('Cannot call %s: %s', $method, $reason));
    }
}
