<?php

declare(strict_types=1);

namespace UnicornFail\ArrayObject;

/**
 * @template TKey as array-key
 * @template TValue
 */
interface MergeableArrayInterface
{
    /**
     * Merges values into the array.
     *
     * @param static<TKey, TValue>|TValue ...$values The collections to merge.
     *
     * @return static<TKey, TValue>
     *
     * @note Does not modify the existing object; returns a cloned instance instead.
     */
    public function merge(...$values);

    /**
     * Recursively merges values into the array.
     *
     * @param static<TKey, TValue>|TValue ...$values The collections to merge.
     *
     * @return static<TKey, TValue>
     *
     * @note Does not modify the existing object; returns a cloned instance instead.
     */
    public function mergeRecursively(...$values);
}
