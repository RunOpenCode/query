<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Middleware;

use RunOpenCode\Component\Query\Contract\Middleware\ContextInterface;
use RunOpenCode\Component\Query\Exception\LogicException;
use RunOpenCode\Component\Query\Executor\TransactionStack;

/**
 * Default implementation of ContextInterface.
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
     * @param object[]          $configuration Configurations for middlewares.
     * @param ?TransactionStack $transaction   Current transactional scope, if any.
     */
    public function __construct(
        private array            $configuration = [],
        public ?TransactionStack $transaction = null,
    ) {
        $this->used = new \WeakMap();
    }

    /**
     * {@inheritdoc}
     */
    public function peak(string $type): ?object
    {
        foreach ($this->configuration as $configuration) {
            if (\is_a($configuration, $type)) {
                return $configuration;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function require(string $type): ?object
    {
        $configuration = $this->peak($type);

        if (null === $configuration) {
            return null;
        }

        if ($this->used->offsetExists($configuration)) {
            throw new LogicException(\sprintf(
                'Configuration of type "%s" has already been used by previous middleware.',
                $type
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
        return $this->used->count() === \count($this->configuration);
    }

    /**
     * {@inheritdoc}
     */
    public function unused(): iterable
    {
        foreach ($this->configuration as $configuration) {
            if (!$this->used->offsetExists($configuration)) {
                yield $configuration;
            }
        }
    }
}
