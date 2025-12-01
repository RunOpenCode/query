<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Parser;

use RunOpenCode\Component\Query\Contract\Parser\ParserInterface;
use RunOpenCode\Component\Query\Contract\Parser\VariablesInterface;
use RunOpenCode\Component\Query\Exception\NotExistsException;

/**
 * Registry of available parsers.
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
     * @param non-empty-string   $query     Query or reference to a query to parse.
     * @param VariablesInterface $variables Variables to use during parsing.
     *
     * @return non-empty-string Parsed query.
     */
    public function parse(string $query, VariablesInterface $variables): string
    {
        if (null !== $variables->parser) {
            return ($this->registry[$variables->parser] ?? null)?->parse($query, $variables) ?? throw new NotExistsException(\sprintf(
                'Parser with name "%s" does not exist.',
                $variables->parser
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
