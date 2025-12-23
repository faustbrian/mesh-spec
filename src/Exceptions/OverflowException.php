<?php

declare(strict_types=1);

namespace Cline\Forrst\Exceptions;

use function sprintf;

/**
 * Exception thrown when an arithmetic overflow occurs.
 */
final class OverflowException extends ValidationException
{
    public static function forOperation(string $operation): self
    {
        return new self(sprintf('Integer overflow in %s', $operation));
    }
}
