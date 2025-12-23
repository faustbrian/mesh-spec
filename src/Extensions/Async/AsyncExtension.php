<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Forrst\Extensions\Async;

use Cline\Forrst\Contracts\FunctionInterface;
use Cline\Forrst\Contracts\OperationRepositoryInterface;
use Cline\Forrst\Contracts\ProvidesFunctionsInterface;
use Cline\Forrst\Data\ErrorData;
use Cline\Forrst\Data\ExtensionData;
use Cline\Forrst\Data\OperationData;
use Cline\Forrst\Data\OperationStatus;
use Cline\Forrst\Data\RequestObjectData;
use Cline\Forrst\Data\ResponseData;
use Cline\Forrst\Extensions\AbstractExtension;
use Cline\Forrst\Extensions\Async\Exceptions\InvalidOperationStateException;
use Cline\Forrst\Extensions\Async\Exceptions\OperationNotFoundException;
use Cline\Forrst\Extensions\Async\Functions\OperationCancelFunction;
use Cline\Forrst\Extensions\Async\Functions\OperationListFunction;
use Cline\Forrst\Extensions\Async\Functions\OperationStatusFunction;
use Cline\Forrst\Extensions\ExtensionUrn;
use Cline\Forrst\Functions\FunctionUrn;
use Override;

use function array_merge;
use function assert;
use function bin2hex;
use function filter_var;
use function in_array;
use function is_string;
use function json_encode;
use function max;
use function min;
use function now;
use function parse_url;
use function random_bytes;
use function sprintf;
use function strlen;
use function strtolower;

use const JSON_THROW_ON_ERROR;

use const FILTER_FLAG_NO_PRIV_RANGE;
use const FILTER_FLAG_NO_RES_RANGE;
use const FILTER_VALIDATE_IP;

/**
 * Async operations extension handler.
 *
 * Enables long-running function execution by decoupling request initiation from
 * result delivery. Functions return immediately with an operation ID that clients
 * poll for completion, preventing timeout issues for expensive operations.
 *
 * Supports optional webhook callbacks to notify clients when operations complete,
 * reducing polling overhead for very long operations.
 *
 * Request options:
 * - preferred: boolean - client prefers async execution if supported
 * - callback_url: string - optional URL for POST notification on completion
 *
 * Response data:
 * - operation_id: unique identifier for tracking operation status
 * - status: current state (pending, processing, completed, failed, cancelled)
 * - poll: function call specification for status checks
 * - retry_after: suggested wait duration before next poll
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://docs.cline.sh/forrst/extensions/async
 */
final class AsyncExtension extends AbstractExtension implements ProvidesFunctionsInterface
{
    /**
     * Default retry interval in seconds for polling.
     */
    private const int DEFAULT_RETRY_SECONDS = 5;

    /**
     * Allowed URL schemes for callback URLs (HTTPS only for security).
     */
    private const array ALLOWED_CALLBACK_SCHEMES = ['https'];

    /**
     * Blocked hosts to prevent SSRF attacks.
     */
    private const array BLOCKED_CALLBACK_HOSTS = [
        'localhost',
        '127.0.0.1',
        '0.0.0.0',
        '169.254.169.254', // AWS metadata endpoint
        '::1',
        'metadata.google.internal', // GCP metadata
    ];

    /**
     * Maximum allowed callback URL length to prevent DoS.
     */
    private const int MAX_CALLBACK_URL_LENGTH = 2048;

    /**
     * Maximum metadata size in bytes (64KB) to prevent storage bloat and DoS.
     */
    private const int MAX_METADATA_SIZE_BYTES = 65536;

    /**
     * Number of random bytes for operation ID (96 bits of entropy).
     *
     * Provides 2^96 possible IDs (~7.9 × 10^28), making collisions
     * astronomically unlikely even with billions of operations.
     */
    private const int OPERATION_ID_BYTES = 12;

    /**
     * Create a new async extension instance.
     *
     * @param OperationRepositoryInterface $operations Repository for persisting and retrieving
     *                                                 async operation state across polling requests.
     *                                                 Implementations must support concurrent access
     *                                                 and atomic updates for distributed deployments.
     */
    public function __construct(
        private readonly OperationRepositoryInterface $operations,
    ) {}

    /**
     * Get the functions provided by this extension.
     *
     * Returns the operation management functions (status, cancel, list) that are
     * automatically registered when the async extension is enabled on a server.
     *
     * @return array<int, class-string<FunctionInterface>> Function class names
     */
    #[Override()]
    public function functions(): array
    {
        return [
            OperationStatusFunction::class,
            OperationCancelFunction::class,
            OperationListFunction::class,
        ];
    }

