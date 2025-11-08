<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Tests\Cache;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RunOpenCode\Component\Query\Cache\Invalidate;

final class InvalidateTest extends TestCase
{
    /**
     * @param string[]|string|null $keys
     * @param string[]|string|null $tags
     * @param list<string>         $expectedKeys
     * @param list<string>         $expectedTags
     */
    #[Test]
    #[DataProvider('get_data_for_sanitizes_input')]
    public function sanitizes_input(array|string|null $keys, array|string|null $tags, array $expectedKeys, array $expectedTags): void
    {
        $invalidate = new Invalidate($keys, $tags);

        $this->assertSame($expectedKeys, $invalidate->keys);
        $this->assertSame($expectedTags, $invalidate->tags);
    }

    /**
     * @return iterable<string, array{
     *     string[]|string|null,
     *     string[]|string|null,
     *     list<string>,
     *     list<string>,
     * }>
     */
    public static function get_data_for_sanitizes_input(): iterable
    {
        yield 'Sanitizes nulls.' => [null, null, [], []];
        yield 'Sanitizes strings.' => ['key1', 'tag1', ['key1'], ['tag1']];
        yield 'Passes through arrays.' => [['key1', 'key2'], ['tag1', 'tag2'], ['key1', 'key2'], ['tag1', 'tag2']];
    }

    #[Test]
    public function keys(): void
    {
        $this->assertSame(
            ['key1', 'key2'],
            Invalidate::keys(['key1', 'key2'])->keys
        );
    }

    #[Test]
    public function tags(): void
    {
        $this->assertSame(
            ['tag1', 'tag2'],
            Invalidate::tags(['tag1', 'tag2'])->tags
        );
    }
}
