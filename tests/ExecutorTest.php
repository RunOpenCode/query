<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RunOpenCode\Component\Query\Cache\CacheMiddleware;
use RunOpenCode\Component\Query\Contract\Executor\AffectedInterface;
use RunOpenCode\Component\Query\Contract\Executor\ResultInterface;
use RunOpenCode\Component\Query\Contract\ExecutorInterface;
use RunOpenCode\Component\Query\Doctrine\Dbal\Adapter;
use RunOpenCode\Component\Query\Doctrine\Parameters\Named;
use RunOpenCode\Component\Query\Doctrine\Parameters\Positional;
use RunOpenCode\Component\Query\Exception\LogicException;
use RunOpenCode\Component\Query\Executor;
use RunOpenCode\Component\Query\Middleware\MiddlewareChain;
use RunOpenCode\Component\Query\Parser\ParserMiddleware;
use RunOpenCode\Component\Query\Parser\ParserRegistry;
use RunOpenCode\Component\Query\Parser\TwigParser;
use RunOpenCode\Component\Query\Parser\VoidParser;
use RunOpenCode\Component\Query\Replica\Replica;
use RunOpenCode\Component\Query\Replica\ReplicaMiddleware;
use RunOpenCode\Component\Query\Tests\Fixtures\Dbal\MySqlDatabase;
use RunOpenCode\Component\Query\Tests\Fixtures\TwigFactory;
use RunOpenCode\Component\Query\Tests\PHPUnit\DbalTools;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class ExecutorTest extends TestCase
{
    use DbalTools;

    private Executor $executor;

    protected function setUp(): void
    {
        parent::setUp();
        $adapters       = new Executor\AdapterRegistry([
            new Adapter('foo', $this->createMySqlConnection(MySqlDatabase::Foo)),
            new Adapter('bar', $this->createMySqlConnection(MySqlDatabase::Bar)),
            new Adapter('baz', $this->createSqlLiteConnection(dataset: 'empty')),
        ]);
        $this->executor = new Executor(new MiddlewareChain([
            new CacheMiddleware(new ArrayAdapter()),
            new ParserMiddleware(new ParserRegistry([
                new TwigParser(TwigFactory::create()),
                new VoidParser(),
            ])),
            new ReplicaMiddleware(
                'foo',
                ['baz'],
                $adapters,
            ),
            new Executor\ExecutorMiddleware($adapters),
        ]), $adapters);
    }

    #[Test]
    public function query(): void
    {
        $result = $this->executor->query('default_dataset/filter.sql.twig', new Named()->integer('id', 1));

        $this->assertSame([
            'id'          => 1,
            'title'       => 'Title 1',
            'description' => 'Description 1',
        ], $result->getRecord()); // @phpstan-ignore-line
    }

    #[Test]
    public function query_inside_transaction(): void
    {
        $result = $this->executor->transactional(static function(ExecutorInterface $executor): ResultInterface {
            return $executor->query('default_dataset/filter.sql.twig', new Named()->integer('id', 1));
        });

        $this->assertSame([
            'id'          => 1,
            'title'       => 'Title 1',
            'description' => 'Description 1',
        ], $result->getRecord()); // @phpstan-ignore-line
    }

    #[Test]
    public function query_outside_transaction_throws_exception(): void
    {
        $this->expectException(LogicException::class);

        $this->executor->transactional(function(): void {
            $this->executor->query('default_dataset/filter.sql.twig');
        });
    }

    #[Test]
    public function statement(): void
    {
        $affected = $this->executor->statement(
            'default_dataset/insert.sql.twig',
            new Positional()
                ->integer(42)
                ->string('foo')
                ->string('bar')
        );

        $this->assertCount(1, $affected);
    }

    #[Test]
    public function statement_inside_transaction(): void
    {
        $affected = $this->executor->transactional(static function(ExecutorInterface $executor): AffectedInterface {
            return $executor->statement(
                'default_dataset/insert.sql.twig',
                new Positional()
                    ->integer(42)
                    ->string('foo')
                    ->string('bar')
            );
        });

        $this->assertCount(1, $affected);
    }

    #[Test]
    public function statement_outside_transaction_throws_exception(): void
    {
        $this->expectException(LogicException::class);

        $this->executor->transactional(function(): AffectedInterface {
            return $this->executor->statement(
                'default_dataset/insert.sql.twig',
                new Positional()
                    ->integer(42)
                    ->string('foo')
                    ->string('bar')
            );
        });
    }

    #[Test]
    public function nested_transactions(): void
    {
        $result = $this->executor->transactional(static function(ExecutorInterface $executor): ResultInterface {
            $executor->transactional(static function(ExecutorInterface $executor): void {
                $executor->statement(
                    'default_dataset/insert.sql.twig',
                    new Positional()
                        ->integer(42)
                        ->string('foo')
                        ->string('bar')
                );
            });

            return $executor->query('default_dataset/filter.sql.twig', new Named()->integer('id', 42));
        });

        $this->assertSame([
            'id'          => 42,
            'title'       => 'foo',
            'description' => 'bar',
        ], $result->getRecord()); // @phpstan-ignore-line
    }

    #[Test]
    public function transaction_outside_transaction_throws_exception(): void
    {
        $this->expectException(LogicException::class);

        $this->executor->transactional(function(): int {
            return $this->executor->transactional(static fn(): int => 1);
        });
    }

    #[Test]
    public function fetch_from_replica(): void
    {
        $result = $this->executor->query(
            'default_dataset/filter.sql.twig',
            new Named()->integer('id', 1),
            new Replica(),
        );

        $this->assertNull($result->getRecord(null));
    }

    #[Test]
    public function fetch_from_replica_within_transaction_throws_exception(): void
    {
        $this->expectException(LogicException::class);

        $this->executor->transactional(function(ExecutorInterface $executor): ResultInterface {
            return $executor->query(
                'default_dataset/filter.sql.twig',
                new Named()->integer('id', 1),
                new Replica(),
            );
        });
    }
}
