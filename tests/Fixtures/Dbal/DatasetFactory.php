<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Tests\Fixtures\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;

/**
 * Fills database with predefined schema and dataset.
 *
 * @internal
 */
final class DatasetFactory
{
    private static self $instance;

    /**
     * Fixtures represents predefined database schemas and datasets
     * which can be used to prepare database state for tests.
     *
     * Fixtures are indexed by table names, each table contains
     * schema definition and datasets which can be loaded into that table.
     */
    private const array FIXTURES = [
        'test'       => [
            'schema'   => [
                'columns' => [
                    'id'          => [Types::INTEGER, ['unsigned' => true]],
                    'title'       => [Types::STRING, ['length' => 32]],
                    'description' => [Types::STRING, ['length' => 255]],
                ],
                'primary' => ['id'],
            ],
            'datasets' => [
                'empty'   => [],
                'default' => [
                    ['id' => 1, 'title' => 'Title 1', 'description' => 'Description 1'],
                    ['id' => 2, 'title' => 'Title 2', 'description' => 'Description 2'],
                    ['id' => 3, 'title' => 'Title 3', 'description' => 'Description 3'],
                    ['id' => 4, 'title' => 'Title 4', 'description' => 'Description 4'],
                    ['id' => 5, 'title' => 'Title 5', 'description' => 'Description 5'],
                ],
            ],
        ],
        'conversion' => [
            'schema'   => [
                'columns' => [
                    'id'            => [Types::INTEGER, ['unsigned' => true]],
                    'text_value'    => [Types::STRING, ['length' => 255]],
                    'float_value'   => [Types::FLOAT, ['notnull' => false]],
                    'date_value'    => [Types::DATE_IMMUTABLE, ['notnull' => false]],
                    'boolean_value' => [Types::BOOLEAN, ['notnull' => false]],
                ],
                'primary' => ['id'],
                'cast'    => [
                    'date_value' => Types::DATE_IMMUTABLE,
                ],
            ],
            'datasets' => [
                'default' => [
                    ['id' => 1, 'text_value' => 'Text value 1', 'float_value' => 1.1, 'date_value' => '2005-10-11', 'boolean_value' => true],
                    ['id' => 2, 'text_value' => 'Text value 2', 'float_value' => 1.2, 'date_value' => '2005-10-12', 'boolean_value' => false],
                    ['id' => 3, 'text_value' => 'Text value 3', 'float_value' => 1.3, 'date_value' => '2005-10-13', 'boolean_value' => true],
                ],
            ],
        ],
    ];

    private function __construct()
    {
    }

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function prepare(Connection $connection, string $table, string $dataset): void
    {
        if (!isset(self::FIXTURES[$table])) {
            throw new \InvalidArgumentException(\sprintf(
                'Predefined table "%s" does not exist, available tables are "%s".',
                $dataset,
                \implode(', ', \array_keys(self::FIXTURES))
            ));
        }

        if (!isset(self::FIXTURES[$table]['datasets'][$dataset])) {
            throw new \InvalidArgumentException(\sprintf(
                'Predefined dataset "%s" for table "%s" does not exist, available datasets are "%s".',
                $dataset,
                $table,
                \implode(', ', \array_keys(self::FIXTURES[$table]['datasets']))
            ));
        }

        $schema     = self::FIXTURES[$table]['schema'];
        $statements = $this->getSchemaDdl($connection, $table, $schema['columns'], $schema['primary']);

        foreach ($statements as $statement) {
            $connection->executeStatement($statement);
        }

        $dataset = self::FIXTURES[$table]['datasets'][$dataset];
        $cast    = self::FIXTURES[$table]['schema']['cast'] ?? [];

        if (empty($dataset)) {
            return;
        }

        foreach ($dataset as $record) {
            $keys   = \array_keys($record);
            $values = \array_map(
                fn(string $column, mixed $value): mixed => isset($cast[$column]) ? $this->cast($value, $cast[$column]) : $value,
                $keys,
                \array_values($record)
            );

            $connection->insert(
                $table,
                \array_combine($keys, $values),
                \array_map(static fn(array $definition): string => $definition[0], $schema['columns'])
            );
        }
    }

    /**
     * Get schema DDL statements for given table definition.
     *
     * @param Connection                                                                       $connection Database connection.
     * @param non-empty-string                                                                 $name       Table name.
     * @param array<non-empty-string, array{non-empty-string, array<non-empty-string, mixed>}> $columns    Table columns definition.
     * @param list<non-empty-string>                                                           $primary    Table primary key columns.
     *
     * @return non-empty-list<string> SQL statements to create table schema
     */
    private function getSchemaDdl(Connection $connection, string $name, array $columns, array $primary): array
    {
        $schema = new Schema();
        $table  = $schema->createTable($name);

        foreach ($columns as $column => [$type, $options]) {
            $table->addColumn($column, $type, $options);
        }

        if (!empty($primary)) {
            $table->addPrimaryKeyConstraint(new PrimaryKeyConstraint(
                null,
                \array_map(static fn(string $name): UnqualifiedName => UnqualifiedName::unquoted($name), $primary),
                true,
            ));
        }

        return [
            \sprintf('DROP TABLE IF EXISTS %s;', $name),
            ...$schema->toSql($connection->getDatabasePlatform()),
        ];
    }

    private function cast(mixed $value, string $type): mixed
    {
        if (null === $value) {
            return null;
        }

        return match ($type) {
            Types::DATE_IMMUTABLE => new \DateTimeImmutable($value),
            default => throw new \InvalidArgumentException(\sprintf(
                'Unsupported type "%s" for value type "%s" provided.',
                $type,
                \get_debug_type($value),
            ))
        };
    }

}
