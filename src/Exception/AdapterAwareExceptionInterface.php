<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Exception;

use RunOpenCode\Component\Query\Contract\Executor\AdapterInterface;

/**
 * Exceptions which are thrown by adapter may contain reference to adapter which thrown them.
 */
interface AdapterAwareExceptionInterface extends \Throwable
{
    public ?AdapterInterface $adapter {
        get;
    }
}
