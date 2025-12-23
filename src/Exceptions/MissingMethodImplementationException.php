<?php

declare(strict_types=1);

namespace Cline\Forrst\Exceptions;

use LogicException;

use function sprintf;

/**
 * Exception thrown when a required method has not been implemented.
 */
final class MissingMethodImplementationException extends LogicException implements ForrstException
{
    public static function forMethod(string $class, string $method): self
    {
        return new self(sprintf('Class %s must implement %s()', $class, $method));
    }
}
