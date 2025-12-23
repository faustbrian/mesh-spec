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
use Cline\Forrst\Exceptions\InvalidFieldTypeException;
use Cline\Forrst\Exceptions\InvalidFieldValueException;
use Cline\Forrst\Exceptions\OperationNotFoundException;
use Cline\Forrst\Extensions\Async\Descriptors\OperationStatusDescriptor;
use Cline\Forrst\Functions\AbstractFunction;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use function assert;
use function is_string;

/**
 * Async operation status check function.
 *
 * Implements forrst.operation.status for retrieving operation status.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://docs.cline.sh/forrst/extensions/async
 */
#[Descriptor(OperationStatusDescriptor::class)]
final class OperationStatusFunction extends AbstractFunction
{
    /**
     * Create a new operation status function instance.
     *
     * @param OperationRepositoryInterface $repository Operation repository
     * @param LoggerInterface              $logger     Logger for recording operations
     */
    public function __construct(
        private readonly OperationRepositoryInterface $repository,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Execute the operation status function.
     *
     * @throws OperationNotFoundException If the operation ID does not exist
     *
     * @return array<string, mixed> Operation status details
     */
    public function __invoke(): array
    {
        $operationId = $this->requestObject->getArgument('operation_id');

        if (!is_string($operationId)) {
            throw InvalidFieldTypeException::forField('operation_id', 'string', $operationId);
        }

        $this->validateOperationId($operationId);

        $operation = $this->repository->find($operationId);

        if (!$operation instanceof OperationData) {
            $this->logger->warning('Operation not found for status check', [
                'operation_id' => $operationId,
            ]);

            throw OperationNotFoundException::create($operationId);
        }

        $this->logger->debug('Operation status retrieved', [
            'operation_id' => $operationId,
            'status' => $operation->status->value,
            'function' => $operation->function,
        ]);

        return $operation->toArray();
    }

    /**
     * Validate operation ID format.
     *
     * @param string $operationId Operation ID to validate
     *
     * @throws \InvalidArgumentException If format is invalid
     */
    private function validateOperationId(string $operationId): void
    {
        if (!preg_match('/^op_[0-9a-f]{24}$/', $operationId)) {
            throw InvalidFieldValueException::forField(
                'operation_id',
                'Expected format: op_<24 hex characters>',
            );
        }
    }
}
