<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Parser;

use RunOpenCode\Component\Query\Contract\Parser\ParserInterface;
use RunOpenCode\Component\Query\Contract\Parser\VariablesInterface;

/**
 * Void parser does not perform any parsing at all.
 *
 * It assumes that provided query is ready to be executed as is.
 */
final class VoidParser implements ParserInterface
{
    public const string NAME = 'void';

    /**
     * {@inheritdoc}
     */
    public string $name {
        get => self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $query): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function parse(string $query, VariablesInterface $variables): string
    {
        return $query;
    }
}
