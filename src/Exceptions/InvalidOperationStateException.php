<?php

declare(strict_types=1);

namespace Cline\Forrst\Exceptions;

use function sprintf;

/**
 * Exception thrown when an operation is in an invalid state for the requested action.
 */
final class InvalidOperationStateException extends ValidationException
{
    public static function forState(string $currentState, string $requiredState): self
    {
        return new self(sprintf('Operation is in state "%s", but "%s" is required', $currentState, $requiredState));
    }
}
