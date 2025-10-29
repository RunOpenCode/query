<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Tests\Fixtures\Dbal;

use Doctrine\DBAL\Connection;
use RunOpenCode\Component\Query\Tests\Fixtures\Log\BufferingLogger;

/**
 * Registry for database connections and their loggers.
 */
final class Registry
{
    private static self $instance;

    /**
     * @var \WeakMap<Connection, BufferingLogger>
     */
    private \WeakMap $registry;

    private function __construct()
    {
        $this->registry = new \WeakMap();
    }

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function createSqlLiteConnection(string $table = 'test', string $dataset = 'empty'): Connection
    {
        [$connection, $logger] = SqlLiteFactory::instance()->create();

        $this->registry->offsetSet($connection, $logger);

        DatasetFactory::instance()->prepare($connection, $table, $dataset);

        $logger->clear();

        return $connection;
    }

    public function createMySqlConnection(MySqlDatabase $database, string $table = 'test', string $dataset = 'empty'): Connection
    {
        [$connection, $logger] = MySqlFactory::instance()->create($database);

        $this->registry->offsetSet($connection, $logger);

        DatasetFactory::instance()->prepare($connection, $table, $dataset);

        $logger->clear();

        return $connection;
    }

    public function getLogger(Connection $connection): BufferingLogger
    {
        // @phpstan-ignore-next-line
        return $this->registry->offsetGet($connection) ?? throw new \RuntimeException('Logger for given connection is not registered.');
    }

    public function clearLogs(): void
    {
        foreach ($this->registry as $logger) {
            $logger->clear();
        }
    }

    public function clear(): void
    {
        foreach ($this->registry as $connection => $logger) {
            $connection->close();
            $logger->clear();
        }

        $this->registry = new \WeakMap();
    }
}
