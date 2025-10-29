<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Contract\Executor;

/**
 * Configures checks of query/statement execution within transactional scope.
 *
 * By default, all queries/statements should be executed within same transactional
 * scope.
 *
 * However, it is possible to create nested transactions, as well as execute queries/statements
 * which are using connections that are not in transactional state.
 *
 * With this configuration parameter, it is possible to override default behaviour, however, if
 * not used with care, could lead to unexpected result and behaviour.
 */
enum TransactionalScope
{
    /**
     * Denotes that transaction scope allows execution of queries/statements
     * using connections outside of transactional scope.
     *
     * In general, this will denote that execution of query will not check
     * if used connection is in transactional scope at all.
     */
    case None;

    /**
     * Denotes that transaction scope allows execution of queries/statements
     * with connections within outer transactional scope.
     */
    case Parent;

    /**
     * Denotes that transaction scope allows execution of queries/statements
     * with connection within this transactional scope only.
     *
     * This is safe and default behaviour.
     */
    case Strict;
}
