<?php

declare(strict_types=1);

namespace TimDev\ArrayUndot;

final class Undotter
{
    /**
     * Implements invokable for interop with, e.g., Laminas ConfigAggregator.
     *
     * @param  mixed[] $array
     * @return mixed[]
     */
    public function __invoke(array $array): array
    {
        return self::undot($array);
    }

    /**
     * Return a copy of $array where any dotted keys have been normalized
     * into nested values.
     *
     * @param  mixed[] $array
     * @return mixed[]
     */
    public static function undot(array $array): array
    {
        return self::transform($array);
    }

    /**
     * The actual implementation, with second, optional, argument needed for
     * recursion.
     */
    private static function transform(array $input, ?array &$output = null): array
    {
        $output = $output ?? [];
        foreach ($input as $k => $v) {
            if (is_array($v)) {
                $v = self::transform($v, $output[$k]);
            }

            if (is_string($k) && str_contains($k, '.')) {
                self::insertDotted($k, $v, $output);
                unset($output[$k]);
            } else {
                $output[$k] = $v;
            }
        }
        return $output;
    }

    /**
     * Set or merge $value into $array based on a dotted-string $key:
     *
     * Given a $key of 'a.b.c' and a value 'foo', sets $array['a']['b']['c'] =
     * 'foo'.
     *
     * If $value is an array, and $array already contains an array at $key,
     * $value is merged into the existing element (see mergeArray() for the
     * precise semantics of the merge).
     */
    private static function insertDotted(string $key, mixed $value, array &$array): void
    {
        $keys = explode('.', $key);

        foreach ($keys as $i => $k) {
            if (count($keys) === 1) {
                break;
            }

            unset($keys[$i]);

            if (!isset($array[$k]) || !is_array($array[$k])) {
                $array[$k] = [];
            }

            $array = &$array[$k];
        }

        $key = array_shift($keys)
            // @codeCoverageIgnoreStart
            ?? throw new \LogicException('$keys should always contain a single, string, element');
            // @codeCoverageIgnoreEnd
        if (is_array($value)) {
            $array[$key] = static::merge($array[$key] ?? [], $value);
        } else {
            $array[$key] = $value;
        }
    }

    /**
     * Recursively merge $b into $a and return the result.
     *
     * This is a slimmed down version of the method ConfigAggregator uses:
     *
     * https://github.com/laminas/laminas-config-aggregator/blob/1.6.x/src/ConfigAggregator.php#L156-L190
     *
     * (which itself is lifted from laminas/stdlib). I suspect it's subtly
     * different from array_merge[_recursive](), otherwise, why would it
     * even exist.
     */
    private static function merge(array $a, array $b): array
    {
        foreach ($b as $key => $value) {
            if (isset($a[$key]) || array_key_exists($key, $a)) {
                if (is_int($key)) {
                    $a[] = $value;
                } elseif (is_array($value) && is_array($a[$key])) {
                    $a[$key] = static::merge($a[$key], $value);
                } else {
                    $a[$key] = $value;
                }
            } else {
                $a[$key] = $value;
            }
        }
        return $a;
    }
}
