<?php

declare(strict_types=1);

namespace Cline\Forrst\Exceptions;

use function sprintf;

/**
 * Exception thrown when a required configuration is missing.
 */
final class MissingConfigurationException extends ConfigurationException
{
    public static function forKey(string $key): self
    {
        return new self(sprintf('Missing required configuration: %s', $key));
    }
}
