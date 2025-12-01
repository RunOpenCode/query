<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Contract\Context;

use RunOpenCode\Component\Query\Contract\Configuration\ExecutionInterface;

/**
 * Context of statement execution available for statement middlewares.
 */
interface StatementContextInterface extends ContextInterface
{
    /**
     * Statement being executed.
     *
     * @var non-empty-string
     */
    public string $statement {
        get;
    }

    /**
     * Configuration for statement execution.
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
     * @return self New instance of statement context with provided adapter execution configuration.
     */
    public function withExecution(ExecutionInterface $configuration): self;
}