    #[Override()]
    public function getUrn(): string
    {
        return ExtensionUrn::Async->value;
    }

    #[Override()]
    public function isErrorFatal(): bool
    {
        return true;
    }

    /**
     * Check if client prefers async execution.
     *
     * Function handlers should check this flag to decide between sync and async
     * execution. The server is not obligated to honor the preference but should
     * use it as a hint when the operation supports both modes.
     *
     * @param null|array<string, mixed> $options Extension options from request
     *
     * @return bool True if client indicated async preference
     */
    public function isPreferred(?array $options): bool
    {
        return ($options['preferred'] ?? false) === true;
    }

    /**
     * Get callback URL for completion notification.
     *
     * If provided, the server should POST the operation result to this URL when
     * execution completes, allowing clients to avoid polling for long operations.
     *
     * Validates URL format, enforces HTTPS, and blocks internal/private IPs to
     * prevent SSRF (Server-Side Request Forgery) attacks.
     *
     * @param null|array<string, mixed> $options Extension options from request
     *
     * @return null|string Callback URL or null if not specified
     *
     * @throws \InvalidArgumentException If callback URL is invalid or blocked
     */
    public function getCallbackUrl(?array $options): ?string
    {
        $callbackUrl = $options['callback_url'] ?? null;

        if ($callbackUrl === null) {
            return null;
        }

        if (!is_string($callbackUrl)) {
            throw new \InvalidArgumentException(
                'Callback URL must be a string',
            );
        }

        // Validate max URL length (prevent DoS)
        if (strlen($callbackUrl) > self::MAX_CALLBACK_URL_LENGTH) {
            throw new \InvalidArgumentException(
                'Callback URL exceeds maximum length of 2048 characters',
            );
        }

        // Validate URL format
        $parts = parse_url($callbackUrl);

        if ($parts === false) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid callback URL format: %s',
                $callbackUrl,
            ));
        }

        // Enforce HTTPS only
        if (!isset($parts['scheme']) || !in_array($parts['scheme'], self::ALLOWED_CALLBACK_SCHEMES, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Callback URL must use HTTPS scheme, got: %s',
                $parts['scheme'] ?? 'none',
            ));
        }

        // Block internal/private IPs (SSRF protection)
        $host = $parts['host'] ?? '';

        if (in_array(strtolower($host), self::BLOCKED_CALLBACK_HOSTS, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Callback URL host is not allowed: %s',
                $host,
            ));
        }

        // Block private IP ranges
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            if (!filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new \InvalidArgumentException(sprintf(
                    'Callback URL cannot use private/reserved IP addresses: %s',
                    $host,
                ));
            }
        }

        return $callbackUrl;
    }

    /**
     * Create an async operation and build immediate response.
     *
     * Function handlers call this method when deciding to execute asynchronously.
     * It creates a pending operation record, persists it to the repository, and
     * returns both the immediate response to send to the client and the operation
     * record for background processing.
     *
     * The response includes polling instructions and retry timing to optimize
     * client polling behavior.
     *
     * @param RequestObjectData         $request      Original function call request
     * @param ExtensionData             $extension    Async extension data from request
     * @param null|array<string, mixed> $metadata     Optional metadata stored with operation
     * @param int                       $retrySeconds Suggested seconds between poll attempts
     *
     * @return array{response: ResponseData, operation: OperationData} Tuple of response and operation
     *
     * @throws \InvalidArgumentException If metadata size exceeds maximum allowed (64KB)
     */
    public function createAsyncOperation(
        RequestObjectData $request,
        ExtensionData $extension,
        ?array $metadata = null,
        int $retrySeconds = self::DEFAULT_RETRY_SECONDS,
    ): array {
        // Validate metadata size
        if ($metadata !== null) {
            $metadataJson = json_encode($metadata, JSON_THROW_ON_ERROR);
            $metadataSize = strlen($metadataJson);

            if ($metadataSize > self::MAX_METADATA_SIZE_BYTES) {
                throw new \InvalidArgumentException(sprintf(
                    'Operation metadata size (%d bytes) exceeds maximum allowed (%d bytes)',
                    $metadataSize,
                    self::MAX_METADATA_SIZE_BYTES,
                ));
            }
        }

        $systemMetadata = [
            'original_request_id' => $request->id,
            'callback_url' => $this->getCallbackUrl($extension->options),
            'created_at' => now()->toIso8601String(),
        ];

        $finalMetadata = $metadata !== null
            ? array_merge($metadata, $systemMetadata)
            : $systemMetadata;

        // Create the operation record
        $operation = new OperationData(
            id: $this->generateOperationId(),
            function: $request->call->function,
            version: $request->call->version,
            status: OperationStatus::Pending,
            metadata: $finalMetadata,
        );

        // Persist the operation
        $this->operations->save($operation);

        // Build the async response
        $response = ResponseData::success(
            result: null,
            id: $request->id,
            extensions: [
                ExtensionData::response(ExtensionUrn::Async->value, [
                    'operation_id' => $operation->id,
                    'status' => $operation->status,
                    'poll' => [
                        'function' => FunctionUrn::OperationStatus->value,
                        'version' => '1',
                        'arguments' => ['operation_id' => $operation->id],
                    ],
                    'retry_after' => [
                        'value' => $retrySeconds,
                        'unit' => 'second',
                    ],
                ]),
            ],
        );

        return [
            'response' => $response,
            'operation' => $operation,
        ];
    }

    /**
     * Transition operation to processing status.
     *
     * Background workers should call this when beginning execution to signal
     * to polling clients that work has started.
     *
     * @param string     $operationId Unique operation identifier
     * @param null|float $progress    Optional initial progress value (0.0 to 1.0)
     *
     * @throws OperationNotFoundException If operation doesn't exist
     * @throws InvalidOperationStateException If operation cannot be marked as processing
     */
    public function markProcessing(string $operationId, ?float $progress = null): void
    {
        $operation = $this->operations->find($operationId);

        if (!$operation instanceof OperationData) {
            throw new OperationNotFoundException(sprintf(
                'Cannot mark operation %s as processing: operation not found',
                $operationId,
            ));
        }

        // Validate state transitions
        if ($operation->status === OperationStatus::Completed) {
            throw new InvalidOperationStateException(sprintf(
                'Cannot mark operation %s as processing: already completed',
                $operationId,
            ));
        }

        if ($operation->status === OperationStatus::Failed) {
            throw new InvalidOperationStateException(sprintf(
                'Cannot mark operation %s as processing: operation failed',
                $operationId,
            ));
        }

        if ($operation->status === OperationStatus::Cancelled) {
            throw new InvalidOperationStateException(sprintf(
                'Cannot mark operation %s as processing: operation was cancelled',
                $operationId,
            ));
        }

        $updated = new OperationData(
            id: $operation->id,
            function: $operation->function,
            version: $operation->version,
            status: OperationStatus::Processing,
            progress: $progress,
            startedAt: now()->toImmutable(),
            metadata: $operation->metadata,
        );

        $this->operations->save($updated);
    }

    /**
     * Complete operation with successful result.
     *
     * Background workers call this when execution finishes successfully.
     * The result is stored and subsequent polling requests will receive it.
     *
     * @param string $operationId Unique operation identifier
     * @param mixed  $result      Function execution result to return to client
     *
     * @throws OperationNotFoundException If operation doesn't exist
     * @throws InvalidOperationStateException If operation cannot be completed
     */
    public function complete(string $operationId, mixed $result): void
    {
        $operation = $this->operations->find($operationId);

        if (!$operation instanceof OperationData) {
            throw new OperationNotFoundException(sprintf(
                'Cannot complete operation %s: operation not found',
                $operationId,
            ));
        }

        // Validate state transitions
        if ($operation->status === OperationStatus::Completed) {
            throw new InvalidOperationStateException(sprintf(
                'Cannot complete operation %s: already completed',
                $operationId,
            ));
        }

        if ($operation->status === OperationStatus::Cancelled) {
            throw new InvalidOperationStateException(sprintf(
                'Cannot complete operation %s: operation was cancelled',
                $operationId,
            ));
        }

        $updated = new OperationData(
            id: $operation->id,
            function: $operation->function,
            version: $operation->version,
            status: OperationStatus::Completed,
            progress: 1.0,
            result: $result,
            startedAt: $operation->startedAt,
            completedAt: now()->toImmutable(),
            metadata: $operation->metadata,
        );

        $this->operations->save($updated);
    }

    /**
     * Fail operation with error details.
     *
     * Background workers call this when execution encounters unrecoverable errors.
     * The errors are stored and subsequent polling requests will receive them.
     *
     * @param string                $operationId Unique operation identifier
     * @param array<int, ErrorData> $errors      Error details describing the failure
     *
     * @throws OperationNotFoundException If operation doesn't exist
     * @throws InvalidOperationStateException If operation cannot be failed
     */
    public function fail(string $operationId, array $errors): void
    {
        $operation = $this->operations->find($operationId);

        if (!$operation instanceof OperationData) {
            throw new OperationNotFoundException(sprintf(
                'Cannot fail operation %s: operation not found',
                $operationId,
            ));
        }

        // Validate state transitions
        if ($operation->status === OperationStatus::Completed) {
            throw new InvalidOperationStateException(sprintf(
                'Cannot fail operation %s: already completed',
                $operationId,
            ));
        }

        if ($operation->status === OperationStatus::Failed) {
            throw new InvalidOperationStateException(sprintf(
                'Cannot fail operation %s: already failed',
                $operationId,
            ));
        }

        if ($operation->status === OperationStatus::Cancelled) {
            throw new InvalidOperationStateException(sprintf(
                'Cannot fail operation %s: operation was cancelled',
                $operationId,
            ));
        }

        $updated = new OperationData(
            id: $operation->id,
            function: $operation->function,
            version: $operation->version,
            status: OperationStatus::Failed,
            progress: $operation->progress,
            errors: $errors,
            startedAt: $operation->startedAt,
            completedAt: now()->toImmutable(),
            metadata: $operation->metadata,
        );

        $this->operations->save($updated);
    }

    /**
     * Update operation progress for long-running tasks.
     *
     * Background workers can call this periodically during execution to provide
     * progress feedback to polling clients. Progress is clamped to [0.0, 1.0]
     * and cannot decrease from its current value.
     *
     * @param string      $operationId Unique operation identifier
     * @param float       $progress    Progress value between 0.0 (started) and 1.0 (complete)
     * @param null|string $message     Optional human-readable status message
     *
     * @throws OperationNotFoundException If operation doesn't exist
     * @throws InvalidOperationStateException If operation cannot have progress updated
     * @throws \InvalidArgumentException If progress decreases or message exceeds maximum length
     */
    public function updateProgress(string $operationId, float $progress, ?string $message = null): void
    {
        $operation = $this->operations->find($operationId);

        if (!$operation instanceof OperationData) {
            throw new OperationNotFoundException(sprintf(
                'Cannot update progress for operation %s: operation not found',
                $operationId,
            ));
        }

        // Validate operation is in a state where progress updates make sense
        if (!in_array($operation->status, [OperationStatus::Pending, OperationStatus::Processing], true)) {
            throw new InvalidOperationStateException(sprintf(
                'Cannot update progress for operation %s: operation is in %s state',
                $operationId,
                $operation->status->value,
            ));
        }

        // Validate progress doesn't decrease
        $currentProgress = $operation->progress ?? 0.0;
        $newProgress = max(0.0, min(1.0, $progress));

        if ($newProgress < $currentProgress) {
            throw new \InvalidArgumentException(sprintf(
                'Progress cannot decrease from %.2f to %.2f for operation %s',
                $currentProgress,
                $newProgress,
                $operationId,
            ));
        }

        $metadata = $operation->metadata ?? [];

        if ($message !== null) {
            if (strlen($message) > 1000) {
                throw new \InvalidArgumentException(
                    'Progress message cannot exceed 1000 characters',
                );
            }
            $metadata['progress_message'] = $message;
            $metadata['progress_updated_at'] = now()->toIso8601String();
        }

        $updated = new OperationData(
            id: $operation->id,
            function: $operation->function,
            version: $operation->version,
            status: $operation->status,
            progress: $newProgress,
            result: $operation->result,
            errors: $operation->errors,
            startedAt: $operation->startedAt,
            completedAt: $operation->completedAt,
            cancelledAt: $operation->cancelledAt,
            metadata: $metadata,
        );

        $this->operations->save($updated);
    }

    /**
     * Generate cryptographically unique operation ID.
     *
     * Uses 12 random bytes (96 bits) encoded as hex, providing sufficient
     * uniqueness for distributed operation tracking without coordination.
     * Checks for collisions and retries if necessary.
     *
     * @return string Operation identifier with 'op_' prefix
     *
     * @throws \RuntimeException If unable to generate unique ID after maximum attempts
     */
    private function generateOperationId(): string
    {
        $maxAttempts = 10;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $operationId = 'op_'.bin2hex(random_bytes(self::OPERATION_ID_BYTES));

            // Check if ID already exists
            $existing = $this->operations->find($operationId);

            if ($existing === null) {
                return $operationId;
            }

            // Collision detected (extremely rare), try again
        }

        throw new \RuntimeException(sprintf(
            'Failed to generate unique operation ID after %d attempts',
            $maxAttempts,
        ));
    }
}
