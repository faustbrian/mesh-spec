<?php

declare(strict_types=1);

namespace Cline\Forrst\Exceptions;

use function sprintf;

/**
 * Exception thrown when a configuration value is invalid.
 */
final class InvalidConfigurationException extends ConfigurationException
{
    public static function forKey(string $key, string $reason): self
    {
        return new self(sprintf('Invalid configuration for %s: %s', $key, $reason));
    }
}
