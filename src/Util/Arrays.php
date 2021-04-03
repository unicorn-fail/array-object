<?php

declare(strict_types=1);

namespace UnicornFail\ArrayObject\Util;

/**
 * @internal
 */
class Arrays
{
    /**
     * @template TKey of array-key
     * @template TValue
     *
     * @param array<TKey, TValue> $array
     *
     * @return array<TKey, TValue>
     */
    public static function flatten(array $array, bool $preserveKeys = false)
    {
        $flattened = [];

        \array_walk_recursive(
            $array,
            /**
             * @param TValue $v
             * @param TKey   $k
             */
            static function ($v, $k) use (&$flattened, $preserveKeys): void {
                if ($preserveKeys) {
                    /** @psalm-suppress MixedArrayAssignment */
                    $flattened[$k] = $v;
                } else {
                    /** @psalm-suppress MixedArrayAssignment */
                    $flattened[] = $v;
                }
            }
        );

        /** @var array<TKey, TValue> $flattened */
        return $flattened;
    }
}
