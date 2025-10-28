<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Parser;

use RunOpenCode\Component\Query\Contract\Parser\ParserInterface;
use RunOpenCode\Component\Query\Exception\NotExistsException;

/**
 * Registry of available parsers.
 *
 * @internal
 */
final readonly class ParserRegistry
{
    /**
     * @var array<non-empty-string, ParserInterface>
     */
    private array $registry;

    /**
     * @param iterable<ParserInterface> $parsers
     */
    public function __construct(iterable $parsers)
    {
        $registry = [];

        foreach ($parsers as $parser) {
            $registry[$parser->name] = $parser;
        }

        $this->registry = $registry;
    }

    /**
     * Parse given query with provided variables using specified parser or
     * auto-detecting parser that supports given query.
     *
     * @param non-empty-string      $query     Query or reference to a query to parse.
     * @param array<string, mixed>  $variables Variables to use during parsing.
     * @param non-empty-string|null $parser    Optional parser name to use for parsing.
     *
     * @return string Parsed query.
     */
    public function parse(string $query, array $variables, ?string $parser = null): string
    {
        if (null !== $parser) {
            return ($this->registry[$parser] ?? null)?->parse($query, $variables) ?? throw new NotExistsException(\sprintf(
                'Parser with name "%s" does not exist.',
                $parser
            ));
        }

        foreach ($this->registry as $current) {
            if (!$current->supports($query)) {
                continue;
            }

            return $current->parse($query, $variables);
        }

        throw new NotExistsException(\sprintf(
            'Could not find parser that supports given query source "%s".',
            $query
        ));
    }
}
