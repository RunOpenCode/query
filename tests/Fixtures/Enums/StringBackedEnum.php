<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Tests\Fixtures\Enums;

enum StringBackedEnum: string
{
    case Foo = 'foo';
    case Bar = 'bar';
    case Baz = 'baz';
}
