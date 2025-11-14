<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ConnectionException as GenericDbalConnectionException;
use Doctrine\DBAL\Exception\ConnectionException as DbalConnectionException;
use Doctrine\DBAL\TransactionIsolationLevel;
use RunOpenCode\Component\Query\Exception\ConnectionException;
use RunOpenCode\Component\Query\Exception\IsolationException;

/**
 * Tracks connection isolation levels.
 *
 * Utility class which helps tracking connection transaction isolation levels,
 * setting new level and reverting to previous one.
 *
 * @internal
 */
final readonly class Isolator
{
    /**
     * Stack where isolation level modifications are tracked.
     *
     * NULL denotes that reverse does not require modification of the current isolation level.
     *
     * @var \SplStack<TransactionIsolationLevel|null>
     */
    private \SplStack $isolations;

    /**
     * Create new instance of isolator.
     *
     * @param non-empty-string $name       Connection name.
     * @param Connection       $connection Connection.
     */
    public function __construct(
        private string     $name,
        private Connection $connection,

    ) {
        $this->isolations = new \SplStack();
    }

    /**
     * Set new isolation level for connection, if applicable.
     *
     * @param TransactionIsolationLevel|null $requested Requested isolation level.
     */
    public function isolate(?TransactionIsolationLevel $requested): void
    {
        if (null === $requested) {
            $this->isolations->push(null);
            return;
        }

        $current = $this->get();

        // Requested isolation is the same as current one.
        if ($current === $requested) {
            $this->isolations->push(null);
            return;
        }

        $this->set($requested);

        $this->isolations->push($current);
    }

    /**
     * Revert isolation level of connection to previous value, if applicable.
     */
    public function revert(): void
    {
        $previous = $this->isolations->pop();

        if (null === $previous) {
            return;
        }

        $current = $this->get();

        if ($current === $previous) {
            return;
        }

        $this->set($previous);
    }

    /**
     * Get current connection transaction isolation level.
     */
    public function get(): TransactionIsolationLevel
    {
        try {
            return $this->connection->getTransactionIsolation();
        } catch (GenericDbalConnectionException|DbalConnectionException $exception) {
            throw new ConnectionException(\sprintf(
                'Connection error occurred while trying to get current transaction isolation level for connection "%s".',
                $this->name,
            ), $exception,);
        } catch (\Exception $exception) {
            throw new IsolationException(\sprintf(
                'Unable to get transaction isolation level for connection "%s".',
                $this->name,
            ), $exception);
        }
    }

    /**
     * Set new transaction isolation level for the connection.
     */
    private function set(TransactionIsolationLevel $level): void
    {
        try {
            $this->connection->setTransactionIsolation($level);
        } catch (GenericDbalConnectionException|DbalConnectionException $exception) {
            throw new ConnectionException(\sprintf(
                'Connection error occurred while trying to set transaction isolation level "%s" for connection "%s".',
                $level->name,
                $this->name,
            ), $exception);
        } catch (\Exception $exception) {
            throw new IsolationException(\sprintf(
                'Unable to set transaction isolation level "%s" for connection "%s".',
                $level->name,
                $this->name,
            ), $exception);
        }
    }
}
