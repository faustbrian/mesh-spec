<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Forrst\Validation;

use InvalidArgumentException;
use JsonException;

use function json_encode;
use function preg_match;
use function strlen;

use const JSON_THROW_ON_ERROR;

/**
 * Validates URN formats and array structures for Forrst extensions.
 */
final class UrnValidator
{
    /**
     * Validate Forrst extension URN format.
     *
     * @param string $urn       URN to validate
     * @param string $fieldName Field name for error messages
     *
     * @throws InvalidArgumentException If URN is invalid
     */
    public static function validateExtensionUrn(string $urn, string $fieldName = 'urn'): void
    {
        if ($urn === '') {
            throw new InvalidArgumentException("Extension {$fieldName} cannot be empty");
        }

        // Forrst extension URNs must follow: urn:forrst:ext:name
        if (!preg_match('/^urn:forrst:ext:[a-z0-9][a-z0-9_-]*$/i', $urn)) {
            throw new InvalidArgumentException(
                "Extension {$fieldName} must follow format 'urn:forrst:ext:name', got: {$urn}"
            );
        }

        // Validate URN length (reasonable limit)
        if (strlen($urn) > 255) {
            throw new InvalidArgumentException(
                "Extension {$fieldName} exceeds maximum length of 255 characters"
            );
        }
    }

    /**
     * Validate array structure, depth, and size.
     *
     * @param null|array<string, mixed> $array    Array to validate
     * @param string                    $fieldName Field name for error messages
     * @param int                       $maxDepth  Maximum nesting depth allowed
     *
     * @throws InvalidArgumentException If array is invalid
     */
    public static function validateArray(?array $array, string $fieldName, int $maxDepth = 5): void
    {
        if ($array === null) {
            return;
        }

        // Validate array is not empty when provided
        if ($array === []) {
            throw new InvalidArgumentException(
                "Extension {$fieldName} cannot be an empty array. Use null instead."
            );
        }

        // Validate depth to prevent DoS
        $checkDepth = function (array $arr, int $currentDepth) use (&$checkDepth, $maxDepth, $fieldName): void {
            if ($currentDepth > $maxDepth) {
                throw new InvalidArgumentException(
                    "Extension {$fieldName} exceeds maximum nesting depth of {$maxDepth}"
                );
            }

            foreach ($arr as $value) {
                if (\is_array($value)) {
                    $checkDepth($value, $currentDepth + 1);
                }
            }
        };

        $checkDepth($array, 1);

        // Validate total size
        try {
            $serialized = json_encode($array, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidArgumentException(
                "Extension {$fieldName} contains invalid data that cannot be JSON serialized: {$e->getMessage()}"
            );
        }

        if (strlen($serialized) > 65536) { // 64KB limit
            throw new InvalidArgumentException(
                "Extension {$fieldName} exceeds maximum size of 64KB when serialized"
            );
        }
    }
}
