<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Forrst\Exceptions;

use RuntimeException;

/**
 * Exception thrown when an extension has invalid event handler configuration.
 *
 * Indicates the extension's getSubscribedEvents() returned an invalid
 * configuration that cannot be registered with the event subscriber.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidExtensionConfigurationException extends RuntimeException
{
    /**
     * Create exception for an invalid priority value.
     *
     * @param string $extensionUrn Extension identifier
     * @param string $eventClass   Event class name
     * @param mixed  $priority     The invalid priority value
     */
    public static function invalidPriority(string $extensionUrn, string $eventClass, mixed $priority): self
    {
        return new self(sprintf(
            'Extension "%s" has invalid priority for event "%s": expected integer, got %s',
            $extensionUrn,
            $eventClass,
            get_debug_type($priority),
        ));
    }

    /**
     * Create exception for an invalid method name.
     *
     * @param string $extensionUrn Extension identifier
     * @param string $eventClass   Event class name
     * @param mixed  $method       The invalid method value
     */
    public static function invalidMethod(string $extensionUrn, string $eventClass, mixed $method): self
    {
        return new self(sprintf(
            'Extension "%s" has invalid method for event "%s": expected string, got %s',
            $extensionUrn,
            $eventClass,
            get_debug_type($method),
        ));
    }

    /**
     * Create exception for a method that doesn't exist on the extension.
     *
     * @param string $extensionUrn   Extension identifier
     * @param string $eventClass     Event class name
     * @param string $method         Method name that doesn't exist
     * @param string $extensionClass Extension class name
     */
    public static function methodNotFound(
        string $extensionUrn,
        string $eventClass,
        string $method,
        string $extensionClass,
    ): self {
        return new self(sprintf(
            'Extension "%s" (%s) references non-existent method "%s" for event "%s"',
            $extensionUrn,
            $extensionClass,
            $method,
            $eventClass,
        ));
    }

    /**
     * Create exception for a method that is not callable(e.g., private).
     *
     * @param string $extensionUrn   Extension identifier
     * @param string $eventClass     Event class name
     * @param string $method         Method name that is not callable
     * @param string $extensionClass Extension class name
     */
    public static function methodNotCallable(
        string $extensionUrn,
        string $eventClass,
        string $method,
        string $extensionClass,
    ): self {
        return new self(sprintf(
            'Extension "%s" (%s) method "%s" for event "%s" is not callable (must be public)',
            $extensionUrn,
            $extensionClass,
            $method,
            $eventClass,
        ));
    }
}
