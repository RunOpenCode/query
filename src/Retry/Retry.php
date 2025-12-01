<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Retry;

use RunOpenCode\Component\Query\Exception\InvalidArgumentException;

/**
 * Retry query, statement or transaction on failure.
 */
final readonly class Retry
{
    /**
     * Create configuration for retry middleware.
     *
     * @param float|non-negative-int              $delay      How long to wait until next attempt in seconds (fractals supported).
     * @param positive-int                        $attempts   Maximum number of retry attempts.
     * @param positive-int                        $multiplier Attempt multiplier which allows you to make successive delays linear or exponential.
     * @param bool                                $unsafe     If set to TRUE, it will allow retry of query/statement/transaction inside of transactional scope.
     * @param list<class-string<\Exception>>|null $catch      Exceptions on which retry should be attempted. If null, default exceptions will be caught.
     */
    public function __construct(
        public int|float $delay = 0.01,
        public int       $attempts = 3,
        public int       $multiplier = 1,
        public bool      $unsafe = false,
        public ?array    $catch = null,
    ) {
        // noop.
    }

    /**
     * Calculate delay for given attempt.
     *
     * @param positive-int $attempt Current attempt number
     *
     * @return positive-int Number
     */
    public function delay(int $attempt): int
    {
        \assert($attempt <= $this->attempts, new InvalidArgumentException(\sprintf(
            'Maximum number of attempts exceeded: %d attempts',
            $attempt
        )));

        $seconds      = $this->delay * $attempt + $this->delay * $this->multiplier * ($attempt - 1);
        $microseconds = (int)\ceil($seconds * 1_000_000);

        /** @var positive-int $microseconds */
        return $microseconds;
    }
}
