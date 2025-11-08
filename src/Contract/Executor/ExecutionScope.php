<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Contract\Executor;

/**
 * Determines where within transactional scope query/statement may be executed.
 *
 * Having in mind that it is possible to have nested transactions, queries/statements
 * may be executed within different levels of transaction nesting.
 *
 * Each nested transaction may define transaction for different connections, and therefore,
 * queries/statements may be executed using connection that are in parent transaction, or,
 * may be executed using connection for which transaction is not started at all.
 *
 * Transactional scope is safe guard, where developers must explicitly allow execution of
 * queries/statements within nested transactional scope, or execution of queries/statements
 * using connections for which transaction have not been started at all.
 *
 * By default, all implementations should implement {@see ExecutionScope::Strict} scope
 * execution check.
 */
enum ExecutionScope
{
    /**
     * Denotes that query/statement may be executed for connections for which
     * transaction started in any nesting level of current transaction execution
     * scope, as well as for connections for which transaction is not even started.
     */
    case None;

    /**
     * Denotes that query/statement may be executed for connections for which
     * transaction started in any nesting level of current transaction execution
     * scope.
     */
    case Parent;

    /**
     * Denotes that transaction scope allows execution of queries/statements
     * with connection within this transaction execution scope only.
     *
     * This is safest and default behaviour.
     */
    case Strict;
}
