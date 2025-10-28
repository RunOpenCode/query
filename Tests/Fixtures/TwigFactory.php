<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Tests\Fixtures;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final readonly class TwigFactory
{
    public static function create(): Environment
    {
        $loader = new FilesystemLoader([
            __DIR__ . '/query',
        ]);
        
        return new Environment($loader);
    }
}