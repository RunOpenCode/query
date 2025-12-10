<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Parser;

use RunOpenCode\Component\Query\Contract\Parser\ParserInterface;
use RunOpenCode\Component\Query\Contract\Parser\VariablesInterface;
use RunOpenCode\Component\Query\Exception\InvalidArgumentException;
use RunOpenCode\Component\Query\Exception\NotExistsException;
use RunOpenCode\Component\Query\Exception\ParserSyntaxException;
use RunOpenCode\Component\Query\Exception\RuntimeException;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;

/**
 * Twig query parser.
 *
 * This parser uses Twig templating engine to parse query sources.
 *
 * Parser supports queries that are written in one single Twig template file,
 * or, multiple queries written within blocks inside a single Twig template file.
 *
 * For example, given a Twig template file `@foo/bar/query.sql.twig` will render
 * the whole content of the file as a query, while `@foo/bar/query.sql.twig::block_name`
 * will render only the content of the `block_name` block defined inside the
 * `@foo/bar/query.sql.twig` template file.
 */
final class TwigParser implements ParserInterface
{
    public const string NAME = 'twig';

    /**
     * {@inheritdoc}
     */
    public string $name {
        get => self::NAME;
    }

    /**
     * Create Twig parser.
     *
     * @param Environment            $twig Twig environment to use.
     * @param list<non-empty-string> $patterns File patterns to support.
     */
    public function __construct(
        private readonly Environment $twig,
        private readonly array       $patterns = ['/^.*\.twig$/'],
    ) {
        // noop
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $source): bool
    {
        [$template,] = $this->template($source);

        return \array_any(
            $this->patterns,
            static fn(string $pattern): bool => 1 === \Safe\preg_match($pattern, $template)
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

        [$template, $block] = $this->template($source);
        $context = \iterator_to_array($variables);

        try {
            $wrapper = $this->twig->load($template);
        } catch (LoaderError $exception) {
            throw new NotExistsException(\sprintf(
                'Could not find source "%s" in any of known Twig templates.',
                $template,
            ), $exception);
        } catch (SyntaxError $exception) {
            throw new ParserSyntaxException(\sprintf(
                'Source "%s" contains Twig syntax error and could not be compiled.',
                $template,
            ), $exception);
        } catch (\Exception $exception) {
            throw new RuntimeException(\sprintf(
                'Unknown exception occurred during loading of source "%s" from Twig template.',
                $template,
            ), $exception);
        }

        if (null !== $block && !$wrapper->hasBlock($block, $context)) {
            throw new NotExistsException(\sprintf(
                'Block "%s" in source "%s" provided in Twig template does not exists.',
                $block,
                $template,
            ));
        }

        try {
            $parsed = null !== $block ? $wrapper->renderBlock($block, $context) : $wrapper->render($context);
        } catch (\Exception $exception) {
            throw new RuntimeException(\sprintf(
                'Unknown exception occurred during rendering of source "%s" contained in Twig template.',
                $source,
            ), $exception);
        }

        return \trim($parsed) ?: throw new RuntimeException(\sprintf(
            'Parsed source from Twit template "%s" yielded empty string.',
            $source
        ));
    }

    /**
     * Splits given query into template name and optional block name.
     *
     *
     * @return array{string, ?string}
     */
    private function template(string $source): array
    {
        if (\str_contains($source, '::')) {
            $parts = \explode('::', $source, 2);

            return 2 === \count($parts) ? $parts : [$source, null];
        }

        return [$source, null];
    }
}
