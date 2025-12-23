<?php

declare(strict_types=1);

namespace Cline\Forrst\Exceptions;

use RuntimeException;

/**
 * Base exception for configuration-related errors.
 *
 * Thrown when server configuration, extension configuration, or other
 * system configuration is invalid or missing required values.
 */
abstract class ConfigurationException extends RuntimeException implements ForrstException
{
    // Abstract base - no factory methods
}
