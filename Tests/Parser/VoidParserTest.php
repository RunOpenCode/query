<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Tests\Parser;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RunOpenCode\Component\Query\Parser\VoidParser;

final class VoidParserTest extends TestCase
{
    private VoidParser $parser;
    
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new VoidParser();
    }

    /**
     * @param non-empty-string $query
     */
    #[Test]
    #[DataProvider('get_data_for_passes_through')]
    public function passes_through(string $query): void
    {
        $this->assertTrue($this->parser->supports($query));
        $this->assertSame($query, $this->parser->parse($query, []));
    }
    
    /**
     * @return iterable<string, array{non-empty-string}>
     */
    public static function get_data_for_passes_through(): iterable
    {
        yield 'Simple valid SQL query.' => [
            'SELECT * FROM users WHERE id = 1;',
        ];
        yield 'Any kind of string.' => [
            'foo bar baz',
        ];
    }
}