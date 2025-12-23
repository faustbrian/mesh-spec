<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Forrst\Enums;

/**
 * Priority levels for replay operations in the Forrst replay extension.
 *
 * Defines execution priority for queued replay operations. Higher priority
 * replays are processed before lower priority ones when multiple replays
 * are queued. Priority affects scheduling order but does not guarantee
 * immediate execution.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://docs.cline.sh/forrst/extensions/replay
 */
enum ReplayPriority: string
{
    /**
     * High priority replay operations processed before normal and low priority.
     *
     * Used for time-sensitive or critical replays that should be processed
     * as soon as possible when the replay queue has capacity.
     */
    case High = 'high';

    /**
     * Normal priority replay operations processed in standard queue order.
     *
     * Default priority level for most replay operations. Provides balanced
     * scheduling without preferential treatment or deferral.
     */
    case Normal = 'normal';

    /**
     * Low priority replay operations processed after normal and high priority.
     *
     * Used for non-urgent replays that can be deferred when higher priority
     * work is available. Useful for batch operations or background tasks.
     */
    case Low = 'low';

    /**
     * Get the numeric weight for this priority level.
     *
     * Higher values indicate higher priority. Used for queue sorting
     * and priority comparisons. Values are spaced to allow future
     * priority levels to be inserted between existing ones.
     *
     * @return int Priority weight (10=low, 50=normal, 90=high)
     */
    public function getWeight(): int
    {
        return match ($this) {
            self::High => 90,
            self::Normal => 50,
            self::Low => 10,
        };
    }

    /**
     * Compare this priority with another priority.
     *
     * Returns a negative number if this priority is lower, zero if equal,
     * or a positive number if this priority is higher. Compatible with
     * usort() and other comparison-based sorting functions.
     *
     * @param self $other Priority to compare against
     * @return int Comparison result: negative (lower), 0 (equal), positive (higher)
     */
    public function compareTo(self $other): int
    {
        return $this->getWeight() <=> $other->getWeight();
    }

    /**
     * Check if this priority is higher than another priority.
     *
     * @param self $other Priority to compare against
     * @return bool True if this priority should be processed before the other
     */
    public function isHigherThan(self $other): bool
    {
        return $this->getWeight() > $other->getWeight();
    }

    /**
     * Check if this priority is lower than another priority.
     *
     * @param self $other Priority to compare against
     * @return bool True if this priority should be processed after the other
     */
    public function isLowerThan(self $other): bool
    {
        return $this->getWeight() < $other->getWeight();
    }

    /**
     * Check if this priority is the same as another priority.
     *
     * @param self $other Priority to compare against
     * @return bool True if priorities are equal
     */
    public function isEqualTo(self $other): bool
    {
        return $this === $other;
    }
}
