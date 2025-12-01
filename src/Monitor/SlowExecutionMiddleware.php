<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Monitor;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use RunOpenCode\Component\Query\Contract\Context\QueryContextInterface;
use RunOpenCode\Component\Query\Contract\Context\StatementContextInterface;
use RunOpenCode\Component\Query\Contract\Context\TransactionContextInterface;
use RunOpenCode\Component\Query\Contract\Executor\AffectedInterface;
use RunOpenCode\Component\Query\Contract\Executor\ParametersInterface;
use RunOpenCode\Component\Query\Contract\Executor\ResultInterface;
use RunOpenCode\Component\Query\Contract\Middleware\QueryMiddlewareInterface;
use RunOpenCode\Component\Query\Contract\Middleware\StatementMiddlewareInterface;
use RunOpenCode\Component\Query\Contract\Middleware\TransactionMiddlewareInterface;
use RunOpenCode\Component\Query\Exception\LogicException;

/**
 * Monitor for slow queries, statements and transactions on application level.
 *
 * This middleware is intended to be used in production.
 */
final readonly class SlowExecutionMiddleware implements QueryMiddlewareInterface, StatementMiddlewareInterface, TransactionMiddlewareInterface
{
    private ?Slow $default;

    /**
     * Create new instance of slow execution monitor.
     *
     * @param LoggerInterface $logger    Logger to use for logging.
     * @param LogLevel::*     $level     Log level to use when adding record to a log.
     * @param positive-int    $threshold Default number of milliseconds under which execution of query is considered as "slow".
     * @param bool            $always    Flag denoting if every query and statement should be subject of monitoring.
     */
    public function __construct(
        private LoggerInterface $logger,
        private string          $level = LogLevel::ERROR,
        private int             $threshold = 100,
        bool                    $always = false,
    ) {
        $this->default = $always ? new Slow($this->threshold) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function query(string $query, QueryContextInterface $context, callable $next): ResultInterface
    {
        $configuration = $context->middlewares->require(Slow::class) ?? $this->default;

        if (null === $configuration) {
            return $next($query, $context);
        }

        [$result, $duration, $threshold, $slow] = $this->execute(
            $configuration,
            static fn(): ResultInterface => $next($query, $context)
        );

        if ($slow) {
            $this->logger->log($this->level, 'Slow query detected ("{identity}" using {parameters} parameters), execution time of {duration}ms exceeded configured threshold of {threshold}ms.', [
                'identity'   => $configuration->identity ?? $context->query,
                'duration'   => $duration,
                'parameters' => $context->peak(ParametersInterface::class)?->count() ?? 0,
                'threshold'  => $threshold,
            ]);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function statement(string $statement, StatementContextInterface $context, callable $next): AffectedInterface
    {
        $configuration = $context->middlewares->require(Slow::class) ?? $this->default;

        if (null === $configuration) {
            return $next($statement, $context);
        }

        [$result, $duration, $threshold, $slow] = $this->execute(
            $configuration,
            static fn(): AffectedInterface => $next($statement, $context)
        );

        if ($slow) {
            $this->logger->log($this->level, 'Slow statement detected ("{identity}" using {parameters} parameters), execution time of {duration}ms exceeded configured threshold of {threshold}ms.', [
                'identity'   => $configuration->identity ?? $context->statement,
                'duration'   => $duration,
                'parameters' => $context->peak(ParametersInterface::class)?->count() ?? 0,
                'threshold'  => $threshold,
            ]);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function transactional(callable $function, TransactionContextInterface $context, callable $next): mixed
    {
        $configuration = $context->middlewares->require(Slow::class) ?? $this->default;

        if (null === $configuration) {
            return $next($function, $context);
        }

        if (null === $configuration->identity) {
            throw new LogicException('Transaction execution can not be monitored without provided identity.');
        }

        [$result, $duration, $threshold, $slow] = $this->execute(
            $configuration,
            static fn(): mixed => $next($function, $context)
        );

        if ($slow) {
            $this->logger->log($this->level, 'Slow transaction detected ("{identity}"), execution time of {duration}ms exceeded configured threshold of {threshold}ms.', [
                'identity'  => $configuration->identity,
                'duration'  => $duration,
                'threshold' => $threshold,
            ]);
        }

        return $result;
    }

    /**
     * Execute query, statement or transaction and measure performances.
     *
     * @template T
     *
     * @param Slow          $configuration Monitoring configuration.
     * @param callable(): T $next          Next middleware call.
     *
     * @return array{T, non-negative-int, positive-int, bool}
     */
    private function execute(Slow $configuration, callable $next): array
    {
        $start     = \microtime(true);
        $result    = $next();
        $duration  = (int)((\microtime(true) - $start) * 1000);
        $threshold = $configuration->threshold ?? $this->threshold;

        return [$result, \max(0, $duration), $threshold, $duration > $threshold];
    }
}
