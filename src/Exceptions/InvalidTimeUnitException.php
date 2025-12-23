<?php

declare(strict_types=1);

namespace Cline\Forrst\Exceptions;

use function sprintf;

/**
 * Exception thrown when an invalid time unit is used.
 */
final class InvalidTimeUnitException extends ValidationException
{
    public static function forUnit(string $unit): self
    {
        return new self(sprintf('Unknown time unit: %s', $unit));
    }
}
