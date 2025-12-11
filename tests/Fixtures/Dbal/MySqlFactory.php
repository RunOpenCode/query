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
    private const int MAX_ATTEMPTS = 15;

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

        for ($i = 0; $i < self::MAX_ATTEMPTS; ++$i) {
            $connection = DriverManager::getConnection([
                'driver'   => 'mysqli',
                'dbname'   => $database->value,
                'user'     => 'roc',
                'password' => 'roc',
                'host'     => 'mysql.local',
            ], $configuration);

            if (!$connection->isConnected()) {
                return [$connection, $logger];
            }

            try {
                $connection->getServerVersion();

                return [$connection, $logger];
            } catch (\Exception) {
                \sleep(1);
            }
        }

        throw new \RuntimeException(\sprintf(
            'Unable to connect to database "%s" after %d attempts.',
            $database->value,
            self::MAX_ATTEMPTS,
        ));
    }
}
