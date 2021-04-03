<?php

declare(strict_types=1);

namespace UnicornFail\ArrayObject;

use SplObjectStorage;
use UnicornFail\ArrayObject\Exception\EmptyException;
use UnicornFail\ArrayObject\Exception\WeakReferenceException;
use UnicornFail\ArrayObject\Util\Arrays;
use WeakReference;

/**
 * @template TKey of object
 * @template TValue
 * @extends  AbstractMap<TKey, TValue>
 */
abstract class AbstractObjectMap extends AbstractMap
{
    /** @return SplObjectStorage<ArrayReference<TKey>, TValue> */
    protected static function createStorage(): SplObjectStorage
    {
        /** @var SplObjectStorage<ArrayReference<TKey>, TValue> $storage */
        $storage = new SplObjectStorage();

        return $storage;
    }

    /**
     * @param ?mixed $key
     *
     * @return ?TKey
     */
    protected function denormalizeKey($key)
    {
        if ($key instanceof WeakReference) {
            if (($object = $key->get()) === null) {
                throw new WeakReferenceException();
            }

            return $object;
        }

        /** @var ?TKey $key */
        return $key;
    }

    /** @return array<int, array{0: TKey, 1: TValue}> */
    public function entries(): array
    {
        $entries = [];

        /** @var array<ArrayReference<TKey>> $keys */
        $keys = \iterator_to_array($this->__storage);

        foreach ($keys as $key) {
            try {
                /** @var ArrayReference<TValue> $value */
                $value = $this->__storage[$key];

                $entries[] = [$key->get(), $value->get()];
            } catch (WeakReferenceException $e) {
                continue;
            }
        }

        return $entries;
    }

    public function filter(callable $callback)
    {
        $clone = clone $this;

        /** @var array<TKey, TValue> $filtered */
        $filtered = \array_filter($clone->toArray(), $callback);

        $clone->reset($filtered);

        return $clone;
    }

    public function first()
    {
        if ($this->isEmpty()) {
            throw new EmptyException(self::class);
        }

        $currentKey = $this->__storage->current();

        $this->__storage->rewind();

        /** @var TValue $first */
        $first = $this->current();

        $this->resetPosition($currentKey);

        return $first;
    }

    public function flatten()
    {
        $clone = clone $this;

        /** @var array<TKey, TValue> $values */
        $values = Arrays::flatten($clone->toArray(), $clone instanceof AssociativeArrayObjectInterface);

        $clone->reset($values);

        return $clone;
    }

    public function forEach(callable $callback)
    {
        $clone = clone $this;

        foreach ($clone->entries() as $entry) {
            [$key, $value] = $entry;
            $callback($value, $key);
        }

        return $clone;
    }

    /**
     * Retrieves a key object by their Spl object identifier
     *
     * @return ?TKey
     */
    public function getKeyObjectById(int $id)
    {
        foreach ($this->__storage as $key) {
            if (\spl_object_id($key) === $id) {
                /** @var TKey $key */
                return $key;
            }
        }

        return null;
    }

    public function has($key, bool $strict = true): bool
    {
        // phpcs:ignore SlevomatCodingStandard.Functions.StrictCall.NonStrictComparison
        return \in_array($key, $this->keys(), $strict);
    }

