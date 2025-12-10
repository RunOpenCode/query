<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Contract\Context;

use RunOpenCode\Component\Query\Exception\LogicException;

/**
 * Middleware configuration registry.
 *
 * Middleware configuration registry holds configuration objects and track
 * their usage during execution lifetime.
 *
 * Registry distinct objects by their type, not by their addresses, which
 * means that two objects of same type can not be added to registry.
 *
 * @extends \IteratorAggregate<object>
 *
 * @internal
 */
interface MiddlewaresConfigurationInterface extends \IteratorAggregate, \Countable
{
    /**
     * Check if configuration object of given type is within registry.
     *
     * If instance is provided, type from instance is resolved.
     *
     * @param object|class-string $type Configuration instance or type.
     */
    public function has(object|string $type): bool;

    /**
     * Peaks for configuration object from registry.
     *
     * Configuration is requested by providing type of object, or instance from which
     * type is resolved.
     *
     * If matching configuration is not found, null is returned.
     *
     * When configuration object is returned, *IT IS NOT MARKED AS USED* within registry.
     *
     * @template T of object
     *
     * @param class-string<T>|T $type Configuration instance or its type.
     *
     * @return T|null
     */
    public function peak(object|string $type): ?object;

    /**
     * Requires configuration object from registry.
     *
     * Configuration is requested by providing type of object, or instance from which
     * type is resolved.
     *
     * If matching configuration is not found, null is returned.
     *
     * When configuration object is returned, *IT IS MARKED AS USED* within registry.
     *
     * @template T of object
     *
     * @param class-string<T>|T $type Configuration instance or its type.
     *
     * @return T|null
     *
     * @throws LogicException If configuration object has been already required from registry.
     */
    public function require(object|string $type): ?object;

    /**
     * Append new configuration object to the registry.
     *
     * @param object $configuration Configuration to append.
     *
     * @return self New instance of configurations registry with appended configuration.
     *
     * @throws LogicException If configuration of same type already exists within registry.
     */
    public function append(object $configuration): self;

    /**
     * Remove configuration from the registry.
     *
     * Configuration is requested by providing type of object, or instance from which
     * type is resolved.
     *
     * @param object|class-string<object> $configuration Configuration instance or its type to remove.
     *
     * @return self New instance of configurations without given configuration.
     *
     * @throws LogicException If configuration of given type does not exists within context.
     */
    public function remove(object|string $configuration): self;
}
