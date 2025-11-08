<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Parser;

use RunOpenCode\Component\Query\Contract\Parser\VariablesInterface;
use RunOpenCode\Component\Query\Exception\LogicException;
use RunOpenCode\Component\Query\Exception\OutOfBoundsException;

/**
 * Default implementation of bag of variables.
 *
 * Implementation allows mutation for the performance reasons.
 */
final class Variables implements VariablesInterface
{
    /**
     * Variables in variable bag.
     *
     * @var array<non-empty-string, mixed>
     */
    private array $variables = [];

    /**
     * Create instance of variables bag.
     *
     * @param non-empty-string|null                             $parser    Parser to be used with these variables.
     * @param VariablesInterface|array<non-empty-string, mixed> $variables Initial variables.
     */
    public function __construct(
        public readonly ?string  $parser = null,
        VariablesInterface|array $variables = [],
    ) {
        foreach ($variables as $name => $value) {
            $this->add($name, $value);
        }
    }

    /**
     * Create new variables bag for default parser.
     *
     * @param array<non-empty-string, mixed> $variables Variables to register.

     */
    public static function default(array $variables = []): self
    {
        return new self(null, $variables);
    }

    /**
     * Create new variable bag for void parser.
     */
    public static function void(): self
    {
        return new self(VoidParser::NAME);
    }

    /**
     * Create new variables bag for twig parser.
     *
     * @param array<non-empty-string, mixed> $variables Variables to register.
     */
    public static function twig(array $variables = []): self
    {
        return new self(TwigParser::NAME, $variables);
    }

    /**
     * Creates new variables bag from another bag or array of variables.
     *
     * @param non-empty-string|null                             $parser    Parser to be used with these variables.
     * @param VariablesInterface|array<non-empty-string, mixed> $variables Variables bag or array to create from.
     *
     * @return self
     */
    public static function create(?string $parser = null, VariablesInterface|array $variables = []): self
    {
        return new self($parser, $variables);
    }

    /**
     * {@inheritdoc}
     */
    public function add(string $name, mixed $value): VariablesInterface
    {
        if (\array_key_exists($name, $this->variables)) {
            throw new LogicException(\sprintf(
                'Cannot add variable "%s" to variables bag. Variable with the same name already exists.',
                $name,
            ));
        }

        $this->variables[$name] = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $name, mixed $value): VariablesInterface
    {
        $this->variables[$name] = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function remove(string $name): self
    {
        unset($this->variables[$name]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function merge(VariablesInterface|array $other, bool $overwrite = true): VariablesInterface
    {
        foreach ($other as $name => $value) {
            $method = $overwrite ? 'set' : 'add';
            $this->{$method}($name, $value);
        }

        return $this;
    }

    /**
     * Magic getter for variable.
     *
     * Throws exception if variable does not exist.
     *
     * @param non-empty-string $name Name of the variable.
     *
     * @return mixed
     *
     * @throws OutOfBoundsException If variable does not exist.
     */
    public function __get(string $name): mixed
    {
        return \array_key_exists($name, $this->variables) ? $this->variables[$name] : throw new OutOfBoundsException(\sprintf(
            'Variable "%s" does not exist in variables bag.',
            $name,
        ));
    }

    /**
     * Magic setter for variable.
     *
     * @param non-empty-string $name  Name of the variable.
     * @param mixed            $value Value of the variable.
     */
    public function __set(string $name, mixed $value): void
    {
        $this[$name] = $value;
    }

    /**
     * Checks if variable exists in the bag.
     *
     * @param non-empty-string $name Name of the variable.
     *
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return $this->offsetExists($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        yield from $this->variables;
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return \count($this->variables);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset): bool
    {
        return \array_key_exists($offset, $this->variables);
    }

    /**
     * {@inheritdoc}
     *
     * @throws OutOfBoundsException If variable does not exist.
     */
    public function offsetGet($offset): mixed
    {
        return \array_key_exists($offset, $this->variables) ? $this->variables[$offset] : throw new OutOfBoundsException(\sprintf(
            'Variable "%s" does not exist in variables bag.',
            $offset,
        ));
    }

    /**
     * {@inheritdoc}
     *
     * @param non-empty-string $offset
     */
    public function offsetSet($offset, $value): void
    {
        $this->variables[$offset] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset): void
    {
        unset($this->variables[$offset]);
    }
}
