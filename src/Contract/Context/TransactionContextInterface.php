<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Contract\Context;

use RunOpenCode\Component\Query\Contract\Configuration\ExecutionInterface;
use RunOpenCode\Component\Query\Contract\Configuration\ExecutionScope;
use RunOpenCode\Component\Query\Contract\Configuration\TransactionInterface;
use RunOpenCode\Component\Query\Exception\LogicException;

/**
 * Context of transaction execution available for transaction middlewares.
 *
 * @extends \IteratorAggregate<non-empty-string, TransactionInterface>
 */
interface TransactionContextInterface extends \IteratorAggregate, ContextInterface
{
    /**
     * Parent transaction context, if exists.
     */
    public ?TransactionContextInterface $parent {
        get;
    }

    /**
     * Check if execution scope is allowed within current transaction context.
     *
     * @param ExecutionScope                      $scope      Execution scope.
     * @param ExecutionInterface|non-empty-string $connection Execution configuration or connection which will be used for execution of query or statement.
     */
    public function accepts(ExecutionScope $scope, ExecutionInterface|string $connection): bool;
    
    /**
     * Append additional transaction configuration to context.
     *
     * @param TransactionInterface $configuration Additional configuration of the transaction execution.
     *
     * @return self New instance of transaction context with additional connection into transactional scope.
     *
     * @throws LogicException If connection is already within transaction context.
     */
    public function append(TransactionInterface $configuration): self;

    /**
     * Remove transaction configuration from context.
     *
     * @param TransactionInterface|non-empty-string $configuration Transaction configuration or connection to remove.
     *
     * @return self New instance of transaction context without provided transaction configuration.
     *
     * @throws LogicException If transaction configuration (or connection) is not within transaction context.
     */
    public function remove(TransactionInterface|string $configuration): self;

    /**
     * Replace transaction configurations in context.
     *
     * @param TransactionInterface ...$configurations Transaction configurations to use.
     *
     * @return self New instance of transaction context with provided transaction configurations.
     */
    public function withTransactions(TransactionInterface ...$configurations): self;
}
