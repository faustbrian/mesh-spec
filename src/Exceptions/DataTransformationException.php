<?php

declare(strict_types=1);

namespace Cline\Forrst\Exceptions;

use RuntimeException;

use function sprintf;

/**
 * Exception thrown when data transformation fails.
 */
final class DataTransformationException extends RuntimeException implements ForrstException
{
    public static function cannotTransform(string $from, string $to, string $reason, ?\Throwable $previous = null): self
    {
        return new self(sprintf('Cannot transform %s to %s: %s', $from, $to, $reason), 0, $previous);
    }
}
