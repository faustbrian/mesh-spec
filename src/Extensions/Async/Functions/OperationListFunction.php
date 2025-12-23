<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Forrst\Extensions\Async\Functions;

use Cline\Forrst\Attributes\Descriptor;
use Cline\Forrst\Contracts\OperationRepositoryInterface;
use Cline\Forrst\Data\OperationData;
use Cline\Forrst\Extensions\Async\Descriptors\OperationListDescriptor;
use Cline\Forrst\Functions\AbstractFunction;

use function array_map;
use function in_array;
use function is_int;
use function is_string;
use function sprintf;
use function strlen;

/**
 * Async operation listing function.
 *
 * Implements forrst.operation.list for retrieving paginated operation lists.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://docs.cline.sh/forrst/extensions/async
 */
#[Descriptor(OperationListDescriptor::class)]
final class OperationListFunction extends AbstractFunction
{
    /**
     * Create a new operation list function instance.
     *
     * @param OperationRepositoryInterface $repository Operation repository
     */
    public function __construct(
        private readonly OperationRepositoryInterface $repository,
    ) {}

    /**
     * Execute the operation list function.
     *
     * @throws \InvalidArgumentException If any argument is invalid
     *
     * @return array{operations: array<int, array<string, mixed>>, next_cursor?: string} Paginated operations
     */
    public function __invoke(): array
    {
        $status = $this->requestObject->getArgument('status');
        $function = $this->requestObject->getArgument('function');
        $limit = $this->requestObject->getArgument('limit', 50);
        $cursor = $this->requestObject->getArgument('cursor');

        $this->validateStatus($status);
        $this->validateFunction($function);
        $this->validateLimit($limit);
        $this->validateCursor($cursor);

        $result = $this->repository->list($status, $function, $limit, $cursor);

        $response = [
            'operations' => array_map(
                fn (OperationData $op): array => $op->toArray(),
                $result['operations'],
            ),
        ];

        if (isset($result['next_cursor']) && $result['next_cursor'] !== null) {
            $response['next_cursor'] = $result['next_cursor'];
        }

        return $response;
    }

    /**
     * Validate status filter.
     *
     * @param mixed $status Status filter value
     *
     * @throws \InvalidArgumentException If status is invalid
     */
    private function validateStatus(mixed $status): void
    {
        if ($status !== null && !is_string($status)) {
            throw new \InvalidArgumentException('status must be a string or null');
        }

        if ($status !== null && !in_array($status, ['pending', 'processing', 'completed', 'failed', 'cancelled'], true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid status filter: %s', $status),
            );
        }
    }

    /**
     * Validate function filter.
     *
     * @param mixed $function Function filter value
     *
     * @throws \InvalidArgumentException If function is invalid
     */
    private function validateFunction(mixed $function): void
    {
        if ($function !== null && !is_string($function)) {
            throw new \InvalidArgumentException('function must be a string or null');
        }
    }

    /**
     * Validate pagination limit.
     *
     * @param mixed $limit Limit value
     *
     * @throws \InvalidArgumentException If limit is invalid
     */
    private function validateLimit(mixed $limit): void
    {
        if (!is_int($limit)) {
            throw new \InvalidArgumentException('limit must be an integer');
        }

        if ($limit < 1 || $limit > 100) {
            throw new \InvalidArgumentException('Limit must be between 1 and 100');
        }
    }

    /**
     * Validate pagination cursor.
     *
     * @param mixed $cursor Cursor value
     *
     * @throws \InvalidArgumentException If cursor is invalid
     */
    private function validateCursor(mixed $cursor): void
    {
        if ($cursor !== null && !is_string($cursor)) {
            throw new \InvalidArgumentException('cursor must be a string or null');
        }

        if ($cursor === null) {
            return;
        }

        $decoded = base64_decode($cursor, true);

        if ($decoded === false) {
            throw new \InvalidArgumentException('Invalid pagination cursor format');
        }

        if (strlen($decoded) > 256) {
            throw new \InvalidArgumentException('Pagination cursor is too large');
        }
    }
}
