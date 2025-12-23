<?php

declare(strict_types=1);

namespace Cline\Forrst\Exceptions;

use RuntimeException;

use function sprintf;

/**
 * Exception thrown when a server fails to boot.
 */
final class ServerBootException extends RuntimeException implements ForrstException
{
    public static function forServer(string $serverName, string $reason): self
    {
        return new self(sprintf('Failed to boot server "%s": %s', $serverName, $reason));
    }
}
