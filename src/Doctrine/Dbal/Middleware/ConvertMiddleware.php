<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Doctrine\Dbal\Middleware;

use RunOpenCode\Component\Query\Contract\Context\QueryContextInterface;
use RunOpenCode\Component\Query\Contract\Executor\ResultInterface;
use RunOpenCode\Component\Query\Contract\Middleware\QueryMiddlewareInterface;
use RunOpenCode\Component\Query\Doctrine\Dbal\Adapter;
use RunOpenCode\Component\Query\Doctrine\Dbal\DatasetInterface;
use RunOpenCode\Component\Query\Exception\LogicException;
use RunOpenCode\Component\Query\Executor\AdapterRegistry;

/**
 * Converts results from Dbal layer according to the provided mapping.
 *
 * @phpstan-import-type DbalColumnType from Convert
 * @phpstan-import-type DbalColumCustomConverter from Convert
 * @phpstan-import-type Row from DatasetInterface
 */
final readonly class ConvertMiddleware implements QueryMiddlewareInterface
{
    public function __construct(private AdapterRegistry $registry)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function query(string $query, QueryContextInterface $context, callable $next): ResultInterface
    {
        $result        = $next($query, $context);
        $configuration = $context->require(Convert::class);

        if (null === $configuration) {
            return $result;
        }

        if (0 === \count($configuration)) {
            throw new LogicException('Configuration for Dbal result set data conversion middleware is empty.');
        }

        $adapter = $this->registry->get($result->connection);

        if (!$adapter instanceof Adapter) {
            throw new LogicException(\sprintf(
                'Middleware for column values conversion expects result set produced by instance of "%s", got "%s".',
                Adapter::class,
                $adapter::class,
            ));
        }

        $platform = $adapter->connection->getDatabasePlatform();

        /** @var ResultInterface<array-key, Row> $result */
        return new Converted($result, $configuration, $platform);
    }
}
