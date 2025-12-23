<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Forrst\Extensions\Async\Exceptions;

use RuntimeException;

/**
 * Exception thrown when an operation cannot be found.
 *
 * Indicates that the requested operation ID does not exist in the repository.
 * This could be due to:
 * - Invalid or mistyped operation ID
 * - Operation was deleted/expired
 * - Operation never existed
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class OperationNotFoundException extends RuntimeException
{
}
