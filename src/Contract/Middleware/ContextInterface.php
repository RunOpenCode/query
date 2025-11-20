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
     * Create new context with replaced configuration.
     *
     * Context will preserve usage status of all configurations, including
     * replacement.
     *
     * @param class-string<object>|object $subject     Current configuration which is being replaced.
     * @param object                      $replacement New replacement configuration.
     *
     * @return self New instance of execution context.
     */
    public function replace(object|string $subject, object $replacement): self;

    /**
     * Create new context with additional configuration.
     * 
     * @param object $configuration Configuration to append.
     *
     * @return self New instance of execution context.
     */
    public function append(object $configuration): self;

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
