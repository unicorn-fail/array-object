<?php

declare(strict_types=1);

namespace UnicornFail\ArrayObject;

use UnicornFail\ArrayObject\Traits\MergeableArrayTrait;

/**
 * @template          TKey as array-key
 * @template          TValue
 * @template-extends  AbstractArrayObject<TKey, TValue>
 */
class ArrayObject extends AbstractObjectMap implements MergeableArrayInterface
{
    /** @use MergeableArrayTrait<TKey, TValue> */
    use MergeableArrayTrait;
}
