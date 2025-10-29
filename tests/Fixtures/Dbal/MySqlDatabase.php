<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Tests\Fixtures\Dbal;

/**
 * Available databases for testing.
 */
enum MySqlDatabase: string
{
    case Foo = 'component_query_foo';
    case Bar = 'component_query_bar';
    case Baz = 'component_query_baz';
}
