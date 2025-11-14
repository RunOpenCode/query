<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query;

/**
 * @internal
 */
function to_date_time_immutable(?\DateTimeInterface $date): ?\DateTimeImmutable
{
    if (null === $date) {
        return null;
    }
    
    if ($date instanceof \DateTimeImmutable) {
        return $date;
    }
    
    return \DateTimeImmutable::createFromInterface($date);
}

/**
 * @internal 
 */
function enum_value(?\UnitEnum $value): int|string|null
{
    if (null === $value) {
        return null;
    }

    $reflection = new \ReflectionEnum($value::class);
    
    // @phpstan-ignore-next-line
    return $reflection->isBacked() ? $value->value : $value->name;
}