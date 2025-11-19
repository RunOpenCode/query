<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Middleware;

use RunOpenCode\Component\Query\Contract\Middleware\ContextInterface;
use RunOpenCode\Component\Query\Exception\LogicException;
use RunOpenCode\Component\Query\Executor\TransactionScope;

/**
 * Default implementation of {@see ContextInterface}.
 *
 * @internal
 */
final readonly class Context implements ContextInterface
{
    /**
     * Holds used configurations.
     *
     * @var \WeakMap<object, true>
     */
    private \WeakMap $used;

    /**
     * Create execution context.
     *
     * @param non-empty-string  $source         Query or statement being executed.
     * @param object[]          $configurations Configurations for middlewares.
     * @param ?TransactionScope $transaction    Current transactional scope, if any.
     */
    public function __construct(
        public string            $source,
        public array             $configurations = [],
        public ?TransactionScope $transaction = null,
    ) {
        $this->used = new \WeakMap();
    }

    /**
     * {@inheritdoc}
     */
    public function peak(object|string $type): ?object
    {
        // @phpstan-ignore-next-line
        return \array_find(
            $this->configurations,
            static fn(object $configuration): bool => \is_string($type) ? \is_a($configuration, $type) : $type === $configuration
        );
    }

    /**
     * {@inheritdoc}
     */
    public function filter(callable $predicate, ?string $type = null): array
    {
        $configurations = \array_filter(
            $this->configurations,
            static fn(object $configuration): bool => !\is_string($type) || \is_a($configuration, $type)
        );

        return \array_values(\array_filter($configurations, $predicate));
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

        if ($this->used->offsetExists($configuration)) {
            throw new LogicException(\sprintf(
                'Configuration of type "%s" has already been used by previous middleware.',
                \is_string($type) ? $type : \get_debug_type($type),
            ));
        }

        $this->used->offsetSet($configuration, true);

        return $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function depleted(): bool
    {
        return $this->used->count() === \count($this->configurations);
    }

    /**
     * {@inheritdoc}
     */
    public function unused(): iterable
    {
        foreach ($this->configurations as $configuration) {
            if (!$this->used->offsetExists($configuration)) {
                yield $configuration;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function used(): iterable
    {
        foreach ($this->configurations as $configuration) {
            if ($this->used->offsetExists($configuration)) {
                yield $configuration;
            }
        }
    }
}
