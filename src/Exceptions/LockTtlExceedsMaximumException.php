<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Forrst\Exceptions;

use RuntimeException;

final class LockTtlExceedsMaximumException extends RuntimeException implements ForrstException
{
    public static function create(int $requested, int $maximum): self
    {
        return new self(
            "Lock TTL of {$requested} seconds exceeds maximum allowed {$maximum} seconds",
        );
    }
}
