<?php

declare(strict_types=1);

namespace UnicornFail\ArrayObject\Exception;

class EmptyException extends \OutOfBoundsException
{
    /** @param class-string $class */
    public function __construct(string $class, ?\Throwable $previous = null)
    {
        parent::__construct(\sprintf('%s is empty.', $class), 0, $previous);
    }
}
