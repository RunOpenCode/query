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
     * When configuration object is returned, it is NOT marked as used within context.
     *
     * @template T of object
     *
     * @param class-string<T> $type
     *
     * @return T|null
     */
    public function peak(string $type): ?object;

    /**
     * Requires configuration object from context.
     *
     * Configuration is requested by providing type of object, and if such object is
     * found within context, it is returned. If matching configuration is not found,
     * null is returned.
     *
     * When configuration object is returned, it is marked as used within context.
     *
     * @template T of object
     *
     * @param class-string<T> $type
     *
     * @return T|null
     */
    public function require(string $type): ?object;

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
}
