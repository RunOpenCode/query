<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Exception;

/**
 * Utility class for middlewares.
 *
 * If middleware supports configuration of exceptions upon which
 * some middleware logic will be execute, this class provides method
 * to identify if type of thrown exception is the one upon which
 * some logic should be executed.
 */
final readonly class Catcher
{
    /**
     * Create new instance of exception catcher.
     *
     * @param list<class-string<\Throwable>> $catch Exceptions types to catch instead of re-thrown.
     */
    public function __construct(private array $catch)
    {
        // noop.
    }

    /**
     * Catch exception
     *
     * @template T of \Throwable
     *
     * @param T    $exception Exception to be caught, if matches configuration.
     * @param bool $previous  Should previous exceptions introspected as well.
     *
     * @return T Exception if matches configured exceptions to catch.
     *
     * @throws T Exception if does not matches configured exceptions to catch.
     */
    public function catch(\Throwable $exception, bool $previous = false): \Throwable
    {
        if ($this->catchable($exception, $previous)) {
            return $exception;
        }

        throw $exception;
    }

    /**
     * Check if exception would be caught.
     *
     * @param \Throwable $exception Exception to check.
     * @param bool       $previous  Should previous exceptions introspected as well.
     */
    public function catchable(\Throwable $exception, bool $previous = false): bool
    {
        if (\array_any($this->catch, static fn($catch): bool => $exception instanceof $catch)) {
            return true;
        }

        if (!$previous || !$exception->getPrevious() instanceof \Throwable) {
            return false;
        }

        return $this->catchable($exception->getPrevious(), $previous);
    }
}
