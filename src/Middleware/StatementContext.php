<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Middleware;

use RunOpenCode\Component\Query\Contract\Configuration\ExecutionInterface;
use RunOpenCode\Component\Query\Contract\Context\StatementContextInterface;
use RunOpenCode\Component\Query\Contract\Context\TransactionContextInterface;
use RunOpenCode\Component\Query\Exception\LogicException;

/**
 * Context of the statement execution.
 *
 * @internal
 */
final readonly class StatementContext implements StatementContextInterface
{
    /**
     * Registry of middlewares configurations.
     */
    public MiddlewaresConfiguration $middlewares;

    /**
     * Create new statement context.
     *
     * @param non-empty-string                 $statement      Statement to execute.
     * @param ExecutionInterface               $execution      Adapter configuration.
     * @param TransactionContextInterface|null $transaction    Transaction context, if exists.
     * @param object                           ...$middlewares Middlewares configurations.
     */
    public function __construct(
        public string                       $statement,
        public ExecutionInterface           $execution,
        public ?TransactionContextInterface $transaction = null,
        object                              ...$middlewares,
    ) {
        if (null === $this->execution->connection) {
            throw new LogicException('Statement context can not be created without specified connection name.');
        }

        $this->middlewares = MiddlewaresConfiguration::create(...$middlewares);
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
    public function withExecution(ExecutionInterface $configuration): self
    {
        $instance = new self(
            $this->statement,
            $configuration,
            $this->transaction,
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
            $this->statement,
            $this->execution,
            $this->transaction,
            ...\array_values($configurations),
        );

        $instance->middlewares->sync($this->middlewares);

        return $instance;
    }

    /**
     * Clone this context.
     */
    public function __clone(): void
    {
        // @phpstan-ignore-next-line
        $this->middlewares = clone $this->middlewares;
    }
}
