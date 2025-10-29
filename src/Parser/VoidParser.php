<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Parser;

use RunOpenCode\Component\Query\Contract\Parser\ParserInterface;

/**
 * Void parser does not perform any parsing at all.
 *
 * It assumes that provided query is ready to be executed as is.
 */
final class VoidParser implements ParserInterface
{
    /**
     * {@inheritdoc}
     */
    public string $name {
        get => 'void';
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
    public function parse(string $query, array $variables): string
    {
        return $query;
    }
}
