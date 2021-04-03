<?php

declare(strict_types=1);

namespace UnicornFail\ArrayObject;

use Consistence\Type\Type;

/**
 * @template            TKey of object
 * @template            TValue
 * @template-extends    AbstractObjectMap<TKey, TValue>
 * @template-implements TypedMapInterface<TKey, TValue>
 */
abstract class AbstractTypedArray extends AbstractObjectMap implements TypedMapInterface
{
    /** @var string */
    private $__valueType;

    /** @param TValue|array<TKey, TValue>|null $values */
    public function __construct(string $valueType, $values = null)
    {
        $this->__valueType = $valueType;
        parent::__construct($values);
    }

    public function getValueType(): string
    {
        return $this->__valueType;
    }

    public function map(callable $callback)
    {
        return parent::map(function ($value, $key) use ($callback) {
            /**
             * @var TValue $value
             * @var TKey   $key
             */
            $return = $callback($value, $key);

            Type::checkType($return, $this->getValueType());

            return $return;
        });
    }

    /**
     * @param ?TKey  $key
     * @param TValue $value
     *
     * @throws \Consistence\InvalidArgumentTypeException
     */
    public function offsetSet($key, $value): void
    {
        Type::checkType($value, $this->getValueType());

        parent::offsetSet($key, $value);
    }
}
