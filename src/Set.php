<?php

declare(strict_types=1);

namespace UnicornFail\ArrayObject;

/**
 * @template   TKey of int
 * @template   TValue
 * @extends    ArrayObject<TKey, TValue>
 * @implements SetInterface<TKey, TValue>
 */
class Set extends ArrayObject implements SetInterface
{

    /** @param TValue|array<TKey, TValue> $value */
    public function add($value): bool
    {
        /** @var array<TValue|array<TKey, TValue>> $args */
        $args = \func_get_args();

        $values = \array_shift($args);
        if (! \is_array($values)) {
            $values = [$values];
        }

        if (\count($args) > 0) {
            /** @var array<TKey, TValue> $values */
            $values = \array_merge($values, ...\array_map(static function ($value) {
                return (array) $value;
            }, $args));
        }

        $successful = true;

        /** @var array<TKey, TValue> $values */
        foreach ($values as $key => $value) {
            // Prevent a value from being added if already set.
            if ($this instanceof UniqueArrayObjectInterface && $this->contains($value)) {
                $successful = false;
                continue;
            }

            if ($this instanceof AssociativeArrayObjectInterface) {
                $this[$key] = $value;
            } else {
                $this[] = $value;
            }
        }

        return $successful;
    }

    public function remove($value, bool $strict = true): bool
    {
        $key = $this->indexOf($value, $strict);

        if ($key === null) {
            return false;
        }

        unset($this[$key]);

        return true;
    }

    public function replace($currentValue, $newValue, bool $strict = true): bool
    {
        $key = $this->indexOf($currentValue, $strict);

        if ($key === null) {
            return false;
        }

        $this[$key] = $newValue;

        return true;
    }
}
