<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Contract\Context;

use RunOpenCode\Component\Query\Contract\Configuration\ExecutionInterface;

/**
 * Context of query execution available for query middlewares.
 */
interface QueryContextInterface extends ContextInterface
{
    /**
     * Query being executed.
     *
     * @var non-empty-string
     */
    public string $query {
        get;
    }

    /**
     * Configuration for query execution.
     */
    public ExecutionInterface $execution {
        get;
    }

    /**
     * Current transactional context, if any.
     */
    public ?TransactionContextInterface $transaction {
        get;
    }

    /**
     * Replace current adapter execution configuration with new one.
     *
     * @param ExecutionInterface $configuration New adapter execution configuration.
     *
     * @return self New instance of query context with provided adapter execution configuration.
     */
    public function withExecution(ExecutionInterface $configuration): self;
}
