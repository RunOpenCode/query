<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Tests\PHPUnit;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\PreCondition;
use RunOpenCode\Component\Query\Tests\Fixtures\Dbal\MySqlDatabase;
use RunOpenCode\Component\Query\Tests\Fixtures\Dbal\Registry;

/**
 * Provides helper methods to deal with Doctrine DBAL in tests.
 */
trait DbalTools
{
    public function createSqlLiteConnection(string $table = 'test', string $dataset = 'default'): Connection
    {
        return Registry::instance()->createSqlLiteConnection($table, $dataset);
    }

    public function createMySqlConnection(MySqlDatabase $database, string $table = 'test', string $dataset = 'default'): Connection
    {
        return Registry::instance()->createMySqlConnection($database, $table, $dataset);
    }

    /**
     * Assert that specific connection did not execute any query nor statement.
     */
    public function assertConnectionDidNotExecute(Connection $connection): void
    {
        $this->assertCount(0, Registry::instance()->getLogger($connection)->peak(), 'Failed asserting that connection did not execute any query nor statement.');
    }

    /**
     * Assert that specific connection executed at least one query or statement.
     */
    public function assertConnectionDidExecute(Connection $connection): void
    {
        $this->assertGreaterThan(0, \count(Registry::instance()->getLogger($connection)->peak()), 'Failed asserting that connection executed at least one query or statement.');
    }

    /**
     * Assert that SQL log for specific connection is the same as expected.
     *
     * @param string[] $expected
     */
    public function assertSqlLogSame(array $expected, Connection $connection): void
    {
        $logs       = Registry::instance()->getLogger($connection)->peak();
        $statements = \array_values(\array_filter(\array_map(static function(array $log): ?string {
            return $log[2]['sql'] ?? match ($log[1]) {
                'Beginning transaction' => 'START TRANSACTION',
                'Committing transaction' => 'COMMIT',
                'Rolling back transaction' => 'ROLLBACK',
                default => null,
            };
        }, $logs)));

        $this->assertSame($expected, $statements);
    }

    #[PreCondition(-1000)]
    protected function clearAllDbalLogs(): void
    {
        Registry::instance()->clearLogs();
    }

    #[After]
    protected function clearDbalConnectionsAndLoggers(): void
    {
        Registry::instance()->clear();
    }
}
