<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Contract\Parser;

/**
 * Query/statement parsers.
 *
 * Parsers are responsible for parsing queries/statements before they are executed.
 *
 * Parsing can be omitted, if raw query/statement is to be executed as is. However,
 * parsing language may be used to provide additional features, like variable
 * substitution, conditional query/statement parts, loops, includes and so on.
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
     * @param non-empty-string $source Query, statement or reference to a query or statement to check.
     *
     * @return bool TRUE if parser supports given query/statement, FALSE otherwise.
     */
    public function supports(string $source): bool;

    /**
     * Parse given query/statement with provided variables.
     *
     * @param non-empty-string   $source    Query, statement or reference to a query or statement to parse.
     * @param VariablesInterface $variables Variables to use during parsing.
     *
     * @return non-empty-string Parsed query.
     */
    public function parse(string $source, VariablesInterface $variables): string;
}
