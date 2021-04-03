<?php

declare(strict_types=1);

namespace UnicornFail\ArrayObject\Traits;

use UnicornFail\ArrayObject\MapInterface;

/**
 * @template TKey of array-key
 * @template TValue
 * @template-implements ArrayObjectInterface<TKey, TValue>
 */
trait MergeableArrayTrait
{
    /**
     * Merges values into the array.
     *
     * @param static<TKey, TValue>|TValue ...$values The collections to merge.
     *
     * @return static<TKey, TValue>
     *
     * @psalm-suppress MoreSpecificReturnType
     *
     * @note Does not modify the existing object; returns a cloned instance instead.
     */
    public function merge(...$values)
    {
        \assert($this instanceof MapInterface);

        $array = clone $this;

        $args = [$array->toArray()];
        foreach ($values as $value) {
            $args[] = $value instanceof MapInterface ? $value->toArray() : [$value];
        }

        /** @var array<TKey, TValue> $merged */
        $merged = \array_merge(...$args);

        $this->clear();

        /** @psalm-suppress MixedArgumentTypeCoercion */
        $this->add(...$merged);

        return $array;
    }

    /**
     * Recursively merges values into the array.
     *
     * @param static<TKey, TValue>|TValue ...$values The collections to merge.
     *
     * @return static<TKey, TValue>
     *
     * @psalm-suppress MoreSpecificReturnType
     *
     * @note Does not modify the existing object; returns a cloned instance instead.
     */
    public function mergeRecursively(...$values)
    {
        \assert($this instanceof MapInterface);

        $array = clone $this;

        $args = [$array->toArray()];
        foreach ($values as $value) {
            $args[] = $value instanceof MapInterface ? $value->toArray() : [$value];
        }

        /** @var array<TKey, TValue> $merged */
        $merged = \array_merge_recursive(...$args);

        $this->clear();

        /** @psalm-suppress MixedArgumentTypeCoercion */
        $this->add(...$merged);

        return $array;
    }
}
