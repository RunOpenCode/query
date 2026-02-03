<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Tests\Functions;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function RunOpenCode\Component\Query\to_regex;

final class ToRegexTest extends TestCase
{
    /**
     * @param non-empty-string $glob
     */
    #[Test]
    #[DataProvider('get_data_for_glob_matches')]
    public function glob_matches(string $glob, string $path, bool $expected): void
    {
        $this->assertSame($expected ? 1 : 0, \preg_match(to_regex($glob), $path));
    }

    /**
     * @return iterable<non-empty-string, array{non-empty-string, string, bool}>
     */
    public static function get_data_for_glob_matches(): iterable
    {
        yield '*.twig, @foo/bar.sql.twig' => ['*.twig', '@foo/bar.sql.twig', false];
        yield '*.twig, foo.sql.twig' => ['*.twig', 'foo.sql.twig', true];
        yield '**/*.twig, @foo/bar.sql.twig' => ['**/*.twig', '@foo/bar.sql.twig', true];
    }
}
