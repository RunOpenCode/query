<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Middleware;

use RunOpenCode\Component\Query\Contract\Configuration\TransactionInterface;
use RunOpenCode\Component\Query\Exception\InvalidArgumentException;
use RunOpenCode\Component\Query\Exception\LogicException;

/**
 * Transaction configurations registry.
 *
 * @implements \IteratorAggregate<non-empty-string, TransactionInterface>
 *
 * @internal
 */
final readonly class TransactionConfigurations implements \IteratorAggregate
{
    /**
     * Transaction configurations.
     *
     * @param array<non-empty-string, TransactionInterface> $configurations
     */
    private function __construct(private array $configurations)
    {
        // noop.
    }

    /**
     * Create new transaction configurations registry.
     *
     * @param TransactionInterface ...$configurations Transaction configurations to register.
     *
     * @return self New instance of transaction configurations registry.
     */
    public static function create(TransactionInterface ...$configurations): self
    {
        if (0 === \count($configurations)) {
            throw new InvalidArgumentException('Transactions context must have at least one transaction configuration provided.');
        }

        $instance = new self([]);

        foreach ($configurations as $configuration) {
            $instance = $instance->append($configuration);
        }

        return $instance;
    }

    /**
     * Check if connection is within transaction configurations registry.
     *
     * @param string $connection Connection name.
     *
     * @return bool TRUE if connection is within transaction configurations registry.
     */
    public function has(string $connection): bool
    {
        return isset($this->configurations[$connection]);
    }

    /**
     * Append additional transaction configuration to registry.
     *
     * @param TransactionInterface $configuration Additional transaction configuration to register.
     *
     * @return self New instance of transaction configuration registry with additional configuration.
     *
     * @throws LogicException If connection is already within registry.
     */
    public function append(TransactionInterface $configuration): self
    {
        \assert(null !== $configuration->connection, new LogicException('Transaction configuration must be provided with connection name.'));

        if (isset($this->configurations[$configuration->connection])) {
            throw new LogicException(\sprintf(
                'Connection "%s" is already within transaction context.',
                $configuration->connection
            ));
        }

        $transactions = $this->configurations;

        $transactions[$configuration->connection] = $configuration;

        return new self($transactions);
    }

    /**
     * Remove transaction configuration from registry.
     *
     * @param TransactionInterface|non-empty-string $configuration Transaction configuration or connection to remove.
     *
     * @return self New instance of transaction configuration registry.
     *
     * @throws LogicException If transaction configuration (or connection) is not within registry.
     */
    public function remove(TransactionInterface|string $configuration): self
    {
        $connection = $configuration instanceof TransactionInterface ? $configuration->connection : $configuration;

        \assert(null !== $connection, new LogicException('Transaction configuration must be provided with connection name.'));

        if (!isset($this->configurations[$connection])) {
            throw new LogicException(\sprintf(
                'Connection "%s" is not within transaction configuration registry.',
                $connection
            ));
        }

        $transactions = $this->configurations;

        unset($transactions[$connection]);

        \assert(0 !== \count($transactions), new LogicException('There must be at least one transaction configuration in configuration registry.'));

        return new self($transactions);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        yield from $this->configurations;
    }
}
