<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Tests\Fixtures\Log;

use Psr\Log\AbstractLogger;

/**
 * Logger to log into buffer.
 *
 * @phpstan-type LogRecord = array{string, string, array<string, mixed>}
 */
final class BufferingLogger extends AbstractLogger
{
    /**
     * @var list<array{string, string, array<string, mixed>}>
     */
    private array $logs = [];

    /**
     * Add record to log buffer.
     *
     * @param string               $level
     * @param string               $message
     * @param array<string, mixed> $context
     */
    public function log($level, $message, array $context = []): void
    {
        $this->logs[] = [$level, $message, $context];
    }

    /**
     * Peak into log buffer.
     *
     * @return list<LogRecord>
     */
    public function peak(): array
    {
        return $this->logs;
    }

    /**
     * Get and clear log buffer.
     *
     * @return list<LogRecord>
     */
    public function flush(): array
    {
        $logs = $this->logs;
        $this->logs = [];

        return $logs;
    }

    /**
     * Clear log buffer.
     */
    public function clear(): void
    {
        $this->logs = [];
    }
}
