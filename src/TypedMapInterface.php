<?php

declare(strict_types=1);

namespace UnicornFail\ArrayObject;

/**
 * @template         TKey as object
 * @template         TValue
 * @template-extends MapInterface<TKey, TValue>
 */
interface TypedMapInterface extends MapInterface
{
    public function getValueType(): string;
}
