<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Parser;

use RunOpenCode\Component\Query\Contract\Parser\ParserInterface;
use RunOpenCode\Component\Query\Contract\Parser\VariablesInterface;
use RunOpenCode\Component\Query\Exception\InvalidArgumentException;
use RunOpenCode\Component\Query\Exception\NotExistsException;
use RunOpenCode\Component\Query\Exception\RuntimeException;
use RunOpenCode\Component\Query\Exception\UnsupportedException;
use Twig\Loader\FilesystemLoader;

/**
 * File query parser.
 *
 * File query parser does not support variables, however, allows you to
 * store your queries in file. It uses same syntax as Twig, so you access
 * them either from global namespace or using namespace alias, i.e:
 * `@foo/bar/query.sql` SQL file.
 */
final class FileParser implements ParserInterface
{
    public const string NAME = 'file';

    public const string MAIN_NAMESPACE = FilesystemLoader::MAIN_NAMESPACE;

    /**
     * {@inheritdoc}
     */
    public string $name {
        get => self::NAME;
    }

    /**
     * List of paths, indexed by namespace.
     *
     * @var array<non-empty-string, non-empty-list<non-empty-string>>
     */
    private readonly array $paths;

    /**
     * @param list<array{non-empty-string, non-empty-string}> $paths
     * @param non-empty-list<non-empty-string>                $patterns
     */
    public function __construct(
        array                  $paths = [],
        private readonly array $patterns = ['/^.*\.sql$/', '/^.*\.dql$/']
    ) {
        $resolved = [];

        foreach ($paths as [$path, $namespace]) {
            $resolved[$namespace]   = $resolved[$namespace] ?? [];
            $resolved[$namespace][] = \rtrim($path, '/');
        }

        $this->paths = $resolved; // @phpstan-ignore-line
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $source): bool
    {
        return \array_any(
            $this->patterns,
            static fn($pattern): bool => 1 === \Safe\preg_match($pattern, $source)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function parse(string $source, VariablesInterface $variables): string
    {
        \assert($this->supports($source), new InvalidArgumentException(\sprintf(
            'Provided file "%s" is not supported by "%s".',
            $source,
            self::class
        )));

        if ($variables instanceof ContextAwareVariables && ($variables->variables?->count() ?? 0) > 0) {
            throw new UnsupportedException(\sprintf(
                'File parser can not utilize variables, number of variables provided: %d.',
                $variables->variables->count(), // @phpstan-ignore-line
            ));
        }

        $path = $this->path($source);

        if (null === $path) {
            throw new NotExistsException(\sprintf(
                'File parser could not find provided source "%s".',
                $source,
            ));
        }

        return \file_get_contents($path) ?: throw new RuntimeException(\sprintf(
            'File parser could not load provided source "%s" (resolved at path "%s").',
            $source,
            $path,
        ));
    }

    /**
     * @param non-empty-string $source
     *
     * @return non-empty-string|null
     */
    private function path(string $source): ?string
    {
        [$namespace, $path] = $this->extract($source);
        $directories = $this->paths[$namespace] ?? null;

        if (null === $directories) {
            return null;
        }

        foreach ($directories as $directory) {
            $file = \sprintf('%s/%s', $directory, $path);

            if (\file_exists($file)) {
                return $file;
            }
        }

        return null;
    }

    /**
     * @param non-empty-string $source
     *
     * @return array{non-empty-string, non-empty-string}
     */
    private function extract(string $source): array
    {
        if ('@' !== $source[0]) {
            return [self::MAIN_NAMESPACE, $source];
        }

        $position = \strpos($source, '/');

        if (false === $position) {
            throw new InvalidArgumentException(\sprintf(
                'Malformed namespaced file name "%s" (expecting "@namespace/path").',
                $source,
            ));
        }

        $namespace = \substr($source, 1, $position - 1);
        $path      = \substr($source, $position + 1);

        /**
         * @var non-empty-string $namespace
         * @var non-empty-string $path
         */
        return [$namespace, $path];
    }
}
