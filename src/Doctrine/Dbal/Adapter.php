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
use RunOpenCode\Component\Query\Contract\Executor\AdapterInterface;
use RunOpenCode\Component\Query\Contract\Executor\OptionsInterface;
use RunOpenCode\Component\Query\Contract\Executor\ParametersInterface;
use RunOpenCode\Component\Query\Contract\Executor\ResultInterface;
use RunOpenCode\Component\Query\Contract\Executor\TransactionInterface;
use RunOpenCode\Component\Query\Doctrine\Isolator;
use RunOpenCode\Component\Query\Doctrine\Transaction;
use RunOpenCode\Component\Query\Exception\BeginTransactionException;
use RunOpenCode\Component\Query\Exception\CommitTransactionException;
use RunOpenCode\Component\Query\Exception\ConnectionException;
use RunOpenCode\Component\Query\Exception\DeadlockException;
use RunOpenCode\Component\Query\Exception\DriverException;
use RunOpenCode\Component\Query\Exception\LogicException;
use RunOpenCode\Component\Query\Exception\RollbackTransactionException;
use RunOpenCode\Component\Query\Exception\RuntimeException;
use RunOpenCode\Component\Query\Exception\SyntaxException;

/**
 * DbalAdapter uses Doctrine Dbal to execute SQL queries and statements.
 *
 * @implements AdapterInterface<Transaction, Options, Result>
 */
final readonly class Adapter implements AdapterInterface
{
    /**
     * A stack of currently active transactions.
     *
     * @var \SplStack<Transaction>
     */
    private \SplStack $transactions;

    /**
     * Instance of transaction level isolation utility.
     *
     * @var Isolator
     */
    private Isolator $isolator;

    /**
     * @param non-empty-string $name
     */
    public function __construct(
        public string     $name,
        public Connection $connection
    ) {
        $this->transactions = new \SplStack();
        $this->isolator     = new Isolator($this->name, $this->connection);
    }

    /**
     * {@inheritdoc}
     */
    public function defaults(string $class): object
    {
        return match (true) {
            \is_a($class, OptionsInterface::class, true) => Options::connection($this->name),
            \is_a($class, TransactionInterface::class, true) => Transaction::connection($this->name),
            default => throw new LogicException(\sprintf(
                'Class %s is not supported.',
                $class
            )),
        };
    }

    /**
     * {@inheritdoc}
     */
    public function begin(TransactionInterface $transaction): void
    {
        \assert($transaction->connection === $this->name, new LogicException(\sprintf(
            'Transaction for connection "%s" requested, "%s" used.',
            $transaction->connection,
            $this->name,
        )));

        $this->isolator->isolate($transaction->isolation);

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

        $this->transactions->push($transaction);
    }

    /**
     * {@inheritdoc}
     */
    public function commit(): void
    {
        try {
            $this->connection->commit();
        } catch (GenericDbalConnectionException|DbalConnectionException $exception) {
            throw new ConnectionException(\sprintf(
                'Unable to commit transaction for connection "%s" due to connection error.',
                $this->name,
            ), $exception);
        } catch (\Exception $exception) {
            throw new CommitTransactionException(\sprintf(
                'Unable to commit transaction for connection "%s".',
                $this->name,
            ), $exception);
        } finally {
            $this->transactions->pop();
            $this->isolator->revert();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rollback(): void
    {
        try {
            $this->connection->rollBack();
        } catch (GenericDbalConnectionException|DbalConnectionException $exception) {
            throw new ConnectionException(\sprintf(
                'Unable to rollback transaction for connection "%s" due to connection error.',
                $this->name,
            ), $exception);
        } catch (\Exception $exception) {
            throw new RollbackTransactionException(\sprintf(
                'Unable to rollback transaction for connection "%s".',
                $this->name,
            ), $exception);
        } finally {
            $this->transactions->pop();
            $this->isolator->revert();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function query(string $query, OptionsInterface $options, ?ParametersInterface $parameters = null): ResultInterface
    {
        // Prepare query invocation closure.
        $invocation = static function(Connection $connection) use ($query, $parameters): ResultInterface {
            return new Result($connection->executeQuery(
                $query,
                $parameters->values ?? [],
                $parameters->types ?? [],
            ));
        };

        return $this->execute($query, $options, $invocation);
    }

    /**
     * {@inheritdoc}
     */
    public function statement(string $query, OptionsInterface $options, ?ParametersInterface $parameters = null): int
    {
        // Prepare statement invocation closure.
        $invocation = static function(Connection $connection) use ($query, $parameters): int {
            return (int)$connection->executeStatement(
                $query,
                $parameters->values ?? [],
                $parameters->types ?? [],
            );
        };

        return $this->execute($query, $options, $invocation);
    }

    /**
     * Execute SQL statement within invokable closure.
     *
     * A common execution logic executing SQL statement.
     *
     * @template T of ResultInterface|int
     *
     * @param string                  $query      SQL query being executed.
     * @param Options                 $options    Execution options.
     * @param callable(Connection): T $invocation Query invocation closure.
     *
     * @return T
     */
    private function execute(string $query, Options $options, callable $invocation): ResultInterface|int
    {
        $isolate = null !== $options->isolation && $options->isolation !== $this->isolator->get();

        $this->isolator->isolate($options->isolation);

        try {
            // @phpstan-ignore-next-line
            $result = $isolate ? $this->connection->transactional($invocation) : $invocation($this->connection);
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
            $this->isolator->revert();
        }

        /** @var T $result */
        return $result;
    }
}
