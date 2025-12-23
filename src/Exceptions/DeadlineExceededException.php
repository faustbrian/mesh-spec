<?php

declare(strict_types=1);

namespace Cline\Forrst\Exceptions;

use function sprintf;

/**
 * Exception thrown when a deadline exceeds the maximum allowed value.
 */
final class DeadlineExceededException extends ValidationException
{
    public static function exceedsMaximum(string $maximum): self
    {
        return new self(sprintf('Deadline cannot exceed %s from now', $maximum));
    }
}
