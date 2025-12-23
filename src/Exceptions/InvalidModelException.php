<?php

declare(strict_types=1);

namespace Cline\Forrst\Exceptions;

use RuntimeException;

use function sprintf;

/**
 * Exception thrown when a resolved model is not of the expected type.
 */
final class InvalidModelException extends RuntimeException implements ForrstException
{
    public static function notInstanceOf(string $expectedClass): self
    {
        return new self(sprintf('Resolved model is not an instance of %s', $expectedClass));
    }
}
