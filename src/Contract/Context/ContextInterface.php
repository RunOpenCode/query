<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Contract\Context;

use RunOpenCode\Component\Query\Exception\LogicException;

/**
 * Define execution context.
 *
 * Interface is not intended to be implemented by itself.
 */
interface ContextInterface
{
    /**
     * Registry of configuration objects for middlewares.
     */
    public MiddlewaresConfigurationInterface $middlewares {
        get;
    }

    /**
     * Peaks for middleware configuration object from middleware configurations registry.
     *
     * Proxy method for `$this->middlewares->peak()`, {@see MiddlewaresConfigurationInterface::peak()}.
     *
     * @template T of object
     *
     * @param class-string<T>|T $type Configuration instance or its type.
     *
     * @return T|null
     */
    public function peak(object|string $type): ?object;

    /**
     * Requires middleware configuration object from middleware configurations registry.
     *
     * Proxy method for `$this->middlewares->require()`, {@see MiddlewaresConfigurationInterface::require()}.
     *
     * @param class-string<T>|T $type Configuration instance or its type.
     *
     * @return T|null
     *
     * @throws LogicException If previous middleware used given configuration type.
     *
     * @template T of object
     *
     */
    public function require(object|string $type): ?object;

    /**
     * Replace current middlewares configurations with provided one.
     *
     * @param object ...$middlewares Ne middleware configurations.
     *
     * @return self New instance of context with middleware configurations.
     */
    public function withConfigurations(object ...$middlewares): self;
}
