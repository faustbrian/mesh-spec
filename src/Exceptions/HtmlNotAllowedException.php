<?php

declare(strict_types=1);

namespace Cline\Forrst\Exceptions;

use function sprintf;

/**
 * Exception thrown when HTML content is found in a field that doesn't allow it.
 */
final class HtmlNotAllowedException extends ValidationException
{
    public static function forField(string $field): self
    {
        return new self(sprintf('%s cannot contain HTML tags', $field));
    }
}
