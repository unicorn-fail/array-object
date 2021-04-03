<?php

declare(strict_types=1);

namespace UnicornFail\ArrayObject;

/**
 * @template TValue
 * @extends  ArrayObjectInterface<int, TValue>
 */
interface SetInterface extends MapInterface, UniqueArrayObjectInterface
{
    /**
     * Ensures that this collection contains the specified element (optional
     * operation).
     *
     * Returns `true` if this collection changed as a result of the call.
     * (Returns `false` if this collection does not permit duplicates and
     * already contains the specified element.)
     *
     * Collections that support this operation may place limitations on what
     * elements may be added to this collection. In particular, some
     * collections will refuse to add `null` elements, and others will impose
     * restrictions on the type of elements that may be added. Collection
     * classes should clearly specify in their documentation any restrictions
     * on what elements may be added.
     *
     * If a collection refuses to add a particular element for any reason other
     * than that it already contains the element, it must throw an exception
     * (rather than returning `false`). This preserves the invariant that a
     * collection always contains the specified element after this call returns.
     *
     * @param TValue|array<array-key, TValue> $value The value to add to the array.
     *
     * @return bool `true` if this collection changed as a result of the call
     */
    public function add($value): bool;

    /**
     * Removes a single instance of the specified element from this collection,
     * if it is present.
     *
     * @param TValue $value  the element to remove from the collection
     * @param bool   $strict whether to perform a strict type check on the value
     *
     * @return bool `true` if an element was removed as a result of this call
     */
    public function remove($value, bool $strict = true): bool;

    /**
     * @param TValue $currentValue The current value that should be replaced.
     * @param TValue $newValue     The new value that will replace the current value with.
     * @param bool   $strict       whether to perform a strict type check on the value
     *
     * @return bool `true` if the current value was found and replaced with the new value.
     *              `false` if the current value was not found and the new value was not added.
     */
    public function replace($currentValue, $newValue, bool $strict = true): bool;
}
