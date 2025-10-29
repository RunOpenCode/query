<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Parser;

use RunOpenCode\Component\Query\Contract\Parser\ParserInterface;
use RunOpenCode\Component\Query\Exception\NotExistsException;
use RunOpenCode\Component\Query\Exception\RuntimeException;
use RunOpenCode\Component\Query\Exception\SyntaxException;
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
    /**
     * {@inheritdoc}
     */
    public string $name {
        get => 'twig';
    }

    public function __construct(
        private readonly Environment $twig
    ) {
        // noop
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $query): bool
    {
        [$template,] = $this->template($query);

        return $this->twig->getLoader()->exists($template);
    }

    /**
     * {@inheritdoc}
     */
    public function parse(string $query, array $variables): string
    {
        [$template, $block] = $this->template($query);

        try {
            $wrapper = $this->twig->load($template);
        } catch (LoaderError $exception) {
            throw new NotExistsException(\sprintf(
                'Could not find query source "%s" in any of known Twig templates.',
                $template,
            ), $exception);
        } catch (SyntaxError $exception) {
            throw new SyntaxException(\sprintf(
                'Query source "%s" contains Twig syntax error and could not be compiled.',
                $template,
            ), $exception);
        } catch (\Exception $exception) {
            throw new RuntimeException(\sprintf(
                'Unknown exception occurred during loading of query source "%s" from Twig template.',
                $template,
            ), $exception);
        }

        if (null !== $block && !$wrapper->hasBlock($block, $variables)) {
            throw new NotExistsException(\sprintf(
                'Block "%s" in query source "%s" provided in Twig template does not exists.',
                $block,
                $template,
            ));
        }

        try {
            $parsed = null !== $block ? $wrapper->renderBlock($block, $variables) : $wrapper->render($variables);
        } catch (\Exception $exception) {
            throw new RuntimeException(\sprintf(
                'Unknown exception occurred during rendering of query source "%s" contained in Twig template.',
                $query,
            ), $exception);
        }

        return \trim($parsed) ?: throw new RuntimeException(\sprintf(
            'Parsed query from Twit template "%s" yielded empty string.',
            $query
        ));
    }

    /**
     * Splits given query into template name and optional block name.
     *
     * @param string $query
     *
     * @return array{string, ?string}
     */
    private function template(string $query): array
    {
        if (\str_contains($query, '::')) {
            $parts = \explode('::', $query, 2);

            return 2 === \count($parts) ? $parts : [$query, null];
        }

        return [$query, null];
    }
}
