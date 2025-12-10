<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query;

use RunOpenCode\Component\Query\Contract\Executor\ResultInterface;
use RunOpenCode\Component\Query\Exception\InvalidArgumentException;
use RunOpenCode\Component\Query\Exception\ResultClosedException;
use Symfony\Component\Finder\Glob;

/**
 * Assert that at most one default value is provided.
 *
 * This function is utilised for methods:
 *
 * - {@see ResultInterface::scalar()}
 * - {@see ResultInterface::vector()}
 * - {@see ResultInterface::record()}
 *
 * when asserting that if default value is passed to invocation,
 * it is a single value, and it is not passed with name.
 */
function assert_default_value(mixed ...$default): void
{
    if (\count($default) > 1) {
        throw new InvalidArgumentException(\sprintf(
            'Expected at most one default value when invoking method "%s" of result set, %d given.',
            \debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS)[1]['function'],
            \count($default),
        ));
    }

    if (!\array_is_list($default)) {
        throw new InvalidArgumentException(\sprintf(
            'Expected default value to be provided without naming argument when invoking method "%s" of result set, "%s" given.',
            \debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS)[1]['function'],
            \array_keys($default)[0],
        ));
    }
}

/**
 * Assert that result set is open.
 *
 * You may utilise these functions in implementation of {@see ResultInterface}.
 *
 * @param ResultInterface $result Result set being checked.
 */
function assert_result_open(ResultInterface $result): void
{
    if (!$result->closed) {
        return;
    }

    throw new ResultClosedException(\sprintf(
        'Can not call method "%s" on closed result set.',
        \debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS)[1]['function']
    ));
}

/**
 * Cast instance of {@see \DateTimeInterface} to {@see \DateTimeImmutable}.
 *
 * @param \DateTimeInterface|null $date Date to cast.
 */
function to_date_time_immutable(?\DateTimeInterface $date): ?\DateTimeImmutable
{
    if (!$date instanceof \DateTimeInterface) {
        return null;
    }

    if ($date instanceof \DateTimeImmutable) {
        return $date;
    }

    return \DateTimeImmutable::createFromInterface($date);
}

/**
 * Extract value from enumeration suitable for persistence layer.
 *
 * When using enumerations in queries/statements, their values must be extracted to be
 * used as parameters. For string and integer backed enums, a value is extracted. Otherwise,
 * name of the case is used.
 *
 * @param \UnitEnum|null $value Enumeration case to cast to int or string, or NULL, if not available.
 *
 * @return int|string|null Extracted enumeration value, or NULL, if NULL is provided.
 */
function enum_value(?\UnitEnum $value): int|string|null
{
    if (!$value instanceof \UnitEnum) {
        return null;
    }

    $reflection = new \ReflectionEnum($value::class);

    // @phpstan-ignore-next-line
    return $reflection->isBacked() ? $value->value : $value->name;
}

/**
 * Converts glob expression to regex.
 *
 * If regex is provided, it will return same value.
 *
 * This function is useful for integration of this library into
 * framework library and/or project. When configuring File/Twig
 * parsers, instead of supporting only regex expressions for file
 * extensions, you may use this function to cast glob expression
 * to regex.
 *
 * In general, this function allows support both glob and regex
 * patterns.
 *
 * @param non-empty-string $str Glob or regex expression.
 *
 * @return non-empty-string Regex.
 */
function to_regex(string $str): string
{
    static $isRegex;

    if (!isset($isRegex)) {
        $isRegex = static function(string $str): bool {
            $availableModifiers = 'imsxuADUn';

            if (\preg_match('/^(.{3,}?)[' . $availableModifiers . ']*$/', $str, $m)) {
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
        };
    }

    // @phpstan-ignore-next-line
    return $isRegex($str) ? $str : Glob::toRegex($str);
}
