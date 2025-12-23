<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Forrst\Discovery;

use Cline\Forrst\Exceptions\EmptyFieldException;
use Cline\Forrst\Exceptions\FieldExceedsMaxLengthException;
use Spatie\LaravelData\Data;

/**
 * Design-time link describing a relationship between functions.
 *
 * Enables clients to discover related operations and navigate between
 * functions. Links can specify runtime expressions that reference values
 * from the current result to build parameters for the linked function.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://docs.cline.sh/forrst/extensions/discovery#componentslinks
 */
final class LinkData extends Data
{
    /**
     * Create a new link definition.
     *
     * @param string                    $name        Unique identifier for this link within the links collection
     *                                               (e.g., "GetEventVenue", "ListEventAttendees"). Used for
     *                                               referencing the link from function definitions using $ref notation.
     * @param null|string               $summary     Brief one-line description of the relationship (e.g., "Retrieve
     *                                               the venue for this event"). Displayed in navigation interfaces
     *                                               and API explorers to help users understand related operations.
     * @param null|string               $description Detailed explanation of when and how to use this link, including
     *                                               any preconditions or contextual information. Supports Markdown
     *                                               for rich documentation formatting.
     * @param null|string               $function    Target function name to invoke (e.g., "venues.get",
     *                                               "attendees.list"). The function must be defined in the
     *                                               same discovery document. Omit if link is purely informational.
     * @param null|array<string, mixed> $params      Parameters to pass to the target function. Values can be runtime
     *                                               expressions referencing the current result (e.g., "$result.venue.id",
     *                                               "$result.id"). Keys are parameter names for the target function.
     * @param null|DiscoveryServerData  $server      Alternative server for the linked function if it's hosted at a
     *                                               different endpoint. Overrides the default server for cross-service
     *                                               navigation scenarios.
     */
    public function __construct(
        public readonly string $name,
        public readonly ?string $summary = null,
        public readonly ?string $description = null,
        public readonly ?string $function = null,
        public readonly ?array $params = null,
        public readonly ?DiscoveryServerData $server = null,
    ) {
        // Validate name
        $trimmedName = trim($this->name);
        if ($trimmedName === '') {
            throw EmptyFieldException::forField('name');
        }

        if (mb_strlen($trimmedName) > 100) {
            throw FieldExceedsMaxLengthException::forField('name', 100);
        }

        // Validate params structure if provided
        if ($this->params !== null) {
            $this->validateParams($this->params);
        }

        // Validate function name format if provided
        if ($this->function !== null) {
            if (!preg_match('/^[a-z][a-z0-9]*(?:\.[a-z][a-z0-9]*)*$/', $this->function)) {
                trigger_error(
                    "Warning: Function name '{$this->function}' should use dot notation (e.g., 'users.get', 'orders.create')",
                    E_USER_WARNING
                );
            }
        }
    }

    /**
     * Validate params structure.
     *
     * @param array<string, mixed> $params
     * @throws EmptyFieldException
     */
    private function validateParams(array $params): void
    {
        foreach ($params as $paramName => $paramValue) {
            if (!is_string($paramName) || trim($paramName) === '') {
                throw EmptyFieldException::forField('parameter name');
            }

            // Check for runtime expression syntax: $result.field
            if (is_string($paramValue) && str_starts_with($paramValue, '$')) {
                if (!preg_match('/^\$result\.[a-zA-Z_][a-zA-Z0-9_.]*$/', $paramValue)) {
                    trigger_error(
                        "Warning: Parameter '{$paramName}' uses runtime expression but may have invalid syntax: '{$paramValue}'",
                        E_USER_WARNING
                    );
                }
            }
        }
    }
}
