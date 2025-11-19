<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Contract\Middleware;

use RunOpenCode\Component\Query\Executor\TransactionScope;

/**
 * Execution context.
 *
 * Execution context holds configurations that can be required by middlewares
 * during execution. It tracks usage of configurations to notify whether all
 * configurations have been used or not.
 *
 * If all configurations have not been used, it may indicate misconfiguration
 * of middleware stack or missing middleware that should have used certain
 * configuration.
 */
interface ContextInterface
{
    /**
     * Query or statement being executed.
     *
     * @var non-empty-string
     */
    public string $source {
        get;
    }

    /**
     * Configuration objects for execution.
     *
     * @var object[]
     */
    public array $configurations {
        get;
    }

    /**
     * Get transaction scope, if available.
     *
     * @var TransactionScope|null
     */
    public ?TransactionScope $transaction {
        get;
    }

    /**
     * Peaks configuration object from context.
     *
     * Configuration is requested by providing type of object, and if such object is
     * found within context, it is returned. If matching configuration is not found,
     * null is returned.
     *
     * You may peak for configuration object by its reference as well.
     *
     * When configuration object is returned, it is NOT marked as used within context.
     *
     * @template T of object
     *
     * @param class-string<T>|T $type
     *
     * @return T|null
     */
    public function peak(object|string $type): ?object;

    /**
     * Filter configurations within context.
     *
     * Filtering does NOT marks configuration object as used within context.
     *
     * @template T
     *
     * @param callable(($type is null ? object : T)): bool $predicate Filter function applied to configurations.
     * @param class-string<T>|null                         $type      You may optionally provide which types of configurations should be subject of filtering.
     *
     * @return ($type is null ? list<object> : list<T>)
     */
    public function filter(callable $predicate, ?string $type = null): array;

    /**
     * Requires configuration object from context.
     *
     * Configuration is requested by providing type of object, and if such object is
     * found within context, it is returned. If matching configuration is not found,
     * null is returned.
     *
     * When configuration object is returned, it is marked as used within context.
     *
     * You may also require configuration object by reference which automatically
     * marks object as used within context.
     *
     * @template T of object
     *
     * @param class-string<T>|T $type
     *
     * @return T|null
     */
    public function require(object|string $type): ?object;

    /**
     * Indicates whether context is depleted (has no unused configurations).
     */
    public function depleted(): bool;

    /**
     * Yields all unused configurations.
     *
     * @return iterable<object>
     */
    public function unused(): iterable;

    /**
     * Yields all used configurations.
     *
     * @return iterable<object>
     */
    public function used(): iterable;
}
