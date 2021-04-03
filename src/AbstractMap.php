<?php

declare(strict_types=1);

namespace UnicornFail\ArrayObject;

use UnicornFail\ArrayObject\Exception\EmptyException;
use UnicornFail\ArrayObject\Util\Arrays;

/**
 * @template   TKey of array-key
 * @template   TValue
 * @implements MapInterface<TKey, TValue>
 */
abstract class AbstractMap implements MapInterface
{
    /**
     * Note: this is intentionally underscored to denote a private property to ensure
     * any subclassed object property names do not conflict.
     *
     * @var array<TKey, TValue>
     */
    private $__storage;

    /**
     * Note: this is intentionally underscored to denote a private property to ensure
     * any subclassed object property names do not conflict.
     *
     * @var string
     */
    protected string $__separator = ', ';

    /** @param TValue|array<TKey, TValue>|null $values */
    public function __construct($values = null)
    {
        $this->__storage = static::createStorage();

        if ($values === null) {
            return;
        }

        if (! \is_iterable($values)) {
            $values = [$values];
        }

        /** @var iterable<TKey, TValue> $values */
        foreach ($values as $key => $value) {
            $this[$key] = $value;
        }
    }

    public function __serialize(): array
    {
        return \get_object_vars($this);
    }

    public function __unserialize(array $data): void
    {
        /** @var array<TKey, TValue> $storage */
        $storage           = $data['__storage'] ?? static::createStorage();
        $this->__storage   = $storage;
        $this->__separator = (string) ($data['__separator'] ?? ', ');
    }

    public function __toString(): string
    {
        return $this->join();
    }

    /** @return array<TKey, TValue> */
    protected static function createStorage()
    {
        return [];
    }

    protected function assertNotEmpty(): void
    {
        if ($this->isEmpty()) {
            throw new EmptyException(self::class);
        }
    }

    public function clear(): void
    {
        $this->__storage = [];
    }

    public function contains($value, bool $strict = true): bool
    {
        // phpcs:ignore SlevomatCodingStandard.Functions.StrictCall.NonStrictComparison
        return \in_array($value, $this->toArray(), $strict);
    }

    public function count(): int
    {
        return \count($this->__storage);
    }

    /** @return TValue */
    public function current()
    {
        $this->assertNotEmpty();

        return \current($this->__storage);
    }

    /**
     * @param ?mixed $key
     *
     * @return ?TKey
     */
    protected function denormalizeKey($key)
    {
        /** @var ?TKey $key */
        return $key;
    }

    /** @return array<int, array{0: TKey, 1: TValue}> */
    public function entries(): array
    {
        $entries = [];

        foreach ($this->__storage as $key => $value) {
            $entries[] = [$key, $value];
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
        $this->assertNotEmpty();

        $currentKey = $this->key();

        $this->rewind();

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

    public function has($key, bool $strict = true, bool $normalizeKey = true): bool
    {
        if ($normalizeKey) {
            $key = $this->normalizeKey($key);
        }

        // phpcs:ignore SlevomatCodingStandard.Functions.StrictCall.NonStrictComparison
        return \in_array($key, $this->keys(), $strict);
    }

    public function indexOf($value, bool $strict = true, bool $denormalizeKey = true)
    {
        // phpcs:ignore SlevomatCodingStandard.Functions.StrictCall.NonStrictComparison
        $key = \array_search($value, $this->toArray(), $strict);

        if ($key === false) {
            return null;
        }

        if ($denormalizeKey) {
            return $this->denormalizeKey($key);
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
    public function key(bool $denormalize = true)
    {
        $key = \key($this->__storage);

        if ($denormalize) {
            $this->denormalizeKey($key);
        }

        return $key;
    }

    /** @return array<TKey> */
    public function keys(bool $denormalize = true): array
    {
        $keys = \array_keys($this->__storage);

        if ($denormalize) {
            $keys = \array_map([$this, 'denormalizeKey'], $keys);
        }

        /** @var array<TKey> $keys */
        return $keys;
    }

    public function last()
    {
        $key = $this->key(false);

        if ($this->isEmpty()) {
            throw new EmptyException(self::class);
        }

        $last = null;
        while (($current = $this->current()) !== null) {
            $last = $current;
            $this->next();
        }

        $this->resetPosition($key);

        return $last;
    }

    public function next(): void
    {
        \next($this->__storage);
    }

    /**
     * @param ?TKey $key
     *
     * @return ?TKey
     */
    protected function normalizeKey($key)
    {
        return $key;
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

    /** @param ?TKey $key */
    protected function resetPosition($key = null): void
    {
        $this->rewind();

        if ($key === null) {
            return;
        }

        $key = $this->normalizeKey($key);
        while ($this->key() !== $key && $this->valid()) {
            $this->next();
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

    /**
     * Allows sub-classes to retrieve the internal storage object.
     *
     * @return array<TKey, TValue>
     */
    protected function storage()
    {
        return $this->__storage;
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
