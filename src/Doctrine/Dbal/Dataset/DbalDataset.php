<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Doctrine\Dbal\Dataset;

use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\Exception\DriverException as DbalDriverException;
use Doctrine\DBAL\Result as DbalResult;
use RunOpenCode\Component\Query\Doctrine\Dbal\DatasetInterface;
use RunOpenCode\Component\Query\Exception\DriverException;
use RunOpenCode\Component\Query\Exception\RuntimeException;

/**
 * Data set created from Doctrine Dbal result.
 */
final class DbalDataset implements DatasetInterface
{
    /**
     * Create new resultset.
     *
     * @param non-empty-string $connection Connection which was used to produce resultset.
     * @param DbalResult       $result     Doctrine Dbal result set.
     */
    public function __construct(
        public readonly string $connection,
        private DbalResult     $result
    ) {
        // noop.
    }

    /**
     * {@inheritdoc}
     */
    public function vector(): iterable
    {
        try {
            while (false !== ($value = $this->result->fetchOne())) {
                yield $value;
            }
        } catch (DbalException|DbalDriverException $exception) {
            throw new DriverException(
                'An error occurred while fetching first value of next row using Doctrine Dbal database driver.',
                $exception,
            );
        } catch (\Exception $exception) { // @phpstan-ignore-line
            throw new RuntimeException(
                'An unexpected error occurred while fetching first value of next row using Doctrine Dbal database driver.',
                $exception,
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function free(): void
    {
        try {
            $this->result->free();
            unset($this->result);
        } catch (\Exception) {
            // noop.
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        try {
            while (false !== ($row = $this->result->fetchAssociative())) {
                yield $row;
            }
        } catch (DbalException|DbalDriverException $exception) {
            throw new DriverException(
                'An error occurred while fetching row as associative array using Doctrine Dbal database driver.',
                $exception,
            );
        } catch (\Exception $exception) { // @phpstan-ignore-line
            throw new RuntimeException(
                'An unexpected error occurred while fetching row as associative array using Doctrine Dbal database driver.',
                $exception,
            );
        }
    }
}
