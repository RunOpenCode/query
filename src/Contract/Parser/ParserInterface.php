<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Contract\Parser;

/**
 * Query parsers.
 *
 * Parsers are responsible for parsing queries before they are executed.
 *
 * Parsing can be omitted, if raw query is to be executed as is. However,
 * parsing language may be used to provide additional features, like variable
 * substitution, conditional query parts, loops, includes and so on.
 */
interface ParserInterface
{
    /**
     * Gets unique parser name.
     *
     * Each parser must have unique name that can be used to reference it. User
     * may explicitly select parser by its name when loading queries.
     *
     * @var non-empty-string
     */
    public string $name {
        get;
    }

    /**
     * Determines if parser supports given query.
     *
     * @param non-empty-string $query Query or reference to a query to check.
     *
     * @return bool TRUE if parser supports given query, FALSE otherwise.
     */
    public function supports(string $query): bool;

    /**
     * Parse given query with provided variables.
     *
     * @param non-empty-string     $query     Query or reference to a query to parse.
     * @param array<string, mixed> $variables Variables to use during parsing.
     *
     * @return non-empty-string Parsed query.
     */
    public function parse(string $query, array $variables): string;
}
