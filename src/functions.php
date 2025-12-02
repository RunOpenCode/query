<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query;

use Symfony\Component\Finder\Glob;

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

function enum_value(?\UnitEnum $value): int|string|null
{
    if (null === $value) {
        return null;
    }

    $reflection = new \ReflectionEnum($value::class);

    // @phpstan-ignore-next-line
    return $reflection->isBacked() ? $value->value : $value->name;
}

/**
 * Check if string is regex.
 *
 * @param non-empty-string $str String to check for.
 *
 * @return bool TRUE if string is regex.
 */
function is_regex(string $str): bool
{
    $availableModifiers = 'imsxuADUn';

    if (preg_match('/^(.{3,}?)[' . $availableModifiers . ']*$/', $str, $m)) {
        $start = \substr($m[1], 0, 1);
        $end   = \substr($m[1], -1);

        if ($start === $end) {
            return !\preg_match('/[*?[:alnum:] \\\\]/', $start);
        }

        foreach ([['{', '}'], ['(', ')'], ['[', ']'], ['<', '>']] as $delimiters) {
            if ($start === $delimiters[0] && $end === $delimiters[1]) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Converts glob expression to regex.
 * 
 * If regex is provided, it will return same value.
 * 
 * @param non-empty-string $str Glob or regex expression.
 *
 * @return non-empty-string Regex.
 */
function to_regex(string $str): string
{
    // @phpstan-ignore-next-line
    return is_regex($str) ? $str : Glob::toRegex($str);
}
