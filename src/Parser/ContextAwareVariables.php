<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Parser;

use RunOpenCode\Component\Query\Contract\Executor\ParametersInterface;
use RunOpenCode\Component\Query\Contract\Context\ContextInterface;
use RunOpenCode\Component\Query\Contract\Parser\VariablesInterface;
use RunOpenCode\Component\Query\Exception\LogicException;
use RunOpenCode\Component\Query\Exception\OutOfBoundsException;

/**
 * A variable bag which consist of parameters, variables and middleware context.
 *
 * This is internal class, holding all available values which may be used for query parsing. We assume
 * that developer will most likely use parameters for query parsing in parsing language, so this class
 * combines all bags together to improve developer experience.
 *
 * However, since collision between parameters name and variables name may occur, do note that variables
 * from variable bag takes precedence.
 *
 * Special 3 variables will be additionally provided:
 *
 * - `variables`: Containing reference to original variables bag.
 * - `parameters`: Containing reference to original parameters bag.
 * - `context`: Containing reference execution context.
 *
 * That means that variables and parameters with name `variables`, `parameters` and `context` will not
 * be available directly.
 *
 * @internal
 */
final readonly class ContextAwareVariables implements VariablesInterface
{
    /**
     * {@inheritdoc}
     */
    public null|string $parser;

    /**
     * @var array<non-empty-string, mixed>
     */
    private array $bag;

    /**
     * Create frozen, context aware variable bag.
     *
     * @param \RunOpenCode\Component\Query\Contract\Context\ContextInterface         $context    Execution context.
     * @param VariablesInterface|null  $variables  Variables to use for query/statement parsing.
     * @param ParametersInterface|null $parameters Parameters to use for query/statement parsing.
     */
    public function __construct(
        public ContextInterface     $context,
        public ?VariablesInterface  $variables,
        public ?ParametersInterface $parameters,
    ) {
        /** @var array<non-empty-string, mixed> $params */
        $params       = null !== $parameters?->values && !\array_is_list($parameters->values) ? $parameters->values : [];
        $this->parser = $variables?->parser;
        $this->bag    = \array_merge(
            $params,
            $variables ? \iterator_to_array($variables) : [],
            [
                'variables'  => $variables,
                'parameters' => $parameters,
                'context'    => $context,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function add(string $name, mixed $value): never
    {
        throw new LogicException(\sprintf(
            'This implementation of "%s" is frozen for mutations.',
            VariablesInterface::class,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $name, mixed $value): VariablesInterface
    {
        throw new LogicException(\sprintf(
            'This implementation of "%s" is frozen for mutations.',
            VariablesInterface::class,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function remove(string $name): VariablesInterface
    {
        throw new LogicException(\sprintf(
            'This implementation of "%s" is frozen for mutations.',
            VariablesInterface::class,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function merge(VariablesInterface|array $other, bool $overwrite = true): VariablesInterface
    {
        throw new LogicException(\sprintf(
            'This implementation of "%s" is frozen for mutations.',
            VariablesInterface::class,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        yield from $this->bag;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists(mixed $offset): bool
    {
        return \array_key_exists($offset, $this->bag);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet(mixed $offset): mixed
    {
        return \array_key_exists($offset, $this->bag) ? $this->bag[$offset] : throw new OutOfBoundsException(\sprintf(
            'Variable "%s" does not exist in variables bag.',
            $offset,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet(mixed $offset, mixed $value): never
    {
        throw new LogicException(\sprintf(
            'This implementation of "%s" is frozen for mutations.',
            VariablesInterface::class,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset(mixed $offset): never
    {
        throw new LogicException(\sprintf(
            'This implementation of "%s" is frozen for mutations.',
            VariablesInterface::class,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return \count($this->bag);
    }
}
