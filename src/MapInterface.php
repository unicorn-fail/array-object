<?php

declare(strict_types=1);

namespace UnicornFail\ArrayObject;

use ArrayAccess;
use Countable;
use JsonSerializable;
use SeekableIterator;
use Stringable;
use UnicornFail\ArrayObject\Exception\EmptyException;

/**
 * @template         TKey
 * @template         TValue
 * @template-extends ArrayAccess<TKey, TValue>
 * @template-extends SeekableIterator<TKey, TValue>
 */
interface MapInterface extends
    ArrayAccess,
    Countable,
    JsonSerializable,
    SeekableIterator,
    Stringable
{
    /**
     * Removes all items from this array.
     */
    public function clear(): void;

    /**
     * Returns `true` if the array contains the specified value.
     *
     * @param TValue $value  The value to find.
     * @param bool   $strict Flag indicating whether to perform a strict type check on the value.
     */
    public function contains($value, bool $strict = true): bool;

    /** @return array<int, array{0: TKey, 1: TValue}> */
    public function entries(): array;

    /**
     * Filter out items of the collection which don't match the criteria of
     * given callback.
     *
     * This will always leave the original collection untouched and will return
     * a new one.
     *
     * See the {@link http://php.net/manual/en/function.array-filter.php PHP array_filter() documentation}
     * for examples of how the `$callback` parameter works.
     *
     * @param callable(TValue):bool $callback A callable to use for filtering elements
     *
     * @return static<TKey, TValue>
     */
    public function filter(callable $callback);

    /**
     * Returns the first item of the collection.
     *
     * @return TValue
     *
     * @throws EmptyException
     */
    public function first();

    /**
     * Flattens any nested array values (multi-dimensional) in the array.
     *
     * @return static<TKey, TValue>
     *
     * @note Does not modify the existing object; returns a cloned instance instead.
     */
    public function flatten();

    /**
     * @param callable(TValue, TKey):void $callback A callable to use on each key/value pair.
     *
     * @return static<TKey, TValue>
     */
    public function forEach(callable $callback);

    /**
     * Returns `true` if the array has a specific key set.
     *
     * @param TValue $key          The key to find.
     * @param bool   $strict       Flag indicating whether to perform a strict type check on the key.
     * @param bool   $normalizeKey Flag indicating whether to normalize the key (used in object maps).
     */
    public function has($key, bool $strict = true, bool $normalizeKey = true): bool;

    /**
     * Retrieves the array index of the value.
     *
     * @param TValue $value
     * @param bool   $strict         Flag indicating whether to perform a strict type check on the value.
     * @param bool   $denormalizeKey Flag indicating whether to denormalize the key (used in object maps).
     *
     * @return ?TKey
     */
    public function indexOf($value, bool $strict = true, bool $denormalizeKey = true);

    /**
     * Returns `true` if this array is empty.
     */
    public function isEmpty(): bool;

    /**
     * Joins the array together as a string, using a separator.
     *
     * @param ?string $separator temporarily specifies the separator to be used instead of
     *                           the default separator set on the array
     */
    public function join(?string $separator = null): string;

    /** @return array<TKey> */
    public function keys(bool $denormalize = true): array;

    /**
     * Returns the last item of the collection.
     *
     * @return ?TValue
     *
     * @throws EmptyException
     */
    public function last();

    /**
     * Apply a given callback method on each item of the collection.
     *
     * This will always leave the original collection untouched. The new
     * collection is created by mapping the callback to each item of the
     * original collection.
     *
     * See the {@link http://php.net/manual/en/function.array-map.php PHP array_map() documentation}
     * for examples of how the `$callback` parameter works.
     *
     * @param callable(TValue, TKey):TValue $callback A callable to apply to each item of the array
     *
     * @return static<TKey, TValue>
     */
    public function map(callable $callback);

    /**
     * @return TValue
     *
     * @throws EmptyException
     */
    public function pop();

    /** @param array<TKey, TValue> $values */
    public function reset(array $values = []): void;

    /**
     * Sets the default separator used when joining the array together as a string.
     */
    public function setSeparator(string $separator): void;

    /**
     * @return TValue
     *
     * @throws EmptyException
     */
    public function shift();

    /**
     * Returns a native PHP array representation of this array object.
     *
     * @return array<array-key, TValue>
     */
    public function toArray(): array;
}
