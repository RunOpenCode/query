<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Middleware;

use RunOpenCode\Component\Query\Contract\Configuration\ExecutionInterface;
use RunOpenCode\Component\Query\Contract\Context\MiddlewaresConfigurationInterface;
use RunOpenCode\Component\Query\Contract\Configuration\TransactionInterface;
use RunOpenCode\Component\Query\Exception\InvalidArgumentException;
use RunOpenCode\Component\Query\Exception\LogicException;

/**
 * Default implementation of {@see MiddlewaresConfigurationInterface}.
 *
 * @internal
 */
final readonly class MiddlewaresConfiguration implements MiddlewaresConfigurationInterface
{
    /**
     * Create registry of configurations for middlewares.
     *
     * @param \SplObjectStorage<object, bool> $registry Registry of configuration objects with value denoting if object has been required by middleware.
     */
    private function __construct(
        private \SplObjectStorage $registry = new \SplObjectStorage(),
    ) {
        // noop
    }

    /**
     * Initialize registry of configurations for middlewares.
     *
     * @param object ...$configurations List of configuration objects for middlewares.
     */
    public static function create(object ...$configurations): self
    {
        $instance = new self();

        foreach ($configurations as $configuration) {
            $instance = $instance->append($configuration);
        }

        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function has(object|string $type): bool
    {
        return null !== $this->peak($type);
    }

    /**
     * {@inheritdoc}
     */
    public function peak(object|string $type): ?object
    {
        $type = \is_object($type) ? $type::class : $type;

        foreach ($this->registry as $configuration) {
            if ($configuration instanceof $type) {
                return $configuration;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function require(object|string $type): ?object
    {
        $configuration = $this->peak($type);

        if (null === $configuration) {
            return null;
        }

        if ($this->registry->offsetGet($configuration)) {
            throw new LogicException(\sprintf(
                'Configuration of type "%s" has already been used by previous middleware.',
                \is_string($type) ? $type : \get_debug_type($type),
            ));
        }

        $this->registry->offsetSet($configuration, true);

        return $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function append(object $configuration): self
    {
        if ($configuration instanceof ExecutionInterface || $configuration instanceof TransactionInterface) {
            throw new InvalidArgumentException(\sprintf(
                'Instance of "%s" must not be appended to the middleware configurations registry, "%s" provided.',
                $configuration instanceof ExecutionInterface ? ExecutionInterface::class : TransactionInterface::class,
                $configuration::class,
            ));
        }

        if (null !== $this->peak($configuration)) {
            throw new LogicException(\sprintf(
                'Configuration of type "%s" is already within configuration registry.',
                \get_debug_type($configuration)
            ));
        }

        $registry = clone $this->registry;

        $registry->offsetSet($configuration, false);

        return new self($registry);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(object|string $configuration): self
    {
        $target = \is_object($configuration) ? $configuration : $this->peak($configuration);

        if (null === $target || !$this->registry->offsetExists($target)) {
            throw new LogicException(\sprintf(
                'Configuration of type "%s" does not exists within current context.',
                \is_string($configuration) ? $configuration : \get_debug_type($configuration)
            ));
        }

        $registry = clone $this->registry;

        $registry->offsetUnset($target);

        return new self($registry);
    }

    /**
     * Check if configuration has been used by middleware.
     *
     * @param object|class-string $configuration Configuration to check.
     */
    public function used(object|string $configuration): bool
    {
        $current = $this->peak($configuration);

        if (null === $current) {
            return false;
        }

        return $this->registry->offsetGet($current);
    }

    /**
     * Assert that all configuration objects has been used by middlewares.
     *
     * This method checks that all configuration objects has been used by middlewares
     * and it is invoked at the very end of execution by executor middleware only.
     *
     * Custom middlewares MUST NOT invoke this method.
     *
     * @throws LogicException If some of the configurations within the context are unutilized.
     */
    public function exhaust(): void
    {
        $unused = [];

        foreach ($this->registry as $configuration) {
            if ($this->registry->offsetGet($configuration)) {
                continue;
            }

            $unused[] = \get_debug_type($configuration);
        }

        if (0 === \count($unused)) {
            return;
        }

        throw new LogicException(\sprintf(
            'Configuration of type "%s" is unused by middlewares.',
            \implode('", "', $unused)
        ));
    }

    /**
     * Sync usage flags of this instance with other instance.
     *
     * @param MiddlewaresConfiguration $other Other instance with usage flags.
     */
    public function sync(self $other): void
    {
        foreach ($this->registry as $configuration) {
            if ($other->used($configuration)) {
                $this->require($configuration);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        foreach ($this->registry as $configuration) {
            yield $configuration;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->registry->count();
    }

    /**
     * Clone this bag.
     */
    public function __clone(): void
    {
        // @phpstan-ignore-next-line
        $this->registry = clone $this->registry;
    }
}
