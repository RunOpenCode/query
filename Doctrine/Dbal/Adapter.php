<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Doctrine\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ConnectionException as GenericDbalConnectionException;
use Doctrine\DBAL\Driver\Exception as DbalDriverException;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\Exception\ConnectionException as DbalConnectionException;
use Doctrine\DBAL\Exception\DeadlockException as DbalDeadlockException;
use Doctrine\DBAL\Exception\SyntaxErrorException as DbalSyntaxErrorException;
use Doctrine\DBAL\TransactionIsolationLevel;
use RunOpenCode\Component\Query\Contract\Executor\AdapterInterface;
use RunOpenCode\Component\Query\Contract\Executor\OptionsInterface;
use RunOpenCode\Component\Query\Contract\Executor\ParametersInterface;
use RunOpenCode\Component\Query\Contract\Executor\ResultInterface;
use RunOpenCode\Component\Query\Contract\Executor\TransactionInterface;
use RunOpenCode\Component\Query\Doctrine\ExceptionWrapper;
use RunOpenCode\Component\Query\Doctrine\Transaction;
use RunOpenCode\Component\Query\Exception\BeginTransactionException;
use RunOpenCode\Component\Query\Exception\CommitTransactionException;
use RunOpenCode\Component\Query\Exception\ConnectionException;
use RunOpenCode\Component\Query\Exception\DeadlockException;
use RunOpenCode\Component\Query\Exception\DriverException;
use RunOpenCode\Component\Query\Exception\ExceptionInterface;
use RunOpenCode\Component\Query\Exception\IsolationException;
use RunOpenCode\Component\Query\Exception\LogicException;
use RunOpenCode\Component\Query\Exception\RollbackTransactionException;
use RunOpenCode\Component\Query\Exception\RuntimeException;
use RunOpenCode\Component\Query\Exception\SyntaxException;

/**
 * DbalAdapter uses Doctrine Dbal to execute SQL queries and statements.
 *
 * @implements AdapterInterface<Transaction, Options, Result>
 *
 * @phpstan-import-type ExecutionType from ExceptionWrapper
 *
 * @internal
 */
