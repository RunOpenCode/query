<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Exception;

class InvalidArgumentException extends \InvalidArgumentException implements ExceptionInterface
{
    public function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    /**
     * Creates an exception for invalid types.
     *
     * @param string|string[] $expected The expected type(s).
     * @param mixed           $actual   The actual value.
     * @param \Throwable|null $previous The previous exception.
     */
    public static function type(array|string $expected, mixed $actual, ?\Throwable $previous = null): self
    {
        $message = \sprintf(
            'Expected value of type "%s", got "%s".',
            \implode('", "', (array)$expected),
            \gettype($actual),
        );

        // @phpstan-ignore-next-line We accept unsafe "new static()".
        return new static($message, $previous);
    }
}
