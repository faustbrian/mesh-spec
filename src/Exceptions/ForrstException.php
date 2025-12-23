<?php

declare(strict_types=1);

namespace Cline\Forrst\Exceptions;

use Throwable;

/**
 * Marker interface for all Forrst package exceptions.
 *
 * Consumers can catch this interface to handle any exception
 * thrown by the Forrst package.
 */
interface ForrstException extends Throwable
{
    // Marker interface - no methods required
}
