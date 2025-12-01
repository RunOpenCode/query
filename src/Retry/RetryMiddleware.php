<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Retry;

use RunOpenCode\Component\Query\Contract\Executor\AffectedInterface;
use RunOpenCode\Component\Query\Contract\Executor\ResultInterface;
use RunOpenCode\Component\Query\Contract\Context\ContextInterface;
use RunOpenCode\Component\Query\Contract\Context\QueryContextInterface;
use RunOpenCode\Component\Query\Contract\Middleware\QueryMiddlewareInterface;
use RunOpenCode\Component\Query\Contract\Context\StatementContextInterface;
use RunOpenCode\Component\Query\Contract\Middleware\StatementMiddlewareInterface;
use RunOpenCode\Component\Query\Contract\Context\TransactionContextInterface;
use RunOpenCode\Component\Query\Contract\Middleware\TransactionMiddlewareInterface;
use RunOpenCode\Component\Query\Exception\Catcher;
use RunOpenCode\Component\Query\Exception\DeadlockException;
use RunOpenCode\Component\Query\Exception\LockWaitTimeoutException;
use RunOpenCode\Component\Query\Exception\LogicException;
use RunOpenCode\Component\Query\Exception\RuntimeException;

/**
 * Retry execution of query, statement or transaction on failure.
 */
final readonly class RetryMiddleware implements QueryMiddlewareInterface, StatementMiddlewareInterface, TransactionMiddlewareInterface
{
    private Catcher $catcher;

    /**
     * Create new instance of retry middleware.
     *
     * @param list<class-string<\Exception>>|null $catch Exceptions on which retry should be attempted. If not provided, defaults will be used.
     */
    public function __construct(?array $catch = null)
    {
        $this->catcher = new Catcher(!empty($catch) ? $catch : [
            DeadlockException::class,
            LockWaitTimeoutException::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function query(string $query, QueryContextInterface $context, callable $next): ResultInterface
    {
        return $this->execute(
            static fn(): ResultInterface => $next($query, $context),
            $context,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function statement(string $statement, StatementContextInterface $context, callable $next): AffectedInterface
    {
        return $this->execute(
            static fn(): AffectedInterface => $next($statement, $context),
            $context,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function transactional(callable $function, TransactionContextInterface $context, callable $next): mixed
    {
        return $this->execute(
            static fn(): mixed => $next($function, $context),
            $context,
        );
    }

    /**
     * @template T
     *
     * @param callable():T     $function
     * @param ContextInterface $context
     *
     * @return T
     */
    private function execute(callable $function, ContextInterface $context): mixed
    {
        $configuration = $context->require(Retry::class);

        if (null === $configuration) {
            return $function();
        }

        $transaction = match (true) {
            $context instanceof TransactionContextInterface => $context->parent,
            $context instanceof QueryContextInterface, $context instanceof StatementContextInterface => $context->transaction,
            default => throw new RuntimeException(\sprintf('Unsupported context implementation provided "%s".', $context::class))
        };

        if (null === $transaction && !$configuration->unsafe) {
            throw new LogicException('Retrying execution within transaction is unsafe. If you REALLY know what you are doing, you may override this behaviour by setting "unsafe" to "true" within your configuration.');
        }

        $attempt = 0;
        $first   = null;
        $catcher = $configuration->catch ? new Catcher($configuration->catch) : $this->catcher;

        do {
            if (0 !== $attempt) {
                \usleep($configuration->delay($attempt));
            }

            $attempt++;

            try {
                return $function();
            } catch (\Exception $exception) {
                $caught = $catcher->catch($exception);
                $first  = $first ?? $caught;
            }
        } while ($attempt < $configuration->attempts);

        throw $first;
    }
}
