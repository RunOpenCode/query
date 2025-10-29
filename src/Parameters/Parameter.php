<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Parameters;

final readonly class Parameter
{
    public function __construct(
        public mixed $value,
        public mixed $type = null
    ) {
        // noop
    }
}
