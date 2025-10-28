<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Executor;

use RunOpenCode\Component\Query\Contract\Executor\TransactionInterface;

final readonly class TransactionStack
{
    /**
     * Size of transaction stack.
     * 
     * @var int 
     */
    public int $size;
    
    /**
     * @param array<non-empty-list<TransactionInterface>> $transactions
     */
    public function __construct(public array $transactions)
    {
        $this->size = \count($this->transactions);
    }

    /**
     * Add transaction scope to transaction stack.
     * 
     * @param non-empty-list<TransactionInterface> $configurations
     *
     * @return self
     */
    public function push(array $configurations): TransactionStack
    {
        return new self([
            $configurations,
            ...$this->transactions,
        ]);
    }
    
    /**
     * Initialize transaction stack.
     * 
     * @param non-empty-list<TransactionInterface> $configurations
     *
     * @return self
     */
    public static function create(array $configurations): self
    {
        return new self([$configurations]);
    }
}