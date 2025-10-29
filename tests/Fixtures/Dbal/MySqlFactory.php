<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Tests\Fixtures\Dbal;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Logging\Middleware as DbalLoggingMiddleware;
use RunOpenCode\Component\Query\Tests\Fixtures\Log\BufferingLogger;

/**
 * Creates MySQL connections for testing purposes.
 */
final class MySqlFactory
{
    private static self $instance;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        // noop.
    }

    /**
     * @return array{Connection, BufferingLogger}
     */
    public function create(MySqlDatabase $database): array
    {
        $configuration = new Configuration();
        $logger        = new BufferingLogger();

        $configuration->setMiddlewares([new DbalLoggingMiddleware($logger)]);

        $connection = DriverManager::getConnection([
            'driver'   => 'mysqli',
            'dbname'   => $database->value,
            'user'     => 'roc',
            'password' => 'roc',
            'host'     => 'mysql.local',
        ], $configuration);

        return [$connection, $logger];
    }
}
