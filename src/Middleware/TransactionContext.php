<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Middleware;

use RunOpenCode\Component\Query\Contract\Configuration\ExecutionInterface;
use RunOpenCode\Component\Query\Contract\Configuration\ExecutionScope;
use RunOpenCode\Component\Query\Contract\Configuration\TransactionInterface;
use RunOpenCode\Component\Query\Contract\Context\TransactionContextInterface;
use RunOpenCode\Component\Query\Exception\InvalidArgumentException;
use RunOpenCode\Component\Query\Exception\LogicException;

/**
 * Context of transaction execution.
 *
 * @internal
 */
final readonly class TransactionContext implements TransactionContextInterface
{
    /**
     * Registry of middlewares configurations.
     */
    public MiddlewaresConfiguration $middlewares;

    /**
     * Registry of transaction configurations.
     */
    private TransactionConfigurations $configurations;

    /**
     * Create new transaction context.
     *
     * @param non-empty-list<TransactionInterface> $configurations Configuration objects for transaction.
     * @param TransactionContextInterface|null     $parent         Parent transaction context, if exists.
     * @param object                               ...$middlewares Middlewares configurations.
     */
    public function __construct(
        array                               $configurations,
        public ?TransactionContextInterface $parent,
        object                              ...$middlewares,
    ) {
        $this->configurations = TransactionConfigurations::create(...$configurations);
        $this->middlewares    = MiddlewaresConfiguration::create(...$middlewares);
    }

    /**
     * {@inheritdoc}
     */
    public function accepts(ExecutionScope $scope, ExecutionInterface|string $connection): bool
    {
        $name = $connection instanceof ExecutionInterface ? $connection->connection : $connection;

        \assert(null !== $name, new LogicException('Connection name must be provided with adapter configuration.'));

        if (ExecutionScope::None === $scope) {
            return true;
        }

        if (ExecutionScope::Strict === $scope) {
            return $this->configurations->has($name);
        }
        if ($this->configurations->has($name)) {
            return true;
        }
        return (bool) $this->parent?->accepts($scope, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function peak(object|string $type): ?object
    {
        return $this->middlewares->peak($type);
    }

    /**
     * {@inheritdoc}
     */
    public function require(object|string $type): ?object
    {
        return $this->middlewares->require($type);
    }

    /**
     * {@inheritdoc}
     */
    public function append(TransactionInterface $configuration): self
    {
        $instance = new self(
            \array_values(\iterator_to_array($this->configurations->append($configuration))), // @phpstan-ignore-line
            $this->parent,
            ...\iterator_to_array($this->middlewares),
        );

        $instance->middlewares->sync($this->middlewares);

        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function remove(TransactionInterface|string $configuration): self
    {
        $instance = new self(
            \array_values(\iterator_to_array($this->configurations->remove($configuration))), // @phpstan-ignore-line
            $this->parent,
            ...\iterator_to_array($this->middlewares),
        );

        $instance->middlewares->sync($this->middlewares);

        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function withConfigurations(object...$configurations): self
    {
        $instance = new self(
            \array_values(\iterator_to_array($this->configurations)), // @phpstan-ignore-line
            $this->parent,
            ...\array_values($configurations),
        );

        $instance->middlewares->sync($this->middlewares);

        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function withTransactions(TransactionInterface ...$configurations): self
    {
        if (0 === \count($configurations)) {
            throw new InvalidArgumentException('At least one transaction configuration must be provided.');
        }

        $instance = new self(
            \array_values($configurations),
            $this->parent,
            ...\iterator_to_array($this->middlewares),
        );

        $instance->middlewares->sync($this->middlewares);

        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        yield from $this->configurations;
    }

    /**
     * Clone this context.
     */
    public function __clone(): void
    {
        $this->middlewares    = clone $this->middlewares;  // @phpstan-ignore-line
        $this->configurations = clone $this->configurations; // @phpstan-ignore-line
    }
}
