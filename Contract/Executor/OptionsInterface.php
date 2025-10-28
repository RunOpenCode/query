<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Contract\Executor;

interface OptionsInterface
{
    /**
     * Connection name to be used for query execution,
     * or null if default connection should be used.
     * 
     * Connection name is executor specific.
     * 
     * @var non-empty-string|null
     */
    public ?string $connection {
        get;
    }

    /**
     * Tags associated with the query execution, or null if none.
     *
     * @var non-empty-string[]|null
     */
    public ?array $tags {
        get;
    }

    /**
     * If query/statement is executed inside transactional scope,
     * configuration can override execution scope verification.
     * 
     * By default, only execution of query/statement is allowed
     * inside direct transactional scope of connection in transaction.
     * 
     * For certain use cases, you may override this behaviour and "loosen"
     * this rule.
     * 
     * Modifying configuration of the transactional scope may lead to unexpected
     * results and weird behaviours.
     */
    public ?TransactionalScope $scope {
        get;
    }

    /**
     * Checks query execution is tagged with given tag.
     *
     * @param non-empty-string $tag Tag to search for.
     *
     * @return bool True if tag is present, false otherwise.
     */
    public function has(string $tag): bool;
}