    public function indexOf($value, bool $strict = true, bool $asObject = true)
    {
        // phpcs:ignore SlevomatCodingStandard.Functions.StrictCall.NonStrictComparison
        $key = \array_search($value, $this->toArray(), $strict);

        if ($key === false) {
            return null;
        }

        if ($asObject) {
            $key = $this->normalizeKey($key);
        }

        /** @var TKey $key */
        return $key;
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    public function join(?string $separator = null): string
    {
        return \implode($separator ?? $this->__separator, $this->toArray());
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /** @return ?TKey */
    public function key()
    {
        return $this->denormalizeKey($this->__storage->current());
    }

    /** @return array<TKey> */
    public function keys(bool $asObjects = true): array
    {
        /** @var array<TKey> $keys */
        $keys = \array_map([$this, 'denormalizeKey'], \iterator_to_array($this->__storage));

        if ($asObjects) {
            $keys = \array_map(
            /**
             * @param TKey $key
             *
             * @return TKey
             */
                function ($key) {
                    if (\is_int($key) && ($object = $this->getKeyObjectById($key))) {
                        $key = $object;
                    }

                    return $key;
                },
                $keys
            );
        }

        /** @var array<TKey> $keys */
        return $keys;
    }

    public function last()
    {
        $key = $this->__storage->current();

        if ($this->isEmpty()) {
            throw new EmptyException(self::class);
        }

        $last = null;
        while (($next = $this->current()) !== null) {
            $last = $next;
            $this->next();
        }

        $this->resetPosition($key);

        return $last;
    }

    public function next(): void
    {
        $this->__storage->next();
    }

    /**
     * @param ?mixed $key
     *
     * @return ArrayReference<TKey>
     */
    protected function normalizeKey($key): ArrayReference
    {
        foreach ($this->__storage as $storageKey) {
            if ($storageKey->ref() === $key) {
                return $storageKey;
            }
        }

        return new ArrayReference($key);
    }

    public function map(callable $callback)
    {
        $clone = clone $this;

        /** @var array<TKey, TValue> $values */
        $values = $clone->toArray();

        foreach ($values as $key => $value) {
            $clone[$key] = \call_user_func($callback, $value, $key);
        }

        return $clone;
    }

    /** @param TKey $key */
    public function offsetExists($key): bool
    {
        return $this->__storage->offsetExists($this->normalizeKey($key));
    }

    /**
     * @param TKey $key
     *
     * @return ?TValue
     */
    public function offsetGet($key)
    {
        if (! $this->offsetExists($key)) {
            return null;
        }

        return $this->__storage->offsetGet($this->normalizeKey($key));
    }

    /**
     * @param TKey|array-key|null $key
     * @param TValue              $value
     */
    public function offsetSet($key, $value): void
    {
        // Prevent a value from being added if already set.
        if ($this instanceof UniqueArrayObjectInterface && $this->contains($value)) {
            return;
        }

        $currentKey = $this->__storage->current();

        // Ensure non-associative arrays are indexed.
        if ($key !== null && ! ($this instanceof AssociativeArrayObjectInterface)) {
            $key = null;
        }

        // Ensure object identifiers are converted to their actual objects.
        if (\is_int($key) && ($obj = $this->getKeyObjectById($key))) {
            $key = $obj;
        } else {
            $key = $this->normalizeKey($key);
        }

        $this->__storage->offsetSet($key, $value);

        $this->resetPosition($currentKey);
    }

    /** @param ?TKey $key */
    public function offsetUnset($key): void
    {
        $currentKey = $this->__storage->current();

        $this->__storage->offsetUnset($this->normalizeKey($key));

        $this->resetPosition($currentKey);

        if ($this instanceof AssociativeArrayObjectInterface) {
            return;
        }

        $values = $this->values();
        $this->clear();

        foreach ($values as $key => $value) {
            $this->offsetSet($key, $value);
        }
    }

    public function pop()
    {
        if ($this->isEmpty()) {
            throw new EmptyException(static::class);
        }

        $currentKey = $this->__storage->current();

        $this->__storage->rewind();

        $lastKey = null;
        while (($nextKey = $this->__storage->current()) !== null) {
            $lastKey = $nextKey;
            $this->__storage->next();
        }

        /** @var TValue $popped */
        $popped = $this->__storage->offsetGet($lastKey);

        $this->offsetUnset($lastKey);

        $this->resetPosition($currentKey);

        return $popped;
    }

    public function reset(array $values = []): void
    {
        $this->clear();
        foreach ($values as $key => $value) {
            $this[$key] = $value;
        }
    }

    protected function resetPosition(?object $key = null): void
    {
        $this->__storage->rewind();

        if ($key === null) {
            return;
        }

        while ($this->__storage->current() !== $key && $this->__storage->valid()) {
            $this->__storage->next();
        }
    }

    public function rewind()
    {
        $this->__storage->rewind();
    }

    public function seek($position): void
    {
        $position--;

        $this->__storage->rewind();
        for ($i = 0; $i <= $position; $i++) {
            $this->__storage->next();
        }
    }

    public function setSeparator(string $separator): void
    {
        $this->__separator = $separator;
    }

    public function shift()
    {
        if ($this->isEmpty()) {
            throw new EmptyException(static::class);
        }

        $currentKey = $this->__storage->current();

        $this->__storage->rewind();

        $firstKey = $this->__storage->current();

        $shifted = $this->__storage->offsetGet($firstKey);

        $this->__storage->offsetUnset($firstKey);

        $this->resetPosition($currentKey);

        return $shifted;
    }

    public function toArray(): array
    {
        /** @var array<array-key> $keys */
        $keys = $this->keys(false);

        return \array_combine($keys, $this->values()) ?: [];
    }

    public function valid()
    {
        return $this->__storage->valid();
    }

    /** @return array<TValue> */
    public function values(): array
    {
        return \array_map(function (object $key) {
            return $this->__storage[$key];
        }, \iterator_to_array($this->__storage));
    }
}
