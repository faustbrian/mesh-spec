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
 * Logical grouping tag for organizing functions in the discovery document.
 *
 * Tags provide a way to group related functions together for better organization
 * and discoverability in API documentation. Functions can be associated with one
 * or more tags to indicate their domain, category, or functional area. Tags may
 * include metadata such as summaries, descriptions, and links to external docs.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://docs.cline.sh/specs/forrst/discovery#tag-object
 * @see https://docs.cline.sh/forrst/
 */
final class TagData extends Data
{
    /**
     * Create a new tag instance.
     *
     * @param string                $name         Unique identifier for the tag used to reference the tag from
     *                                            function definitions. Should use kebab-case or snake_case for
     *                                            consistency (e.g., 'user-management', 'billing', 'analytics').
     *                                            Required field that serves as the tag's primary key.
     * @param null|string           $summary      Brief one-line summary of the tag's purpose. Displayed in API
     *                                            documentation and navigation interfaces. Should be concise
     *                                            (typically under 60 characters) and clearly identify the
     *                                            functional area the tag represents.
     * @param null|string           $description  Detailed explanation of the tag's scope and the types of functions
     *                                            it contains. May include usage guidance, architectural context,
     *                                            or organizational information. Supports markdown formatting for
     *                                            rich documentation in tools that render discovery documents.
     * @param null|ExternalDocsData $externalDocs Reference to external documentation providing additional context
     *                                            about the tag's functional area. Useful for linking to detailed
     *                                            guides, tutorials, or architectural documentation that explains
     *                                            the tagged functions in greater depth.
     *
     * @throws EmptyFieldException If tag name is empty
     * @throws FieldExceedsMaxLengthException If tag name exceeds maximum length
     */
    public function __construct(
        public readonly string $name,
        public readonly ?string $summary = null,
        public readonly ?string $description = null,
        public readonly ?ExternalDocsData $externalDocs = null,
    ) {
        // Validate name
        $trimmedName = trim($name);
        if ($trimmedName === '') {
            throw EmptyFieldException::forField('name');
        }

        if (mb_strlen($trimmedName) > 50) {
            throw FieldExceedsMaxLengthException::forField('name', 50);
        }

        // Recommend kebab-case or snake_case for consistency
        if (!preg_match('/^[a-z][a-z0-9_-]*$/', $trimmedName)) {
            trigger_error(
                "Warning: Tag name '{$trimmedName}' should use lowercase kebab-case or snake_case " .
                "(e.g., 'user-management', 'billing', 'analytics')",
                E_USER_WARNING
            );
        }

        $this->name = $trimmedName;

        // Validate summary length
        if ($this->summary !== null && mb_strlen($this->summary) > 60) {
            trigger_error(
                'Warning: Tag summary should be brief (under 60 characters). Got ' . mb_strlen($this->summary),
                E_USER_WARNING
            );
        }
    }
}
