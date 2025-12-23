<?php

declare(strict_types=1);

namespace Cline\Forrst\Exceptions;

use function sprintf;

/**
 * Exception thrown when mutually exclusive fields are both set.
 */
final class MutuallyExclusiveFieldsException extends ValidationException
{
    public static function forFields(string $field1, string $field2): self
    {
        return new self(sprintf('Cannot have both %s and %s', $field1, $field2));
    }
}