final readonly class Adapter implements AdapterInterface
{
    /**
     * Stores original isolation level which was active when transaction started.
     *
     * @var \WeakMap<TransactionInterface, TransactionIsolationLevel>
     */
    private \WeakMap $isolations;

    /**
     * @param non-empty-string $name
     */
    public function __construct(
        public string     $name,
        public Connection $connection
    ) {
        $this->isolations = new \WeakMap();
    }

    /**
     * {@inheritdoc}
     */
    public function begin(?TransactionInterface $transaction): TransactionInterface
    {
        $transaction = $transaction ?? new Transaction($this->name);

        if ($transaction->connection !== $this->name) {
            throw new LogicException(\sprintf(
                'Transaction for connection "%s" requested, "%s" used.',
                $transaction->connection,
                $this->name,
            ));
        }

        $current = $this->isolate($transaction->isolation);

        if (null !== $current) {
            $this->isolations->offsetSet($transaction, $current);
        }

        try {
            $this->connection->beginTransaction();
        } catch (GenericDbalConnectionException|DbalConnectionException $exception) {
            throw new ConnectionException(\sprintf(
                'Unable to begin transaction for connection "%s" due to connection error.',
                $transaction->connection,
            ), $exception);
        } catch (\Exception $exception) {
            throw new BeginTransactionException(\sprintf(
                'Unable to begin transaction for connection "%s".',
                $transaction->connection,
            ), $exception);
        }

        return $transaction;
    }

    /**
     * {@inheritdoc}
     */
    public function commit(TransactionInterface $transaction): void
    {
        try {
            $this->connection->commit();
        } catch (GenericDbalConnectionException|DbalConnectionException $exception) {
            throw new ConnectionException(\sprintf(
                'Unable to commit transaction for connection "%s" due to connection error.',
                $transaction->connection,
            ), $exception);
        } catch (\Exception $exception) {
            throw new CommitTransactionException(\sprintf(
                'Unable to commit transaction for connection "%s".',
                $transaction->connection,
            ), $exception);
        } finally {
            /** @var TransactionIsolationLevel|null $isolation */
            $isolation = $this->isolations->offsetGet($transaction);

            $this->isolations->offsetUnset($transaction);

            if (null !== $isolation) {
                // Revert to previous isolation level.
                $this->isolate($isolation);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rollback(TransactionInterface $transaction): void
    {
        try {
            $this->connection->rollBack();
        } catch (GenericDbalConnectionException|DbalConnectionException $exception) {
            throw new ConnectionException(\sprintf(
                'Unable to rollback transaction for connection "%s" due to connection error.',
                $transaction->connection,
            ), $exception);
        } catch (\Exception $exception) {
            throw new RollbackTransactionException(\sprintf(
                'Unable to rollback transaction for connection "%s".',
                $transaction->connection,
            ), $exception);
        } finally {
            /** @var TransactionIsolationLevel|null $isolation */
            $isolation = $this->isolations->offsetGet($transaction);

            $this->isolations->offsetUnset($transaction);

            if (null !== $isolation) {
                // Revert to previous isolation level.
                $this->isolate($isolation);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function query(string $query, ?ParametersInterface $parameters = null, ?OptionsInterface $options = null): ResultInterface
    {
        // Prepare query invocation closure.
        $invocation = static function(Connection $connection) use ($query, $parameters): ResultInterface {
            return new Result($connection->executeQuery(
                $query,
                $parameters->values ?? [],
                $parameters->types ?? [], // @phpstan-ignore-line
            ));
        };

        return $this->execute($query, $invocation, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function statement(string $query, ?ParametersInterface $parameters = null, ?OptionsInterface $options = null): int
    {
        // Prepare statement invocation closure.
        $invocation = static function(Connection $connection) use ($query, $parameters): int {
            return (int)$connection->executeStatement(
                $query,
                $parameters->values ?? [],
                $parameters->types ?? [], // @phpstan-ignore-line
            );
        };

        return $this->execute($query, $invocation, $options);
    }

    /**
     * Set transaction isolation level, if applicable.
     *
     * Method will modify current isolation level and return previously used
     * one, so isolation level could be reverted when transaction/query/statement
     * is completed.
     *
     * If change of isolation level is not requested, or current isolation level
     * is equal to the requested one, method will skip execution and NULL will be
     * returned.
     *
     * @param TransactionIsolationLevel|null $requested Requested new isolation level.
     *
     * @return TransactionIsolationLevel|null Previous isolation level, or null, if reverting to previous isolation level is not needed.
     *
     * @throws ConnectionException If there is an issue with database connection.
     * @throws IsolationException If there is an issue with setting new isolation level.
     */
    private function isolate(?TransactionIsolationLevel $requested): ?TransactionIsolationLevel
    {
        // Isolation is not requested.
        if (null === $requested) {
            return null;
        }

        try {
            $current = $this->connection->getTransactionIsolation();
        } catch (GenericDbalConnectionException|DbalConnectionException $exception) {
            throw new ConnectionException('Connection error occurred while trying to get transaction isolation level.', $exception);
        } catch (\Exception $exception) {
            throw new IsolationException('Unable to get transaction isolation level.', $exception);
        }

        // Requested isolation is the same as current one. 
        if ($current === $requested) {
            return null;
        }

        try {
            $this->connection->setTransactionIsolation($requested);
        } catch (GenericDbalConnectionException|DbalConnectionException $exception) {
            throw new ConnectionException('Connection error occurred while trying to set transaction isolation level.', $exception);
        } catch (\Exception $exception) {
            throw new IsolationException('Unable to set transaction isolation level.', $exception);
        }

        return $current;
    }

    /**
     * Execute SQL statement within invokable closure.
     *
     * A common execution logic executing SQL statement.
     *
     * @template T of ResultInterface|int
     *
     * @param string                  $query      SQL query being executed.
     * @param callable(Connection): T $invocation Query invocation closure.
     * @param Options|null            $options    Optional execution options.
     *
     * @return T
     */
    private function execute(string $query, callable $invocation, ?Options $options): ResultInterface|int
    {
        $current = $this->isolate($options?->isolation);
        $isolate = null !== $current;

        try {
            // @phpstan-ignore-next-line
            $result = $isolate ? $this->connection->transactional($invocation) : $invocation($this->connection);
        } catch (ExceptionInterface $exception) {
            // Re-throw library exception.
            throw $exception;
        } catch (GenericDbalConnectionException|DbalConnectionException $exception) {
            throw new ConnectionException(\sprintf(
                'Connection error occurred while trying to execute SQL query "%s" using executor connection "%s".',
                $query,
                $this->name,
            ), $exception);
        } catch (DbalSyntaxErrorException $exception) {
            throw new SyntaxException(\sprintf(
                'Syntax error found in SQL query "%s".',
                $query,
            ), $exception);
        } catch (DbalDeadlockException $exception) {
            throw new DeadlockException(\sprintf(
                'Deadlock error occurred while trying to execute SQL query "%s" using executor connection "%s".',
                $query,
                $this->name,
            ), $exception);
        } catch (DbalException|DbalDriverException $exception) {
            throw new DriverException(\sprintf(
                'Internal driver error occurred while executing SQL query "%s" using executor connection "%s".',
                $query,
                $this->name,
            ), $exception);
        } catch (\Exception $exception) {
            throw new RuntimeException(\sprintf(
                'Unknown error occurred while executing SQL query "%s" using executor connection "%s".',
                $query,
                $this->name,
            ), $exception);
        } finally {
            $this->isolate($current);
        }

        /** @var T $result */
        return $result;
    }
}