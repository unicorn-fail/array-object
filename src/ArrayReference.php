<?php

declare(strict_types=1);

namespace UnicornFail\ArrayObject;

use UnicornFail\ArrayObject\Exception\WeakReferenceException;
use WeakReference;
use stdClass;

/**
 * @template TObject
 */
final class ArrayReference
{
    /** @var WeakReference */
    private WeakReference $reference;

    /** @param TObject $object */
    public function __construct($object)
    {
        if (! \is_object($object)) {
            $object = (object) [self::class => $object];
        }

        /** @var object $object */
        $this->reference = WeakReference::create($object);
    }

    /**
     * @return TObject
     *
     * @throws WeakReferenceException When the referenced object no longer exists.
     */
    public function get()
    {
        /** @var ?TObject $value */
        $value = $this->reference->get();

        if ($value === null) {
            throw new WeakReferenceException();
        }

        if ($value instanceof stdClass && \property_exists($value, self::class)) {
            /** @var TObject $value */
            $value = $value->${self::class};
        }

        return $value;
    }
}
