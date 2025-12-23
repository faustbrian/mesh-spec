<?php

declare(strict_types=1);

namespace Cline\Forrst\Exceptions;

use InvalidArgumentException;

/**
 * Base exception for all data validation errors.
 *
 * Provides a common type for validation-related exceptions thrown during
 * DTO construction, configuration parsing, and input validation. Consumers
 * can catch this interface to handle any validation error.
 */
abstract class ValidationException extends InvalidArgumentException implements ForrstException
{
    // Abstract base - no factory methods
}
