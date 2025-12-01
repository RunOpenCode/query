<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Tests\Middleware;

use RunOpenCode\Component\Query\Contract\Context\ContextInterface;
use RunOpenCode\Component\Query\Doctrine\Configuration\Dbal;
use RunOpenCode\Component\Query\Middleware\StatementContext;

final class StatementContextTest extends AbstractContextTestBase
{
    protected function createContext(object ...$configurations): ContextInterface
    {
        return new StatementContext('INSERT 1', new Dbal('foo'), null, ...$configurations);
    }
}
