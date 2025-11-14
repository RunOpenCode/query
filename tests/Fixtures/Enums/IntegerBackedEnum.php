<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Tests\Fixtures\Enums;

enum IntegerBackedEnum: int
{
    case Foo = 1;
    case Bar = 2;
    case Baz = 3;
}
