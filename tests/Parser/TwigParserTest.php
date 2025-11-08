<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Tests\Parser;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RunOpenCode\Component\Query\Exception\NotExistsException;
use RunOpenCode\Component\Query\Exception\RuntimeException;
use RunOpenCode\Component\Query\Exception\SyntaxException;
use RunOpenCode\Component\Query\Parser\TwigParser;
use RunOpenCode\Component\Query\Parser\Variables;
use RunOpenCode\Component\Query\Tests\Fixtures\TwigFactory;

final class TwigParserTest extends TestCase
{
    private TwigParser $parser;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new TwigParser(TwigFactory::create());
    }

    /**
     * @param non-empty-string $query
     */
    #[Test]
    #[DataProvider('get_data_for_supports')]
    public function supports(string $query, bool $expected): void
    {
        $this->assertSame($expected, $this->parser->supports($query));
    }

    /**
     * @return iterable<string, array{non-empty-string, bool}>
     */
    public static function get_data_for_supports(): iterable
    {
        yield 'Query in template.' => ['all_users.sql.twig', true];
        yield 'Query in template block.' => ['template_with_blocks.sql.twig::get_all_users', true];
        yield 'Query does not exists.' => ['foo', false];
    }

    /**
     * @param non-empty-string     $query
     * @param array<non-empty-string, mixed> $variables
     */
    #[Test]
    #[DataProvider('get_data_for_parses')]
    public function parses(string $query, array $variables, string $expected): void
    {
        $this->assertSame($expected, $this->parser->parse($query, Variables::twig($variables)));
    }

    /**
     * @return iterable<string, array{non-empty-string, array<string, mixed>, non-empty-string}>
     */
    public static function get_data_for_parses(): iterable
    {
        yield 'Using template, without variables.' => ['all_users.sql.twig', [], "SELECT * FROM users;"];
        yield 'Using template block, without variables, targeting all users query.' => ['template_with_blocks.sql.twig::get_all_users', [], 'SELECT * FROM users;'];
        yield 'Using template block, without variables, targeting get user by id query.' => ['template_with_blocks.sql.twig::get_user_by_id', [], 'SELECT * FROM users WHERE id = :id;'];
        yield 'Using template that utilises variables, without variables.' => ['all_users_or_one.sql.twig', [], 'SELECT * FROM users;'];
        yield 'Using template that utilises variables, with variables.' => ['all_users_or_one.sql.twig', ['id' => 42], 'SELECT * FROM users WHERE id = :id;'];
    }

    #[Test]
    public function parse_throws_exception_when_template_does_not_exists(): void
    {
        $this->expectException(NotExistsException::class);

        $this->parser->parse('foo', Variables::twig());
    }

    #[Test]
    public function parse_throws_exception_when_template_block_does_not_exists(): void
    {
        $this->expectException(NotExistsException::class);

        $this->parser->parse('all_users.sql.twig::foo', Variables::twig());
    }

    #[Test]
    public function parse_throws_exception_when_template_has_syntax_error(): void
    {
        $this->expectException(SyntaxException::class);

        $this->parser->parse('syntax_error.sql.twig', Variables::twig());
    }

    #[Test]
    public function parse_throws_runtime_exception_on_rendering_error(): void
    {
        $this->expectException(RuntimeException::class);

        $this->parser->parse('render_error.sql.twig', Variables::twig());
    }
}
