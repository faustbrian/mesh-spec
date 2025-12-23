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
use Cline\Forrst\Exceptions\FieldOutOfRangeException;
use Cline\Forrst\Exceptions\InvalidFieldTypeException;
use Cline\Forrst\Extensions\Async\Descriptors\OperationListDescriptor;
use Cline\Forrst\Functions\AbstractFunction;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use function array_map;
use function count;
use function is_int;
use function is_string;
use function min;

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
     * @param LoggerInterface              $logger     Logger for recording operations
     */
    public function __construct(
        private readonly OperationRepositoryInterface $repository,
        private readonly LoggerInterface $logger = new NullLogger(),
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

        if ($status !== null && !is_string($status)) {
            throw InvalidFieldTypeException::forField('status', 'string', $status);
        }

        if ($function !== null && !is_string($function)) {
            throw InvalidFieldTypeException::forField('function', 'string', $function);
        }

        if (!is_int($limit)) {
            throw InvalidFieldTypeException::forField('limit', 'integer', $limit);
        }

        if ($limit < 1 || $limit > 100) {
            throw FieldOutOfRangeException::forField('limit', 1, 100);
        }

        // Enforce safe maximum
        $limit = min($limit, 50);

        if ($cursor !== null && !is_string($cursor)) {
            throw InvalidFieldTypeException::forField('cursor', 'string', $cursor);
        }

        $result = $this->repository->list($status, $function, $limit, $cursor);

        $this->logger->debug('Operations listed', [
            'count' => count($result['operations']),
            'status_filter' => $status,
            'function_filter' => $function,
            'limit' => $limit,
            'has_next_page' => $result['next_cursor'] !== null,
        ]);

        $response = [
            'operations' => array_map(
                fn (OperationData $op): array => $op->toArray(),
                $result['operations'],
            ),
        ];

        if ($result['next_cursor'] !== null) {
            $response['next_cursor'] = $result['next_cursor'];
        }

        return $response;
    }
}
